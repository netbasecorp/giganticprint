<?php
/**
 * Cart design preview: show only non-empty sides, uncropped, with side captions.
 *
 * Core markup targeted: NBD_Plugin::render_cart() (class.nbdesigner.php), hooked on
 * woocommerce_after_cart_item_name at priority 1. It prints
 *   <img class="nbd_cart_item_design_preview" src=".../<nbd_item_key>/preview/frame_N.png?&t=..."/>
 * for every file in NBDESIGNER_CUSTOMER_DIR/<nbd_item_key>/preview. We buffer that action
 * (priority 0 .. 2) and rewrite only those <img> tags; the Edit design / remove links are untouched.
 *
 * A side is "empty" when design.json has an entry for that frame index with an empty
 * objects/layers list. When design.json is missing/unknown we never hide anything.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SG_Cart {

    private static $buffering = false;

    public static function init() {
        add_action( 'woocommerce_after_cart_item_name', array( __CLASS__, 'start' ), 0, 2 );
        add_action( 'woocommerce_after_cart_item_name', array( __CLASS__, 'end' ), 2, 2 );
        add_filter( 'woocommerce_cart_item_thumbnail', array( __CLASS__, 'thumbnail' ), 99, 3 );
    }

    /** nbd_item_key (design folder) of a cart item, or ''. */
    private static function session_key( $cart_item, $cart_item_key ) {
        $key = '';
        if ( is_array( $cart_item ) && isset( $cart_item['nbd_item_meta_ds']['nbd'] ) ) {
            $key = $cart_item['nbd_item_meta_ds']['nbd'];
        } elseif ( $cart_item_key && function_exists( 'WC' ) && WC()->session ) {
            $key = WC()->session->get( $cart_item_key . '_nbd' );
        }
        $key = is_string( $key ) ? $key : '';
        // folder name only: no traversal
        if ( $key === '' || ! preg_match( '/^[A-Za-z0-9_\-]+$/', $key ) ) return '';
        return $key;
    }

    /** Array of frame index => bool (true = has content), or null when unknown. */
    private static function frame_content( $key ) {
        if ( ! defined( 'NBDESIGNER_CUSTOMER_DIR' ) ) return null;
        $file = NBDESIGNER_CUSTOMER_DIR . '/' . $key . '/design.json';
        if ( ! is_readable( $file ) ) return null;
        $data = json_decode( (string) file_get_contents( $file ), true );
        if ( ! is_array( $data ) || ! $data ) return null;
        $out = array();
        foreach ( $data as $idx => $frame ) {
            if ( ! is_array( $frame ) ) continue;
            // core stores sides as frame_0, frame_1 ...: key by the number only
            $idx = preg_match( '/(\d+)$/', (string) $idx, $mi ) ? $mi[1] : $idx;
            if ( isset( $frame['objects'] ) && is_array( $frame['objects'] ) ) {
                $out[ (string) $idx ] = count( $frame['objects'] ) > 0;
            } elseif ( isset( $frame['layers'] ) && is_array( $frame['layers'] ) ) {
                $out[ (string) $idx ] = count( $frame['layers'] ) > 0;
            }
        }
        return $out ? $out : null;
    }

    /** Side names from config.json (product[N].orientation_name), index => name. */
    private static function side_names( $key ) {
        $names = array();
        $file  = NBDESIGNER_CUSTOMER_DIR . '/' . $key . '/config.json';
        if ( ! is_readable( $file ) ) return $names;
        $cfg = json_decode( (string) file_get_contents( $file ), true );
        if ( is_array( $cfg ) && isset( $cfg['product'] ) && is_array( $cfg['product'] ) ) {
            foreach ( $cfg['product'] as $i => $side ) {
                if ( is_array( $side ) && ! empty( $side['orientation_name'] ) && is_string( $side['orientation_name'] ) ) {
                    $names[ (string) $i ] = $side['orientation_name'];
                }
            }
        }
        return $names;
    }

    private static function frame_of( $src ) {
        $path = (string) parse_url( $src, PHP_URL_PATH );
        if ( preg_match( '/_(\d+)\.[A-Za-z]+$/', $path, $m ) ) return $m[1];
        return null;
    }

    public static function start( $cart_item = null, $cart_item_key = null ) {
        if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) return;
        if ( self::$buffering ) return;
        self::$buffering = true;
        ob_start();
    }

    public static function end( $cart_item = null, $cart_item_key = null ) {
        if ( ! self::$buffering ) return;
        self::$buffering = false;
        $html = ob_get_clean();
        if ( $html === false || $html === '' ) return;
        echo self::rewrite( $html, $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    private static function rewrite( $html, $cart_item, $cart_item_key ) {
        if ( strpos( $html, 'nbd_cart_item_design_preview' ) === false ) return $html;
        $key = self::session_key( $cart_item, $cart_item_key );
        if ( $key === '' ) return $html;
        $content = self::frame_content( $key );
        $names   = self::side_names( $key );

        $pattern = '/<img\b[^>]*\bnbd_cart_item_design_preview\b[^>]*>/i';
        if ( ! preg_match_all( $pattern, $html, $all ) ) return $html;

        // which images stay
        $keep = array();
        foreach ( $all[0] as $i => $tag ) {
            $frame = null;
            if ( preg_match( '/\bsrc=["\']([^"\']+)["\']/i', $tag, $m ) ) $frame = self::frame_of( html_entity_decode( $m[1] ) );
            $empty = ( $content !== null && $frame !== null && isset( $content[ $frame ] ) && $content[ $frame ] === false );
            $keep[ $i ] = array( 'tag' => $tag, 'frame' => $frame, 'empty' => $empty );
        }
        $visible = 0;
        foreach ( $keep as $k ) { if ( ! $k['empty'] ) $visible++; }
        if ( $visible === 0 ) return $html; // nothing reliable to show: leave core output alone

        $n = 0;
        $html = preg_replace_callback( $pattern, function ( $mm ) use ( &$n, $keep, $names, $visible ) {
            $info = isset( $keep[ $n ] ) ? $keep[ $n ] : null;
            $n++;
            if ( ! $info ) return $mm[0];
            if ( $info['empty'] ) return '';
            $cap = '';
            if ( $visible > 1 ) {
                $f   = $info['frame'];
                $lbl = ( $f !== null && isset( $names[ $f ] ) ) ? $names[ $f ] : sprintf( 'Side %d', ( $f !== null ? (int) $f : $n - 1 ) + 1 );
                $cap = '<figcaption class="sg-cart-side-cap">' . esc_html( $lbl ) . '</figcaption>';
            }
            return '<figure class="sg-cart-side">' . $mm[0] . $cap . '</figure>';
        }, $html );
        return $html === null ? '' : $html;
    }

    /** Use the first non-empty design preview as the cart thumbnail. */
    public static function thumbnail( $image, $cart_item = array(), $cart_item_key = '' ) {
        if ( ! function_exists( 'is_cart' ) || ! is_cart() ) return $image;
        if ( ! class_exists( 'Nbdesigner_IO' ) || ! defined( 'NBDESIGNER_CUSTOMER_DIR' ) ) return $image;
        $key = self::session_key( $cart_item, $cart_item_key );
        if ( $key === '' ) return $image;
        $list = Nbdesigner_IO::get_list_images( NBDESIGNER_CUSTOMER_DIR . '/' . $key . '/preview' );
        if ( ! $list ) return $image;
        if ( function_exists( 'nbd_sort_file_by_side' ) ) $list = nbd_sort_file_by_side( $list );
        $content = self::frame_content( $key );
        foreach ( $list as $img ) {
            $frame = self::frame_of( $img );
            if ( $content !== null && $frame !== null && isset( $content[ $frame ] ) && $content[ $frame ] === false ) continue;
            $src = Nbdesigner_IO::convert_path_to_url( $img ) . '?t=' . (int) @filemtime( $img );
            return '<img src="' . esc_url( $src ) . '" class="sg-cart-thumb" alt="" />';
        }
        return $image;
    }
}

SG_Cart::init();
