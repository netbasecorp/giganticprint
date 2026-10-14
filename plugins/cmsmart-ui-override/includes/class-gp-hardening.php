<?php
/**
 * Login rate limiting + user enumeration hardening.
 *
 * - Throttles failed logins per IP and per username (wp-login.php, WooCommerce
 *   login, XML-RPC and wp_signon() based AJAX logins all go through "authenticate").
 * - Hides user listing from anonymous visitors (REST users routes, ?author=N,
 *   author archives without published posts, users sitemap, xmlrpc multicall).
 */
defined( 'ABSPATH' ) || exit;

class GP_Hardening {

    const LOCK_MESSAGE = 'Too many failed attempts. Please try again in 15 minutes.';

    public static function init() {
        add_filter( 'authenticate',         array( __CLASS__, 'check_lockout' ), 30, 3 );
        add_action( 'wp_login_failed',      array( __CLASS__, 'record_failure' ), 10, 2 );
        add_action( 'wp_login',             array( __CLASS__, 'reset_username' ), 10, 1 );

        add_filter( 'rest_endpoints',       array( __CLASS__, 'filter_rest_endpoints' ) );
        add_action( 'template_redirect',    array( __CLASS__, 'block_author_enumeration' ), 0 );
        add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'filter_sitemap_provider' ), 10, 2 );
        add_filter( 'xmlrpc_methods',       array( __CLASS__, 'filter_xmlrpc_methods' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Settings                                                           */
    /* ---------------------------------------------------------------- */
    public static function max_attempts() {
        return max( 1, (int) apply_filters( 'gp_login_max_attempts', 5 ) );
    }

    public static function window() {
        return max( 60, (int) apply_filters( 'gp_login_window', 15 * MINUTE_IN_SECONDS ) );
    }

    public static function lockout() {
        return max( 60, (int) apply_filters( 'gp_login_lockout', 15 * MINUTE_IN_SECONDS ) );
    }

    /* ---------------------------------------------------------------- */
    /* Client IP: REMOTE_ADDR, CF header only when REMOTE_ADDR is private */
    /* ---------------------------------------------------------------- */
    public static function client_ip() {
        $remote = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $public = $remote && false !== filter_var( $remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );

        if ( ! $public && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $cf = trim( (string) wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
            if ( false !== filter_var( $cf, FILTER_VALIDATE_IP ) ) {
                return $cf;
            }
        }
        return $remote ? $remote : '0.0.0.0';
    }

    private static function key( $type, $value ) {
        return 'gp_lf_' . $type . '_' . md5( strtolower( (string) $value ) );
    }

    public static function normalize_username( $username ) {
        return strtolower( trim( (string) $username ) );
    }

    /* ---------------------------------------------------------------- */
    /* authenticate (priority 30, after core username/password check)     */
    /* ---------------------------------------------------------------- */
    public static function check_lockout( $user, $username, $password ) {
        $username = self::normalize_username( $username );
        if ( '' === $username ) {
            return $user; // cookie auth / empty form render
        }

        $ip_locked   = get_transient( self::key( 'lock_ip', self::client_ip() ) );
        $user_locked = get_transient( self::key( 'lock_u', $username ) );

        if ( $ip_locked || $user_locked ) {
            return new WP_Error( 'too_many_attempts', self::LOCK_MESSAGE );
        }
        return $user;
    }

    /* ---------------------------------------------------------------- */
    /* wp_login_failed: count, lock at threshold                          */
    /* ---------------------------------------------------------------- */
    public static function record_failure( $username, $error = null ) {
        if ( is_wp_error( $error ) && 'too_many_attempts' === $error->get_error_code() ) {
            return;
        }
        $username = self::normalize_username( $username );
        if ( '' === $username ) {
            return;
        }

        self::bump( 'ip', self::client_ip() );
        self::bump( 'u', $username );
    }

    private static function bump( $type, $value ) {
        $ckey  = self::key( 'cnt_' . $type, $value );
        $count = (int) get_transient( $ckey ) + 1;
        set_transient( $ckey, $count, self::window() );

        if ( $count >= self::max_attempts() ) {
            set_transient( self::key( 'lock_' . $type, $value ), 1, self::lockout() );
            delete_transient( $ckey );
        }
    }

    public static function reset_username( $user_login ) {
        $username = self::normalize_username( $user_login );
        if ( '' === $username ) {
            return;
        }
        delete_transient( self::key( 'cnt_u', $username ) );
        delete_transient( self::key( 'lock_u', $username ) );
    }

    /* Used by the CLI check script to wipe counters for an IP / username */
    public static function clear_all( $username, $ip = null ) {
        $username = self::normalize_username( $username );
        $ip       = $ip ? $ip : self::client_ip();
        delete_transient( self::key( 'cnt_u', $username ) );
        delete_transient( self::key( 'lock_u', $username ) );
        delete_transient( self::key( 'cnt_ip', $ip ) );
        delete_transient( self::key( 'lock_ip', $ip ) );
    }

    /* ---------------------------------------------------------------- */
    /* User enumeration                                                   */
    /* ---------------------------------------------------------------- */
    public static function filter_rest_endpoints( $endpoints ) {
        // list_users: admins; edit_posts: keeps the block editor author picker working.
        if ( current_user_can( 'list_users' ) || current_user_can( 'edit_posts' ) ) {
            return $endpoints;
        }
        unset( $endpoints['/wp/v2/users'] );
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
        return $endpoints;
    }

    public static function block_author_enumeration() {
        if ( is_user_logged_in() ) {
            return;
        }

        // ?author=N probing
        if ( isset( $_GET['author'] ) && ! is_admin() ) {
            wp_safe_redirect( home_url( '/' ), 301 );
            exit;
        }

        // /author/<login>/ for users who have nothing published
        if ( is_author() ) {
            $author_id = (int) get_queried_object_id();
            $types     = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
            if ( ! $author_id || 0 === (int) count_user_posts( $author_id, $types, true ) ) {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
                nocache_headers();
            }
        }
    }

    public static function filter_sitemap_provider( $provider, $name ) {
        if ( 'users' === $name ) {
            return false;
        }
        return $provider;
    }

    public static function filter_xmlrpc_methods( $methods ) {
        unset( $methods['system.multicall'] );
        return $methods;
    }
}
