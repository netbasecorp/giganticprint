<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * B2B Portal — standalone full-width page via shortcode [pcs_b2b_portal]
 *
 * Usage: Create a WordPress page, add [pcs_b2b_portal] to its content,
 * set the page template to "Full Width" (or use the plugin's auto-setup).
 *
 * Navigation via ?section=overview|store|team|approvals
 */
class PCS_B2B_Portal {

    /* Navigation sections */
    const SECTIONS = array(
        'overview'      => array( 'label' => 'Overview',       'icon' => '📊', 'b2b_only' => false ),
        'orders'        => array( 'label' => 'Orders',         'icon' => '📦', 'b2b_only' => false ),
        'store'         => array( 'label' => 'B2B Store',      'icon' => '🏢', 'b2b_only' => true  ),
        'team'          => array( 'label' => 'Teams',          'icon' => '👥', 'b2b_only' => true  ),
        // 'approvals' (Approval Queue) is hidden: not in the customer's requirements, listed as a Phase 2 idea (view kept in frontend/portal/sections/approvals.php).
        'images'        => array( 'label' => 'Image Zone',     'icon' => '🖼️', 'b2b_only' => true  ),
        'templates'     => array( 'label' => 'Templates',      'icon' => '📐', 'b2b_only' => true  ),
    );

    public static function init() {
        add_shortcode( 'pcs_b2b_portal', array( __CLASS__, 'render_portal' ) );
        add_action( 'wp_enqueue_scripts',    array( __CLASS__, 'enqueue_assets' ) );
        /* Admin helper: create the portal page automatically */
        add_action( 'admin_init',            array( __CLASS__, 'maybe_create_portal_page' ) );
        /* Register plugin-based page template (works with any theme) */
        add_filter( 'theme_page_templates',  array( __CLASS__, 'register_template' ) );
        add_filter( 'template_include',      array( __CLASS__, 'load_template' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Plugin-based page template — works with ANY theme                  */
    /* ---------------------------------------------------------------- */
    public static function register_template( $templates ) {
        $templates['page-b2b-portal-plugin.php'] = 'B2B Portal (Full Width)';
        return $templates;
    }

    public static function load_template( $template ) {
        global $post;
        if ( ! $post ) return $template;

        $page_template = get_post_meta( $post->ID, '_wp_page_template', true );

        if ( 'page-b2b-portal-plugin.php' === $page_template
             || 'page-b2b-portal.php' === $page_template ) {
            /* Use our plugin template if theme doesn't have it */
            $plugin_template = PCS_PLUGIN_DIR . 'frontend/portal-template.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /* ---------------------------------------------------------------- */
    /* Enqueue assets on portal page only                                 */
    /* ---------------------------------------------------------------- */
    public static function enqueue_assets() {
        if ( ! self::is_portal_page() ) return;

        wp_enqueue_style(
            'pcs-portal',
            PCS_PLUGIN_URL . 'admin/css/pcs-frontend.css',
            array(),
            PCS_VERSION
        );
        wp_enqueue_style(
            'pcs-portal-extra',
            PCS_PLUGIN_URL . 'admin/css/pcs-portal-extra.css',
            array( 'pcs-portal' ),
            PCS_VERSION
        );
        wp_enqueue_style(
            'pcs-orders',
            PCS_PLUGIN_URL . 'admin/css/pcs-orders.css',
            array( 'pcs-portal-extra' ),
            PCS_VERSION
        );
        wp_enqueue_script(
            'pcs-portal',
            PCS_PLUGIN_URL . 'admin/js/pcs-frontend.js',
            array( 'jquery' ),
            PCS_VERSION,
            true
        );
        wp_localize_script( 'pcs-portal', 'PCS_FE', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'pcs_ajax' ),
            'user_id'  => get_current_user_id(),
            'tier'     => PCS_DB::get_customer_tier( get_current_user_id() ),
            'tiers'    => PCS_DB::get_tiers_from_rules(),
        ) );
    }

    private static function is_portal_page() {
        $page_id = (int) get_option( 'pcs_portal_page_id' );
        return $page_id && is_page( $page_id );
    }

    /* ---------------------------------------------------------------- */
    /* Shortcode handler                                                  */
    /* ---------------------------------------------------------------- */
    public static function render_portal( $atts ) {
        /* Must be logged in */
        if ( ! is_user_logged_in() ) {
            return '<div style="text-align:center;padding:40px 20px;">'
                 . '<p>Please <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to access your B2B portal.</p>'
                 . '</div>';
        }

        $user_id = get_current_user_id();
        $tier    = PCS_DB::get_customer_tier( $user_id );
        $is_b2b  = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );
        $store   = PCS_DB::get_store_by_user( $user_id );
        $tiers   = PCS_DB::get_tiers_from_rules();

        /* Current section — allow hyphenated slugs like order-detail */
        $raw_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
        $raw_section = preg_replace( '/[^a-z0-9\-]/', '', strtolower( $raw_section ) );
        $self_register = get_option( 'pcs_b2b_self_register', '1' ) !== '0';
        $current       = $raw_section ?: ( $is_b2b ? 'overview' : ( $self_register ? 'apply' : 'overview' ) );

        /* Redirect B2C to apply (or overview if self-registration disabled) */
        if ( ! $is_b2b && in_array( $current, array( 'store', 'team', 'approvals', 'apply' ), true ) ) {
            $current = $self_register ? 'apply' : 'overview';
        }

        ob_start();
        include PCS_PLUGIN_DIR . 'frontend/portal/portal.php';
        return ob_get_clean();
    }

    /* ---------------------------------------------------------------- */
    /* Auto-create portal page in WP admin (runs once)                   */
    /* ---------------------------------------------------------------- */
    public static function maybe_create_portal_page() {
        if ( get_option( 'pcs_portal_page_id' ) ) return;

        $page_id = wp_insert_post( array(
            'post_title'   => 'B2B Portal',
            'post_content' => '[pcs_b2b_portal]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
        ) );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( 'pcs_portal_page_id', $page_id );
            /* Use plugin-based template (works with any theme) */
            update_post_meta( $page_id, '_wp_page_template', 'page-b2b-portal-plugin.php' );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Helper: portal URL with section                                    */
    /* ---------------------------------------------------------------- */
    public static function url( $section = 'overview' ) {
        $page_id = (int) get_option( 'pcs_portal_page_id' );
        $base    = $page_id ? get_permalink( $page_id ) : home_url( '/b2b-portal/' );
        return add_query_arg( 'section', $section, $base );
    }
}
