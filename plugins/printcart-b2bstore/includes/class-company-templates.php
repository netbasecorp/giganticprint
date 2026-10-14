<?php
/**
 * Private template library per company: members of an approved B2B company (and administrators)
 * can save the current design as a company template, list / rename / clone / delete templates and
 * start a new order from one. Templates are never visible outside the company.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Company_Templates {

    const DB_VERSION       = '1';
    const OPT_DB_VERSION   = 'pcs_b4_tpl_db_version';
    const NONCE_ACTION     = 'pcs_ajax';
    const MAX_CONFIG_BYTES = 2097152;  // 2 MB
    const MAX_THUMB_BYTES  = 61440;    // 60 KB
    const MAX_NAME         = 190;
    const MAX_PER_STORE    = 300;
    const OPT_SETTINGS     = 'pcs_store_settings';

    public static function init() {
        self::maybe_create_table();

        /* logged-in only: no wp_ajax_nopriv_ handlers */
        add_action( 'wp_ajax_gp_tpl_list',   array( __CLASS__, 'ajax_list' ) );
        add_action( 'wp_ajax_gp_tpl_get',    array( __CLASS__, 'ajax_get' ) );
        add_action( 'wp_ajax_gp_tpl_save',   array( __CLASS__, 'ajax_save' ) );
        add_action( 'wp_ajax_gp_tpl_rename', array( __CLASS__, 'ajax_rename' ) );
        add_action( 'wp_ajax_gp_tpl_clone',  array( __CLASS__, 'ajax_clone' ) );
        add_action( 'wp_ajax_gp_tpl_delete', array( __CLASS__, 'ajax_delete' ) );
        add_action( 'wp_ajax_gp_img_thumb',  array( __CLASS__, 'ajax_img_thumb' ) );
        add_action( 'pcs_store_deleted',     array( __CLASS__, 'purge_store' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Storage                                                            */
    /* ---------------------------------------------------------------- */

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'pcs_company_templates';
    }

    public static function maybe_create_table() {
        if ( get_option( self::OPT_DB_VERSION ) === self::DB_VERSION ) {
            return;
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table   = self::table();
        $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  store_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  name varchar(190) NOT NULL DEFAULT '',
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  config longtext NOT NULL,
  thumb mediumtext NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY store_id (store_id)
) {$charset};" );
        update_option( self::OPT_DB_VERSION, self::DB_VERSION );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', absint( $id ) ) );
    }

    /* ---------------------------------------------------------------- */
    /* Access control (same rules as the Image Zone)                      */
    /* ---------------------------------------------------------------- */

    /** Ids of the approved companies of the user (ascending). */
    public static function user_store_ids( $user_id ) {
        return class_exists( 'PCS_Company_Images' ) ? PCS_Company_Images::user_store_ids( $user_id ) : array();
    }

    public static function can_access( $store_id, $user_id ) {
        return class_exists( 'PCS_Company_Images' ) && PCS_Company_Images::can_access( $store_id, $user_id );
    }

    /** Requested company if allowed, otherwise the user's first approved company (0 = none). */
    public static function resolve_store( $user_id, $requested = 0 ) {
        return class_exists( 'PCS_Company_Images' ) ? PCS_Company_Images::resolve_store( $user_id, $requested ) : 0;
    }

    /** Creator, company owner, company admin member or site administrator. */
    public static function can_delete( $row, $user_id ) {
        return class_exists( 'PCS_Company_Images' ) && PCS_Company_Images::can_delete( $row, $user_id );
    }

    /* ---------------------------------------------------------------- */
    /* Company setting: hide the public templates                         */
    /* ---------------------------------------------------------------- */

    public static function hide_public( $store_id ) {
        $store_id = absint( $store_id );
        if ( ! $store_id ) {
            return false;
        }
        if ( class_exists( 'PCS_Private_Products' ) && method_exists( 'PCS_Private_Products', 'get_store_setting' ) ) {
            return (bool) PCS_Private_Products::get_store_setting( $store_id, 'hide_public_templates', false );
        }
        $all = get_option( self::OPT_SETTINGS, array() );
        return is_array( $all ) && isset( $all[ $store_id ] ) && is_array( $all[ $store_id ] )
            && ! empty( $all[ $store_id ]['hide_public_templates'] );
    }

    /* ---------------------------------------------------------------- */
    /* Validation                                                         */
    /* ---------------------------------------------------------------- */

    /** @return string|WP_Error the cleaned name */
    public static function clean_name( $name ) {
        $name = trim( sanitize_text_field( (string) $name ) );
        if ( '' === $name ) {
            return new WP_Error( 'gp_tpl_name', 'Please enter a template name.' );
        }
        return mb_substr( $name, 0, self::MAX_NAME );
    }

    /** @return string|WP_Error the config JSON string (unchanged) */
    public static function validate_config( $config ) {
        if ( ! is_string( $config ) || '' === trim( $config ) ) {
            return new WP_Error( 'gp_tpl_config', 'The template content is missing.' );
        }
        if ( strlen( $config ) > self::MAX_CONFIG_BYTES ) {
            return new WP_Error( 'gp_tpl_big', 'The template is larger than 2 MB.' );
        }
        $data = json_decode( $config, true );
        if ( null === $data || ! is_array( $data ) ) {
            return new WP_Error( 'gp_tpl_json', 'The template content is not valid.' );
        }
        return $config;
    }

    /** A small JPEG data URL, or '' when missing / invalid / too big. */
    public static function clean_thumb( $thumb ) {
        if ( ! is_string( $thumb ) || '' === $thumb || strlen( $thumb ) > self::MAX_THUMB_BYTES ) {
            return '';
        }
        if ( ! preg_match( '#^data:image/jpeg;base64,[A-Za-z0-9+/]+={0,2}$#', $thumb ) ) {
            return '';
        }
        return $thumb;
    }

    /** The product must exist and be visible to the user. */
    public static function check_product( $product_id, $user_id ) {
        $product_id = absint( $product_id );
        if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
            return new WP_Error( 'gp_tpl_product', 'This product does not exist.' );
        }
        $manager = user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'manage_woocommerce' );
        if ( 'publish' !== get_post_status( $product_id ) && ! $manager ) {
            return new WP_Error( 'gp_tpl_product', 'This product is not available.' );
        }
        if ( class_exists( 'PCS_Private_Products' ) && ! PCS_Private_Products::is_visible( $product_id, $user_id ) ) {
            return new WP_Error( 'gp_tpl_product', 'This product is not available.' );
        }
        return $product_id;
    }

    /* ---------------------------------------------------------------- */
    /* Core operations (testable without HTTP)                            */
    /* ---------------------------------------------------------------- */

    /**
     * Create ($id = 0) or update a template. Checks membership of $user_id for $store_id.
     *
     * @return array|WP_Error item as returned by item_to_array()
     */
    public static function save( $store_id, $user_id, $name, $product_id, $config, $thumb = '', $id = 0 ) {
        global $wpdb;
        $store_id = absint( $store_id );
        $user_id  = absint( $user_id );
        $id       = absint( $id );

        if ( ! $store_id || ! self::can_access( $store_id, $user_id ) ) {
            return new WP_Error( 'gp_tpl_denied', 'Templates are available to members of an approved company store.' );
        }
        $cfg = self::validate_config( $config );
        if ( is_wp_error( $cfg ) ) {
            return $cfg;
        }
        $pid = self::check_product( $product_id, $user_id );
        if ( is_wp_error( $pid ) ) {
            return $pid;
        }
        $thumb = self::clean_thumb( $thumb );
        $now   = gmdate( 'Y-m-d H:i:s' );

        if ( $id ) {
            $row = self::get( $id );
            if ( ! $row || (int) $row->store_id !== $store_id || ! self::can_access( $row->store_id, $user_id ) ) {
                return new WP_Error( 'gp_tpl_denied', 'You cannot change this template.' );
            }
            $data   = array( 'product_id' => $pid, 'config' => $cfg, 'updated_at' => $now );
            $format = array( '%d', '%s', '%s' );
            if ( '' !== trim( (string) $name ) ) {
                $n = self::clean_name( $name );
                if ( is_wp_error( $n ) ) {
                    return $n;
                }
                $data['name'] = $n;
                $format[]     = '%s';
            }
            if ( '' !== $thumb ) {
                $data['thumb'] = $thumb;
                $format[]      = '%s';
            }
            if ( false === $wpdb->update( self::table(), $data, array( 'id' => $id ), $format, array( '%d' ) ) ) {
                return new WP_Error( 'gp_tpl_db', 'Could not save the template.' );
            }
            return self::item_to_array( self::get( $id ), $user_id );
        }

        $n = self::clean_name( $name );
        if ( is_wp_error( $n ) ) {
            return $n;
        }
        $count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE store_id = %d', $store_id ) );
        if ( $count >= self::MAX_PER_STORE ) {
            return new WP_Error( 'gp_tpl_limit', 'Your company has reached the limit of ' . self::MAX_PER_STORE . ' templates. Delete some first.' );
        }
        $ok = $wpdb->insert(
            self::table(),
            array(
                'store_id'   => $store_id,
                'user_id'    => $user_id,
                'name'       => $n,
                'product_id' => $pid,
                'config'     => $cfg,
                'thumb'      => $thumb,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
        );
        if ( ! $ok ) {
            return new WP_Error( 'gp_tpl_db', 'Could not save the template.' );
        }
        return self::item_to_array( self::get( (int) $wpdb->insert_id ), $user_id );
    }

    /** @return array|WP_Error */
    public static function rename( $id, $user_id, $name ) {
        global $wpdb;
        $row = self::get( $id );
        if ( ! $row || ! self::can_access( $row->store_id, $user_id ) ) {
            return new WP_Error( 'gp_tpl_denied', 'You cannot change this template.' );
        }
        $n = self::clean_name( $name );
        if ( is_wp_error( $n ) ) {
            return $n;
        }
        $wpdb->update( self::table(), array( 'name' => $n, 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'id' => (int) $row->id ), array( '%s', '%s' ), array( '%d' ) );
        return self::item_to_array( self::get( $row->id ), $user_id );
    }

    /** @return array|WP_Error the copy */
    public static function clone_template( $id, $user_id ) {
        global $wpdb;
        $row = self::get( $id );
        if ( ! $row || ! self::can_access( $row->store_id, $user_id ) ) {
            return new WP_Error( 'gp_tpl_denied', 'You cannot copy this template.' );
        }
        $count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE store_id = %d', (int) $row->store_id ) );
        if ( $count >= self::MAX_PER_STORE ) {
            return new WP_Error( 'gp_tpl_limit', 'Your company has reached the limit of ' . self::MAX_PER_STORE . ' templates. Delete some first.' );
        }
        $now = gmdate( 'Y-m-d H:i:s' );
        $ok  = $wpdb->insert(
            self::table(),
            array(
                'store_id'   => (int) $row->store_id,
                'user_id'    => absint( $user_id ),
                'name'       => mb_substr( 'Copy of ' . $row->name, 0, self::MAX_NAME ),
                'product_id' => (int) $row->product_id,
                'config'     => $row->config,
                'thumb'      => $row->thumb,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
        );
        if ( ! $ok ) {
            return new WP_Error( 'gp_tpl_db', 'Could not copy the template.' );
        }
        return self::item_to_array( self::get( (int) $wpdb->insert_id ), $user_id );
    }

    /** @return true|WP_Error */
    public static function delete( $id, $user_id ) {
        global $wpdb;
        $row = self::get( $id );
        if ( ! $row || ! self::can_delete( $row, $user_id ) ) {
            return new WP_Error( 'gp_tpl_denied', 'You cannot delete this template.' );
        }
        if ( false === $wpdb->delete( self::table(), array( 'id' => (int) $row->id ), array( '%d' ) ) ) {
            return new WP_Error( 'gp_tpl_db', 'Could not delete the template.' );
        }
        return true;
    }

    /** Remove every template of a company (e.g. when the company is deleted). */
    public static function purge_store( $store_id ) {
        global $wpdb;
        $wpdb->delete( self::table(), array( 'store_id' => absint( $store_id ) ), array( '%d' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Same-origin image proxy for template thumbnails (cloud art)        */
    /* ---------------------------------------------------------------- */

    const THUMB_PROXY_MAX    = 800;        // longest side of the proxied image
    const THUMB_PROXY_BYTES  = 62914560;   // 60 MB download cap
    const THUMB_PROXY_PIXELS = 200000000;  // refuse images over 200 megapixels
    const THUMB_RATE_MAX     = 120;        // proxied images per user ...
    const THUMB_RATE_WINDOW  = 600;        // ... per 10 minutes

    /** Hosts the proxy may download from (filter pcs_thumb_hosts). */
    public static function thumb_hosts() {
        $hosts = apply_filters( 'pcs_thumb_hosts', array( 'files.printcart.com' ) );
        $out   = array();
        foreach ( (array) $hosts as $h ) {
            $h = strtolower( trim( (string) $h ) );
            if ( '' !== $h ) {
                $out[] = $h;
            }
        }
        return $out;
    }

    /** https URL, no credentials, default port, host on the allowlist. */
    public static function thumb_url_allowed( $url ) {
        if ( ! is_string( $url ) || '' === $url || strlen( $url ) > 2000 || preg_match( '/[\x00-\x20\x7f]/', $url ) ) {
            return false;
        }
        $p = wp_parse_url( $url );
        if ( ! is_array( $p ) || empty( $p['scheme'] ) || empty( $p['host'] ) || 'https' !== strtolower( $p['scheme'] ) ) {
            return false;
        }
        if ( isset( $p['user'] ) || isset( $p['pass'] ) || ( isset( $p['port'] ) && 443 !== (int) $p['port'] ) ) {
            return false;
        }
        return in_array( strtolower( $p['host'] ), self::thumb_hosts(), true );
    }

    /**
     * Downscale an image (local file path or allowlisted https URL) to at most $max px on the longest side,
     * transparency flattened on white, as JPEG.
     *
     * @return array|WP_Error array( 'bytes' => binary string, 'mime' => 'image/jpeg', 'width' => int, 'height' => int )
     */
    public static function make_thumb( $src, $max = 0 ) {
        $max = $max > 0 ? min( (int) $max, self::THUMB_PROXY_MAX ) : self::THUMB_PROXY_MAX;
        if ( ! class_exists( 'Imagick' ) ) {
            return new WP_Error( 'gp_thumb_env', 'Image processing is not available.' );
        }
        $tmp   = '';
        $local = '';
        if ( is_string( $src ) && 0 === strpos( $src, 'https://' ) ) {
            if ( ! self::thumb_url_allowed( $src ) ) {
                return new WP_Error( 'gp_thumb_host', 'This image host is not allowed.' );
            }
            $tmp = wp_tempnam( 'gpthumb' );
            if ( ! $tmp ) {
                return new WP_Error( 'gp_thumb_tmp', 'Could not create a temporary file.' );
            }
            $res = wp_safe_remote_get( $src, array(
                'timeout'             => 20,
                'redirection'         => 0,
                'stream'              => true,
                'filename'            => $tmp,
                'limit_response_size' => self::THUMB_PROXY_BYTES,
                'user-agent'          => 'GigPrintsThumb/1.0',
            ) );
            if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
                @unlink( $tmp );
                return new WP_Error( 'gp_thumb_fetch', 'Could not download the image.' );
            }
            clearstatcache( true, $tmp );
            $size = (int) @filesize( $tmp );
            if ( $size <= 0 || $size >= self::THUMB_PROXY_BYTES ) {
                @unlink( $tmp );
                return new WP_Error( 'gp_thumb_size', 'The image is empty or larger than 60 MB.' );
            }
            $local = $tmp;
        } elseif ( is_string( $src ) && is_file( $src ) ) {
            $local = $src;
        } else {
            return new WP_Error( 'gp_thumb_src', 'No image source.' );
        }

        $out = self::resize_local( $local, $max );
        if ( '' !== $tmp ) {
            @unlink( $tmp );
        }
        return $out;
    }

    private static function resize_local( $path, $max ) {
        $allowed = array( 'PNG', 'JPEG', 'GIF', 'WEBP' );
        $im      = null;
        try {
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_MEMORY, 268435456 );
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_MAP, 536870912 );
            Imagick::setResourceLimit( Imagick::RESOURCETYPE_TIME, 20 );

            $probe = new Imagick();
            $probe->pingImage( $path );
            $fmt = strtoupper( (string) $probe->getImageFormat() );
            $w   = (int) $probe->getImageWidth();
            $h   = (int) $probe->getImageHeight();
            $probe->clear();
            if ( ! in_array( $fmt, $allowed, true ) ) {
                return new WP_Error( 'gp_thumb_type', 'This is not a supported image.' );
            }
            if ( $w < 1 || $h < 1 || $w * $h > self::THUMB_PROXY_PIXELS ) {
                return new WP_Error( 'gp_thumb_dim', 'The image dimensions are not supported.' );
            }

            $im = new Imagick();
            $im->setBackgroundColor( new ImagickPixel( 'white' ) );
            $im->readImage( $path . '[0]' );
            $im->setImageBackgroundColor( new ImagickPixel( 'white' ) );
            if ( $im->getImageAlphaChannel() ) {
                $im->setImageAlphaChannel( Imagick::ALPHACHANNEL_REMOVE );
            }
            $im = $im->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
            if ( Imagick::COLORSPACE_CMYK === $im->getImageColorspace() ) {
                $im->transformImageColorspace( Imagick::COLORSPACE_SRGB );
            }
            if ( max( (int) $im->getImageWidth(), (int) $im->getImageHeight() ) > $max ) {
                $im->thumbnailImage( $max, $max, true );
            }
            $im->setImageFormat( 'jpeg' );
            $im->setImageCompressionQuality( 85 );
            $im->stripImage();
            $bytes = $im->getImageBlob();
            $ow    = (int) $im->getImageWidth();
            $oh    = (int) $im->getImageHeight();
            $im->clear();
            if ( '' === $bytes ) {
                return new WP_Error( 'gp_thumb_fail', 'Could not process the image.' );
            }
            return array( 'bytes' => $bytes, 'mime' => 'image/jpeg', 'width' => $ow, 'height' => $oh );
        } catch ( Throwable $e ) {
            if ( $im instanceof Imagick ) {
                $im->clear();
            }
            return new WP_Error( 'gp_thumb_type', 'This is not a supported image.' );
        }
    }

    /** GET/POST gp_img_thumb: u = allowlisted https image URL; returns a downscaled JPEG (members of an approved company only). */
    public static function ajax_img_thumb() {
        self::check_nonce();
        $uid = get_current_user_id();
        $sid = self::resolve_store( $uid, isset( $_REQUEST['store_id'] ) ? absint( $_REQUEST['store_id'] ) : 0 );
        if ( ! $sid ) {
            wp_send_json_error( array( 'message' => 'Not available for your account.' ), 403 );
        }
        $u = isset( $_REQUEST['u'] ) ? trim( (string) wp_unslash( $_REQUEST['u'] ) ) : '';
        if ( ! self::thumb_url_allowed( $u ) ) {
            wp_send_json_error( array( 'message' => 'This image host is not allowed.' ), 400 );
        }
        $key   = 'gp_thumb_rl_' . $uid;
        $count = (int) get_transient( $key );
        if ( $count >= self::THUMB_RATE_MAX ) {
            wp_send_json_error( array( 'message' => 'Too many requests. Please wait a few minutes.' ), 429 );
        }
        set_transient( $key, $count + 1, self::THUMB_RATE_WINDOW );

        $res = self::make_thumb( $u );
        if ( is_wp_error( $res ) ) {
            $code = $res->get_error_code();
            wp_send_json_error( array( 'message' => $res->get_error_message() ), in_array( $code, array( 'gp_thumb_fetch', 'gp_thumb_env' ), true ) ? 502 : 415 );
        }
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }
        nocache_headers();
        header( 'Cache-Control: private, max-age=3600' );
        header( 'Pragma:' );
        header( 'Expires:' );
        header( 'Vary: Cookie' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Type: ' . $res['mime'] );
        header( 'Content-Length: ' . strlen( $res['bytes'] ) );
        echo $res['bytes']; // phpcs:ignore WordPress.Security.EscapeOutput
        exit;
    }

    /* ---------------------------------------------------------------- */
    /* Presentation                                                       */
    /* ---------------------------------------------------------------- */

    public static function item_to_array( $row, $viewer_id = 0, $with_thumb = true ) {
        if ( ! $row ) {
            return array();
        }
        $pid     = (int) $row->product_id;
        $product = $pid ? get_the_title( $pid ) : '';
        $user    = get_userdata( (int) $row->user_id );
        return array(
            'id'           => (int) $row->id,
            'store_id'     => (int) $row->store_id,
            'name'         => (string) $row->name,
            'product_id'   => $pid,
            'product_name' => (string) wp_strip_all_tags( $product ),
            'thumb'        => $with_thumb ? (string) $row->thumb : '',
            'author'       => $user ? $user->display_name : '',
            'created'      => mysql2date( 'M j, Y', get_date_from_gmt( $row->created_at ) ),
            'updated'      => mysql2date( 'M j, Y', get_date_from_gmt( $row->updated_at ) ),
            'start_url'    => add_query_arg( array( 'product_id' => $pid, 'gp_tpl' => (int) $row->id ), home_url( '/create-your-own/' ) ),
            'can_delete'   => $viewer_id ? self::can_delete( $row, $viewer_id ) : false,
        );
    }

    /** Items of a company, newest first; optionally only for one product. */
    public static function list_for_store( $store_id, $viewer_id = 0, $product_id = 0, $with_thumb = true ) {
        global $wpdb;
        $sql  = 'SELECT * FROM ' . self::table() . ' WHERE store_id = %d';
        $args = array( absint( $store_id ) );
        if ( absint( $product_id ) ) {
            $sql   .= ' AND product_id = %d';
            $args[] = absint( $product_id );
        }
        $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT ' . (int) self::MAX_PER_STORE;
        $out  = array();
        foreach ( (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) as $r ) {
            $out[] = self::item_to_array( $r, $viewer_id, $with_thumb );
        }
        return $out;
    }

    /* ---------------------------------------------------------------- */
    /* AJAX                                                               */
    /* ---------------------------------------------------------------- */

    private static function check_nonce() {
        $nonce = isset( $_REQUEST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_nonce'] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Please log in.' ), 403 );
        }
    }

    private static function require_post() {
        if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
            wp_send_json_error( array( 'message' => 'POST required.' ), 405 );
        }
    }

    private static function fail( $err, $status = 400 ) {
        wp_send_json_error( array( 'message' => $err->get_error_message() ), 'gp_tpl_denied' === $err->get_error_code() ? 403 : $status );
    }

    private static function request_store() {
        $uid = get_current_user_id();
        $sid = self::resolve_store( $uid, isset( $_REQUEST['store_id'] ) ? absint( $_REQUEST['store_id'] ) : 0 );
        if ( ! $sid ) {
            wp_send_json_error( array( 'message' => 'Templates are available to members of an approved company store.' ), 403 );
        }
        return $sid;
    }

    public static function ajax_list() {
        self::check_nonce();
        $uid = get_current_user_id();
        $sid = self::request_store();
        $pid = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
        wp_send_json_success( array(
            'store_id' => $sid,
            'items'    => self::list_for_store( $sid, $uid, $pid, empty( $_REQUEST['no_thumb'] ) ),
        ) );
    }

    public static function ajax_get() {
        self::check_nonce();
        $uid = get_current_user_id();
        $row = self::get( isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0 );
        if ( ! $row || ! self::can_access( $row->store_id, $uid ) ) {
            wp_send_json_error( array( 'message' => 'Template not found.' ), 404 );
        }
        $ok = self::check_product( $row->product_id, $uid );
        if ( is_wp_error( $ok ) ) {
            self::fail( $ok );
        }
        $item           = self::item_to_array( $row, $uid, false );
        $item['config'] = json_decode( $row->config, true );
        wp_send_json_success( array( 'item' => $item ) );
    }

    public static function ajax_save() {
        self::check_nonce();
        self::require_post();
        $uid = get_current_user_id();
        $sid = self::request_store();
        $id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $res = self::save(
            $sid,
            $uid,
            isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
            isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
            isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '',
            isset( $_POST['thumb'] ) ? wp_unslash( $_POST['thumb'] ) : '',
            $id
        );
        if ( is_wp_error( $res ) ) {
            self::fail( $res );
        }
        $res['thumb'] = '';
        wp_send_json_success( array( 'item' => $res ) );
    }

    public static function ajax_rename() {
        self::check_nonce();
        self::require_post();
        $res = self::rename(
            isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
            get_current_user_id(),
            isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : ''
        );
        if ( is_wp_error( $res ) ) {
            self::fail( $res );
        }
        wp_send_json_success( array( 'item' => $res ) );
    }

    public static function ajax_clone() {
        self::check_nonce();
        self::require_post();
        $res = self::clone_template( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0, get_current_user_id() );
        if ( is_wp_error( $res ) ) {
            self::fail( $res );
        }
        wp_send_json_success( array( 'item' => $res ) );
    }

    public static function ajax_delete() {
        self::check_nonce();
        self::require_post();
        $id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $res = self::delete( $id, get_current_user_id() );
        if ( is_wp_error( $res ) ) {
            self::fail( $res );
        }
        wp_send_json_success( array( 'id' => $id ) );
    }
}
