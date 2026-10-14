<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Frontend: WooCommerce My Account integration.
 * Adds custom tabs/endpoints for B2B account management.
 */
class PCS_Frontend {

    /* Endpoint slugs */
    const EP_OVERVIEW   = 'pcs-overview';
    const EP_APPLY_B2B  = 'pcs-apply-b2b';
    const EP_B2B_STORE  = 'pcs-b2b-store';
    const EP_TEAM       = 'pcs-team';
    const EP_APPROVALS  = 'pcs-approvals';

    public static function init() {
        /* Register endpoints */
        add_action( 'init',                          array( __CLASS__, 'register_endpoints' ) );
        /* Add tabs to My Account menu */
        add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ) );
        /* Endpoint titles */
        add_filter( 'woocommerce_endpoint_' . self::EP_OVERVIEW  . '_title', function() { return 'My Account Overview'; } );
        add_filter( 'woocommerce_endpoint_' . self::EP_APPLY_B2B . '_title', function() { return 'Apply for B2B'; } );
        add_filter( 'woocommerce_endpoint_' . self::EP_B2B_STORE . '_title', function() { return 'B2B Store'; } );
        add_filter( 'woocommerce_endpoint_' . self::EP_TEAM      . '_title', function() { return 'Teams'; } );
        add_filter( 'woocommerce_endpoint_' . self::EP_APPROVALS . '_title', function() { return 'Approval Queue'; } );
        /* Content callbacks */
        add_action( 'woocommerce_account_' . self::EP_OVERVIEW  . '_endpoint', array( __CLASS__, 'page_overview' ) );
        add_action( 'woocommerce_account_' . self::EP_APPLY_B2B . '_endpoint', array( __CLASS__, 'page_apply_b2b' ) );
        add_action( 'woocommerce_account_' . self::EP_B2B_STORE . '_endpoint', array( __CLASS__, 'page_b2b_store' ) );
        add_action( 'woocommerce_account_' . self::EP_TEAM      . '_endpoint', array( __CLASS__, 'page_team' ) );
        add_action( 'woocommerce_account_' . self::EP_APPROVALS . '_endpoint', array( __CLASS__, 'page_approvals' ) );
        /* Enqueue assets */
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        /* Handle B2B application form submission */
        add_action( 'init', array( __CLASS__, 'handle_apply_b2b_submit' ) );
        /* Redirect My Account B2B tabs → B2B Portal page */
        add_action( 'template_redirect', array( __CLASS__, 'redirect_to_portal' ) );
        /* Override endpoint URLs in the My Account menu → direct to portal */
        add_filter( 'woocommerce_get_endpoint_url', array( __CLASS__, 'override_endpoint_url' ), 10, 2 );
        /* Flush rewrite on plugin load (run once) */
        add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush_rewrite' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Endpoints                                                          */
    /* ---------------------------------------------------------------- */
    public static function register_endpoints() {
        add_rewrite_endpoint( self::EP_OVERVIEW,   EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( self::EP_APPLY_B2B,  EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( self::EP_B2B_STORE,  EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( self::EP_TEAM,       EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( self::EP_APPROVALS,  EP_ROOT | EP_PAGES );
    }

    public static function maybe_flush_rewrite() {
        if ( get_option( 'pcs_flush_rewrite' ) !== PCS_VERSION ) {
            flush_rewrite_rules();
            update_option( 'pcs_flush_rewrite', PCS_VERSION );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Menu items                                                         */
    /* ---------------------------------------------------------------- */
    public static function add_menu_items( $items ) {
        $user_id      = get_current_user_id();
        $tier         = PCS_DB::get_customer_tier( $user_id );
        $is_b2b       = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );
        $store_status = '';
        if ( $user_id ) {
            $store = PCS_DB::get_store_by_user( $user_id );
            if ( $store ) $store_status = $store->status;
        }

        $new_items = array();

        /* Insert Overview first, right after Dashboard */
        foreach ( $items as $key => $label ) {
            $new_items[ $key ] = $label;
            if ( $key === 'dashboard' ) {
                $new_items[ self::EP_OVERVIEW ] = 'Overview';
            }
        }
        $items = $new_items;

        /* B2B-specific tabs */
        if ( $is_b2b ) {
            $items[ self::EP_B2B_STORE ] = '🏢 B2B Store';
        } else {
            $items[ self::EP_APPLY_B2B ] = $store_status === 'pending' ? '⭐ B2B Status' : '⭐ Apply for B2B';
        }

        return $items;
    }

    /* ---------------------------------------------------------------- */
    /* Assets                                                             */
    /* ---------------------------------------------------------------- */
    public static function enqueue_assets() {
        if ( ! is_account_page() ) return;

        wp_enqueue_style(
            'pcs-frontend',
            PCS_PLUGIN_URL . 'admin/css/pcs-frontend.css',
            array(),
            PCS_VERSION
        );
        wp_enqueue_script(
            'pcs-frontend',
            PCS_PLUGIN_URL . 'admin/js/pcs-frontend.js',
            array( 'jquery' ),
            PCS_VERSION,
            true
        );
        wp_localize_script( 'pcs-frontend', 'PCS_FE', array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'pcs_ajax' ),
            'user_id'    => get_current_user_id(),
            'tier'       => PCS_DB::get_customer_tier( get_current_user_id() ),
            'tiers'      => PCS_DB::get_tiers_from_rules(),
        ) );
    }

    /* ---------------------------------------------------------------- */
    /* Override endpoint URLs → point directly to B2B Portal page        */
    /* (so the link in the My Account menu goes straight to portal)       */
    /* ---------------------------------------------------------------- */
    public static function override_endpoint_url( $url, $endpoint ) {
        $portal_id = (int) get_option( 'pcs_portal_page_id' );
        if ( ! $portal_id ) return $url;

        $map = array(
            self::EP_B2B_STORE  => 'store',
            self::EP_APPLY_B2B  => 'apply',
            self::EP_TEAM       => 'team',
            self::EP_APPROVALS  => 'approvals',
            self::EP_OVERVIEW   => 'overview',
            /* Override standard WC orders endpoint → portal orders section */
            'orders'            => 'orders',
        );

        if ( isset( $map[ $endpoint ] ) ) {
            return add_query_arg( 'section', $map[ $endpoint ], get_permalink( $portal_id ) );
        }

        return $url;
    }

    /* ---------------------------------------------------------------- */
    /* Redirect My Account WC endpoints → B2B Portal                     */
    /* ---------------------------------------------------------------- */
    public static function redirect_to_portal() {
        if ( ! is_account_page() || ! is_user_logged_in() ) return;
        /* Allow bypassing portal redirect via ?pcs_noredirect=1 */
        if ( ! empty( $_GET['pcs_noredirect'] ) ) return;

        $portal_id = (int) get_option( 'pcs_portal_page_id' );
        if ( ! $portal_id ) return;

        $endpoint = WC()->query ? WC()->query->get_current_endpoint() : '';

        /* Auto-redirect B2B users from My Account dashboard → portal overview */
        if ( empty( $endpoint ) ) {
            $user_id = get_current_user_id();
            $tier    = PCS_DB::get_customer_tier( $user_id );
            $tiers   = PCS_DB::get_tiers_from_rules();
            $is_b2b  = isset( $tiers[ $tier ] ) && (float) $tiers[ $tier ]['discount'] > 0;

            /* Also redirect if user is member of an approved B2B store */
            if ( ! $is_b2b ) {
                global $wpdb;
                $is_b2b = (bool) $wpdb->get_var( $wpdb->prepare(
                    "SELECT m.id FROM {$wpdb->prefix}pcs_b2b_members m
                     INNER JOIN {$wpdb->prefix}pcs_b2b_stores s ON s.id = m.store_id
                     WHERE m.user_id = %d AND s.status IN ('active','approved') LIMIT 1",
                    $user_id
                ) );
            }

            if ( $is_b2b ) {
                wp_safe_redirect( PCS_B2B_Portal::url( 'overview' ) );
                exit;
            }
        }

        $redirect_map = array(
            self::EP_B2B_STORE  => 'store',
            self::EP_APPLY_B2B  => 'apply',
            self::EP_TEAM       => 'team',
            self::EP_APPROVALS  => 'approvals',
            self::EP_OVERVIEW   => 'overview',
            'orders'            => 'orders',
        );

        if ( isset( $redirect_map[ $endpoint ] ) ) {
            wp_safe_redirect( add_query_arg( 'section', $redirect_map[ $endpoint ], get_permalink( $portal_id ) ) );
            exit;
        }
    }

    /* ---------------------------------------------------------------- */
    /* Handle B2B application form                                        */
    /* ---------------------------------------------------------------- */
    public static function handle_apply_b2b_submit() {
        if ( ! isset( $_POST['pcs_apply_b2b_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['pcs_apply_b2b_nonce'], 'pcs_apply_b2b' ) ) {
            wc_add_notice( 'Security check failed. Please try again.', 'error' );
            return;
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) return;

        /* Prevent double-application */
        $existing = PCS_DB::get_store_by_user( $user_id );
        if ( $existing ) {
            wc_add_notice( 'You have already submitted a B2B application.', 'notice' );
            return;
        }

        $user = wp_get_current_user();
        $data = array(
            'user_id'        => $user_id,
            'company_name'   => sanitize_text_field( $_POST['company_name'] ?? '' ),
            'industry'       => sanitize_text_field( $_POST['industry']     ?? '' ),
            'contact_name'   => sanitize_text_field( $_POST['contact_name'] ?? $user->display_name ),
            'contact_email'  => sanitize_email( $_POST['contact_email']     ?? $user->user_email ),
            'contact_phone'  => sanitize_text_field( $_POST['contact_phone'] ?? '' ),
            'website'        => esc_url_raw( $_POST['website']              ?? '' ),
            'monthly_volume' => sanitize_text_field( $_POST['monthly_volume'] ?? '' ),
            'about'          => sanitize_textarea_field( $_POST['about']    ?? '' ),
            'tier'           => get_option( 'pcs_b2b_default_tier', 'b2b_a' ),
            'status'         => 'pending',
        );

        if ( empty( $data['company_name'] ) ) {
            wc_add_notice( 'Company name is required.', 'error' );
            return;
        }

        $store_id = PCS_DB::save_store( $data );

        if ( $store_id ) {
            /* Also add as owner member */
            PCS_DB::add_member( $store_id, $user_id, 'admin', 0 );

            /* Auto-approve if enabled in settings */
            if ( get_option( 'pcs_b2b_auto_approve' ) === '1' ) {
                $default_tier = get_option( 'pcs_b2b_default_tier', 'b2b_a' );
                PCS_B2B_Stores::approve_store( $store_id, array(
                    'tier'      => $default_tier,
                    'team_size' => 5,
                ) );
                wc_add_notice( 'Your B2B account has been automatically activated!', 'success' );
                wp_safe_redirect( PCS_B2B_Portal::url( 'overview' ) );
                exit;
            }

            /* Notify admin of new B2B application */
            self::notify_admin_new_application( $data, $store_id );

            wc_add_notice( 'Your B2B application has been submitted! We will review and respond within 24 hours.', 'success' );
            wp_safe_redirect( wc_get_account_endpoint_url( self::EP_APPLY_B2B ) );
            exit;
        } else {
            wc_add_notice( 'Something went wrong. Please try again.', 'error' );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Notify admin when a new B2B application is submitted              */
    /* ---------------------------------------------------------------- */
    private static function notify_admin_new_application( $data, $store_id ) {
        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        $review_url  = admin_url( 'admin.php?page=printcart-stores-b2b-stores&status=pending' );

        $subject = sprintf( '[%s] New B2B application from %s', $site_name, $data['company_name'] );

        $body = '<!DOCTYPE html><html><body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;background:#f4f6f8;padding:32px 16px;margin:0;">'
              . '<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);margin:0 auto;">'
              . '<tr><td style="background:linear-gradient(135deg,#1d2327,#2563eb);padding:28px 32px;">'
              . '<h2 style="margin:0;color:#fff;font-size:18px;">📋 New B2B Application</h2>'
              . '<p style="margin:4px 0 0;color:rgba(255,255,255,.7);font-size:13px;">' . esc_html( $site_name ) . '</p>'
              . '</td></tr>'
              . '<tr><td style="padding:28px 32px;">'
              . '<table width="100%" cellpadding="0" cellspacing="0">'
              . self::email_row( 'Company', $data['company_name'] )
              . self::email_row( 'Industry', $data['industry'] )
              . self::email_row( 'Contact', $data['contact_name'] . ' &lt;' . $data['contact_email'] . '&gt;' )
              . ( $data['contact_phone'] ? self::email_row( 'Phone', $data['contact_phone'] ) : '' )
              . ( $data['monthly_volume'] ? self::email_row( 'Est. monthly volume', $data['monthly_volume'] ) : '' )
              . ( $data['about'] ? self::email_row( 'About', nl2br( esc_html( $data['about'] ) ) ) : '' )
              . '</table>'
              . '<div style="margin-top:24px;text-align:center;">'
              . '<a href="' . esc_url( $review_url ) . '" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:700;">Review application →</a>'
              . '</div>'
              . '</td></tr>'
              . '<tr><td style="padding:16px 32px;border-top:1px solid #f3f4f6;background:#f9fafb;text-align:center;">'
              . '<p style="margin:0;font-size:12px;color:#9ca3af;">This notification was sent automatically by ' . esc_html( $site_name ) . '</p>'
              . '</td></tr>'
              . '</table></body></html>';

        wp_mail( $admin_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    private static function email_row( $label, $value ) {
        return '<tr>'
             . '<td style="padding:7px 0;font-size:13px;color:#6b7280;width:160px;vertical-align:top;">' . esc_html( $label ) . '</td>'
             . '<td style="padding:7px 0;font-size:13px;color:#111827;font-weight:600;">' . $value . '</td>'
             . '</tr>';
    }

    /* ---------------------------------------------------------------- */
    /* Page: Overview                                                     */
    /* ---------------------------------------------------------------- */
    public static function page_overview() {
        $user_id  = get_current_user_id();
        $tier     = PCS_DB::get_customer_tier( $user_id );
        $tiers    = PCS_DB::get_tiers_from_rules();
        $is_b2b   = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );
        $tier_def = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => 'B2C', 'discount' => 0 );
        $style    = PCS_DB::get_tier_style( $tier );
        $user     = wp_get_current_user();

        /* WC stats */
        $order_count = wc_get_customer_order_count( $user_id );
        $total_spent = (float) wc_get_customer_total_spent( $user_id );

        /* Recent orders */
        $recent_orders = wc_get_orders( array(
            'customer' => $user_id,
            'limit'    => 5,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ) );

        self::render_view( 'account-overview', compact( 'user', 'tier', 'tier_def', 'style', 'is_b2b', 'tiers', 'order_count', 'total_spent', 'recent_orders' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Page: Apply for B2B                                                */
    /* ---------------------------------------------------------------- */
    public static function page_apply_b2b() {
        $user_id  = get_current_user_id();
        $tier     = PCS_DB::get_customer_tier( $user_id );
        $tiers    = PCS_DB::get_tiers_from_rules();
        $is_b2b   = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );
        $store    = PCS_DB::get_store_by_user( $user_id );
        $user     = wp_get_current_user();
        $total_spent = (float) wc_get_customer_total_spent( $user_id );

        self::render_view( 'account-apply-b2b', compact( 'user', 'tier', 'tiers', 'is_b2b', 'store', 'total_spent' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Page: B2B Store                                                    */
    /* ---------------------------------------------------------------- */
    public static function page_b2b_store() {
        $user_id = get_current_user_id();
        $store   = PCS_DB::get_store_by_user( $user_id );
        $tier    = PCS_DB::get_customer_tier( $user_id );
        $tiers   = PCS_DB::get_tiers_from_rules();

        /* Enqueue portal CSS for this page */
        wp_enqueue_style( 'pcs-portal-extra', PCS_PLUGIN_URL . 'admin/css/pcs-portal-extra.css', array(), PCS_VERSION );

        if ( ! $store ) {
            wc_add_notice( 'You do not have an active B2B store.', 'notice' );
            return;
        }
        $members  = PCS_DB::get_members( $store->id );
        $user     = wp_get_current_user();
        $user_id  = get_current_user_id();
        $is_b2b   = in_array( $tier, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ), true );
        $current  = 'store';

        /* Render portal section directly (no sidebar — WC My Account already has a sidebar) */
        $file = PCS_PLUGIN_DIR . 'frontend/portal/sections/store.php';
        if ( file_exists( $file ) ) {
            /* Pass all needed vars */
            $tier_def = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => $tier, 'discount' => 0 );
            include $file;
        }
    }

    /* ---------------------------------------------------------------- */
    /* Page: Team                                                         */
    /* ---------------------------------------------------------------- */
    public static function page_team() {
        $user_id = get_current_user_id();
        $store   = PCS_DB::get_store_by_user( $user_id );
        if ( ! $store ) { echo '<p>No B2B store found.</p>'; return; }
        $members = PCS_DB::get_members( $store->id );
        self::render_view( 'account-team', compact( 'store', 'members' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Page: Approvals                                                    */
    /* ---------------------------------------------------------------- */
    public static function page_approvals() {
        $user_id = get_current_user_id();
        $store   = PCS_DB::get_store_by_user( $user_id );
        if ( ! $store ) { echo '<p>No B2B store found.</p>'; return; }
        self::render_view( 'account-approvals', compact( 'store' ) );
    }

    /* ---------------------------------------------------------------- */
    /* Helper: render a frontend view file with variables                 */
    /* ---------------------------------------------------------------- */
    public static function render_view( $name, $vars = array() ) {
        $file = PCS_PLUGIN_DIR . 'frontend/views/' . $name . '.php';
        if ( ! file_exists( $file ) ) {
            echo '<p style="color:red;">View not found: ' . esc_html( $name ) . '</p>';
            return;
        }
        if ( $vars ) extract( $vars );
        include $file;
    }
}
