<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCS_Admin {

    public static function init() {
        add_action( 'admin_menu',              array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts',   array( __CLASS__, 'enqueue_assets' ) );
        /* Sidebar menu CSS — must load on ALL admin pages */
        add_action( 'admin_head',              array( __CLASS__, 'menu_sidebar_css' ) );
    }

    public static function menu_sidebar_css() {
        ?>
<style>
#toplevel_page_printcart-stores .wp-submenu li>a{display:flex!important;align-items:center!important;gap:6px;padding:5px 10px 5px 14px!important;line-height:1.6!important;}
#toplevel_page_printcart-stores .wp-submenu li>a:hover,
#toplevel_page_printcart-stores .wp-submenu li.current>a{color:#fff!important;background:rgba(255,255,255,.15)!important;border-radius:4px;}
#adminmenu #toplevel_page_printcart-stores .awaiting-mod,
#adminmenu #toplevel_page_printcart-stores .wp-submenu .awaiting-mod{display:inline-flex!important;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:9999px;font-size:10px;font-weight:700;line-height:1;background:#ca4a1f;color:#fff;margin-left:4px;vertical-align:middle;}
#adminmenu #toplevel_page_printcart-stores .awaiting-mod .pending-count{font-size:10px;font-weight:700;color:#fff;}
</style>
        <?php
    }

    /* ---------------------------------------------------------------- */
    /* Count pending actions for badge                                    */
    /* ---------------------------------------------------------------- */
    public static function get_pending_count() {
        static $count = null;
        if ( null !== $count ) return $count;

        /* Pending B2B store applications */
        $pending_stores = PCS_DB::count_stores( array( 'status' => 'pending' ) );

        /* Pending (awaiting acceptance) invitations */
        $pending_invites = PCS_DB::count_invitations( array( 'status' => 'pending' ) );

        /* New (unread) quote requests */
        $pending_quotes = PCS_Quotes::count( array( 'status' => 'new_request' ) );

        $count = (int) $pending_stores + (int) $pending_invites + (int) $pending_quotes;
        return $count;
    }

    /* ---------------------------------------------------------------- */
    /* Admin menu — B2B tabs only                                          */
    /* ---------------------------------------------------------------- */
    public static function register_menu() {

        $pending_stores  = (int) PCS_DB::count_stores( array( 'status' => 'pending' ) );
        $pending_invites = (int) PCS_DB::count_invitations( array( 'status' => 'pending' ) );
        $pending_quotes  = (int) PCS_Quotes::count( array( 'status' => 'new_request' ) );
        $total_count     = $pending_stores + $pending_invites + $pending_quotes;
        $badge           = $total_count > 0
            ? ' <span class="awaiting-mod count-' . $total_count . '"><span class="pending-count">' . $total_count . '</span></span>'
            : '';

        add_menu_page(
            __( 'B2B & B2C', 'printcart-b2bstore' ),
            __( 'B2B & B2C', 'printcart-b2bstore' ) . $badge,
            'manage_options',
            'printcart-stores',
            array( __CLASS__, 'page_overview' ),
            'dashicons-groups',
            56
        );

        /* Only the B2B-specific pages live here.
           Products / Orders / Design Files / Quotes / Templates / Designer
           stay inside their respective plugins (CMSmart / WooCommerce). */
        /* Build submenu badge labels */
        $b = function( $label, $count ) {
            return $count > 0
                ? $label . ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>'
                : $label;
        };

        $submenus = array(
            array( 'Overview',                                          'printcart-stores',             'page_overview' ),
            array( $b( 'B2B Stores',    $pending_stores ),             'printcart-stores-b2b',         'page_b2b_stores' ),
            array( 'Customers',                                         'printcart-stores-customers',   'page_customers' ),
            array( $b( 'Invitations',   $pending_invites ),            'printcart-stores-invitations', 'page_invitations' ),
            array( $b( 'Custom Quotes', $pending_quotes ),             'printcart-quotes',             'page_quotes' ),
            array( 'Pricing Rules',                                     'printcart-stores-pricing',     'page_pricing' ),
            array( 'Settings',                                          'printcart-stores-settings',    'page_settings' ),
        );

        $first = true;
        foreach ( $submenus as $sm ) {
            add_submenu_page(
                'printcart-stores',
                $sm[0] . ' — B2B',
                $sm[0],
                'manage_options',
                $sm[1],
                array( __CLASS__, $sm[2] )
            );
            $first = false;
        }
    }

    /* ---------------------------------------------------------------- */
    /* Enqueue assets                                                      */
    /* ---------------------------------------------------------------- */
    public static function enqueue_assets( $hook ) {
        // Only on our plugin pages
        if ( false === strpos( $hook, 'printcart-stores' ) && false === strpos( $hook, 'printcart-quotes' ) ) {
            return;
        }

        wp_enqueue_style(
            'pcs-admin',
            PCS_PLUGIN_URL . 'admin/css/pcs-admin.css',
            array(),
            PCS_VERSION
        );

        wp_enqueue_script(
            'pcs-admin',
            PCS_PLUGIN_URL . 'admin/js/pcs-admin.js',
            array( 'jquery' ),
            PCS_VERSION,
            true
        );

        wp_localize_script( 'pcs-admin', 'PCS', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'pcs_ajax' ),
            'strings'  => array(
                'confirm_approve' => __( 'Approve this B2B application?', 'printcart-b2bstore' ),
                'confirm_reject'  => __( 'Reject this application?', 'printcart-b2bstore' ),
                'confirm_delete'  => __( 'Delete this item?', 'printcart-b2bstore' ),
                'saving'          => __( 'Saving…', 'printcart-b2bstore' ),
                'saved'           => __( 'Saved!', 'printcart-b2bstore' ),
                'error'           => __( 'An error occurred.', 'printcart-b2bstore' ),
            ),
        ) );
    }

    /* ---------------------------------------------------------------- */
    /* Helper: render a view file                                          */
    /* ---------------------------------------------------------------- */
    public static function render( $view ) {
        $file = PCS_PLUGIN_DIR . 'admin/views/' . $view . '.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html( ucwords( str_replace( '-', ' ', $view ) ) ) . '</h1>';
            echo '<p>' . esc_html__( 'Coming soon.', 'printcart-b2bstore' ) . '</p></div>';
        }
    }

    /* ---------------------------------------------------------------- */
    /* Page callbacks (B2B tabs only)                                      */
    /* ---------------------------------------------------------------- */
    public static function page_overview()    { self::render( 'overview' ); }
    public static function page_b2b_stores()  {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $id     = isset( $_GET['id'] )     ? absint( $_GET['id'] )                                : 0;
        if ( 'view' === $action && $id ) {
            self::render( 'b2b-store-detail' );
        } else {
            self::render( 'b2b-stores' );
        }
    }
    public static function page_customers() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $id     = isset( $_GET['id'] )     ? absint( $_GET['id'] )                                : 0;
        if ( 'view' === $action && $id ) {
            self::render( 'customer-detail' );
        } else {
            self::render( 'customers' );
        }
    }
    public static function page_invitations() { self::render( 'invitations' ); }
    public static function page_quotes()      {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $raw_id = isset( $_GET['id'] )     ? sanitize_text_field( wp_unslash( $_GET['id'] ) )     : '';
        if ( 'view' === $action && ( 'new' === $raw_id || absint( $raw_id ) > 0 ) ) {
            self::render( 'quote-detail' );
        } else {
            self::render( 'quotes' );
        }
    }
    public static function page_pricing()     { self::render( 'pricing' ); }
    public static function page_settings()    { self::render( 'settings' ); }
}
