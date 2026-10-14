<?php
/**
 * Plugin Name: Signedge Designer UI
 * Plugin URI:  #
 * Description: CSS/JS visual skin override for the pc-designer-web-to-print editor.
 *              Transforms the dark editor into a clean white layout matching the Signedge mockup.
 *              Non-destructive — does NOT modify the original plugin files.
 * Version:     1.0.0
 * Author:      Signedge / Netbase
 * Text Domain: signedge-designer-ui
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SG_DUI_VERSION', '1.10.132' );
define( 'SG_DUI_URL', plugin_dir_url( __FILE__ ) );

/**
 * Injection strategy:
 *
 * The designer page calls hooks in this order:
 *   1. nbd_extra_css  (line ~65 of nbdesigner-frontend-modern.php)
 *   2. pc_head        (line ~72) — outputs ALL CSS bundles including modern.css
 *   3. pc_footer -1   (line ~240) — NBO / Angular base JS
 *   4. nbd_extra_js   (line ~252) — our injection point
 *   5. pc_footer -2   (line ~258) — designer Angular app JS
 *
 * CRITICAL: if we inject via nbd_extra_css, our stylesheet loads BEFORE modern.css.
 * When both use !important with equal specificity, the LATER stylesheet wins.
 * So modern.css would override everything we set. Fix: inject CSS via nbd_extra_js
 * (priority 5) so it loads AFTER modern.css, making our !important rules win.
 */

// Priority 3: inline JSON with WooCommerce category data (before our CSS/JS load).
add_action( 'nbd_extra_js', 'sg_dui_inject_category_data', 3 );
function sg_dui_inject_category_data() {
    if ( ! function_exists( 'get_terms' ) ) return;

    // All product categories (flat list), excluding WooCommerce's default uncategorized.
    $exclude = array();
    $default_cat = (int) get_option( 'default_product_cat' );
    if ( $default_cat ) $exclude[] = $default_cat;

    $cats = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'exclude'    => $exclude,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
        'number'     => 30,
    ) );

    if ( is_wp_error( $cats ) || empty( $cats ) ) return;

    $data = array();
    foreach ( $cats as $cat ) {
        $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
        $image        = '';
        if ( $thumbnail_id ) {
            $img   = wp_get_attachment_image_src( $thumbnail_id, array( 48, 48 ) );
            $image = $img ? $img[0] : '';
        }
        $data[] = array(
            'id'     => $cat->term_id,
            'name'   => $cat->name,
            'slug'   => $cat->slug,
            'link'   => get_term_link( $cat ),
            'image'  => $image,
            'is_new' => false,
        );
    }

    // Determine which category IDs match the current product (for active highlight).
    $product_id      = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
    $current_cat_ids = array();
    if ( $product_id ) {
        $terms = get_the_terms( $product_id, 'product_cat' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $t ) {
                $current_cat_ids[] = $t->term_id;
                if ( $t->parent ) $current_cat_ids[] = $t->parent;
            }
            $current_cat_ids = array_unique( $current_cat_ids );
        }
    }

    // Product base price — inject directly so JS doesn't have to guess.
    $product_price = null;
    if ( $product_id && function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $raw = $product->get_price();
            $product_price = ( $raw !== '' && $raw !== false ) ? (float) $raw : null;
        }
    }

    // WooCommerce cart URL + item count for the cart icon button in the designer.
    $cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
    $cart_count = 0;
    if ( function_exists( 'WC' ) && WC()->cart ) {
        $cart_count = (int) WC()->cart->get_cart_contents_count();
    }

    // Auth state — login button or account link.
    $is_logged_in   = is_user_logged_in();
    $login_url      = wp_login_url( get_permalink() );
    $account_url    = function_exists( 'wc_get_account_endpoint_url' )
                        ? wc_get_account_endpoint_url( 'dashboard' )
                        : wp_login_url();
    $user_name      = '';
    if ( $is_logged_in ) {
        $user = wp_get_current_user();
        $user_name = $user->display_name ?: $user->user_login;
    }

    // B2B tier data — from printcart-b2bstore plugin if active.
    $b2b_tier       = 'b2c';
    $b2b_discount   = 0;
    $b2b_label      = 'Retail';
    $b2b_portal_url = '';
    if ( $is_logged_in ) {
        $uid = get_current_user_id();
        if ( class_exists( 'PCS_DB' ) ) {
            $b2b_tier = (string) PCS_DB::get_customer_tier( $uid );
        }
        if ( class_exists( 'PCS_Pricing' ) ) {
            $b2b_discount = (float) PCS_Pricing::get_effective_discount( $uid );
        }
        if ( class_exists( 'PCS_Customers' ) ) {
            $b2b_label = (string) PCS_Customers::tier_label( $b2b_tier );
        }
    }
    $portal_page_id = (int) get_option( 'pcs_portal_page_id' );
    if ( $portal_page_id ) {
        $b2b_portal_url = add_query_arg( 'section', 'apply', get_permalink( $portal_page_id ) );
    } elseif ( function_exists( 'wc_get_account_endpoint_url' ) ) {
        $b2b_portal_url = wc_get_account_endpoint_url( 'pcs-apply-b2b' );
    }

    echo '<script id="sg-dui-cat-data">'
        . 'window.sg_categories='      . wp_json_encode( $data )                              . ';'
        . 'window.sg_current_cat_ids=' . wp_json_encode( array_values( $current_cat_ids ) )   . ';'
        . 'window.sg_product_price='   . wp_json_encode( $product_price )                     . ';'
        . 'window.sg_cart_url='        . wp_json_encode( $cart_url )                          . ';'
        . 'window.sg_cart_count='      . wp_json_encode( $cart_count )                        . ';'
        . 'window.sg_is_logged_in='    . wp_json_encode( $is_logged_in )                      . ';'
        . 'window.sg_login_url='       . wp_json_encode( $login_url )                         . ';'
        . 'window.sg_account_url='     . wp_json_encode( $account_url )                       . ';'
        . 'window.sg_user_name='       . wp_json_encode( $user_name )                         . ';'
        . 'window.sg_b2b_tier='        . wp_json_encode( $b2b_tier )                          . ';'
        . 'window.sg_b2b_discount='    . wp_json_encode( $b2b_discount )                      . ';'
        . 'window.sg_b2b_label='       . wp_json_encode( $b2b_label )                         . ';'
        . 'window.sg_b2b_portal_url='  . wp_json_encode( $b2b_portal_url )                    . ';'
        . 'window.sg_b2b_nonce='       . wp_json_encode( wp_create_nonce( 'sg_b2b_apply' ) )  . ';'
        . 'window.sg_login_nonce='     . wp_json_encode( wp_create_nonce( 'sg_dui_login' ) )   . ';'
        . 'window.sg_ajax_url='        . wp_json_encode( admin_url( 'admin-ajax.php' ) )       . ';'
        . '</script>' . "\n";
}

// Priority 5: stylesheet (after modern.css so our !important rules win).
add_action( 'nbd_extra_js', 'sg_dui_inject_css', 5 );
function sg_dui_inject_css() {
    echo '<link rel="stylesheet" id="sg-dui-override-css" href="'
        . esc_url( SG_DUI_URL . 'assets/css/override.css' )
        . '?v=' . SG_DUI_VERSION . '" />' . "\n";
}

// Priority 10: main JS.
add_action( 'nbd_extra_js', 'sg_dui_inject_js', 10 );
function sg_dui_inject_js() {
    echo '<script id="sg-dui-override-js" src="'
        . esc_url( SG_DUI_URL . 'assets/js/override.js' )
        . '?v=' . SG_DUI_VERSION . '"></script>' . "\n";
}

/* -----------------------------------------------------------------------
   AJAX: inline login (guest users — nopriv)
----------------------------------------------------------------------- */
add_action( 'wp_ajax_nopriv_sg_dui_login', 'sg_dui_handle_login' );
function sg_dui_handle_login() {
    check_ajax_referer( 'sg_dui_login', '_nonce' );

    $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
    $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
    $remember = ! empty( $_POST['remember'] );

    if ( empty( $username ) || empty( $password ) ) {
        wp_send_json_error( array( 'message' => 'Please enter your username and password.' ) );
    }

    $user = wp_signon( array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ), is_ssl() );

    if ( is_wp_error( $user ) ) {
        wp_send_json_error( array( 'message' => 'Incorrect username or password. Please try again.' ) );
    }

    wp_send_json_success( array( 'message' => 'Logged in successfully.' ) );
}

/* -----------------------------------------------------------------------
   AJAX: B2B account application
   Endpoint: wp_ajax_sg_b2b_apply (logged-in users only)
----------------------------------------------------------------------- */
add_action( 'wp_ajax_sg_b2b_apply', 'sg_dui_handle_b2b_apply' );
function sg_dui_handle_b2b_apply() {
    check_ajax_referer( 'sg_b2b_apply', '_nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to apply.' ) );
    }

    if ( ! class_exists( 'PCS_DB' ) ) {
        wp_send_json_error( array( 'message' => 'B2B feature is not available.' ) );
    }

    $user_id = get_current_user_id();

    $existing = PCS_DB::get_store_by_user( $user_id );
    if ( $existing ) {
        wp_send_json_error( array( 'message' => 'You have already submitted a B2B application.' ) );
    }

    $company_name = sanitize_text_field( isset( $_POST['company_name'] ) ? $_POST['company_name'] : '' );
    if ( empty( $company_name ) ) {
        wp_send_json_error( array( 'message' => 'Company name is required.' ) );
    }

    $user = wp_get_current_user();
    $data = array(
        'user_id'        => $user_id,
        'company_name'   => $company_name,
        'company_slug'   => sanitize_title( $company_name ),
        'industry'       => sanitize_text_field( isset( $_POST['industry'] )       ? $_POST['industry']       : '' ),
        'contact_name'   => sanitize_text_field( isset( $_POST['contact_name'] )   ? $_POST['contact_name']   : $user->display_name ),
        'contact_email'  => sanitize_email(      isset( $_POST['contact_email'] )  ? $_POST['contact_email']  : $user->user_email ),
        'contact_phone'  => sanitize_text_field( isset( $_POST['contact_phone'] )  ? $_POST['contact_phone']  : '' ),
        'website'        => esc_url_raw(         isset( $_POST['website'] )        ? $_POST['website']        : '' ),
        'monthly_volume' => sanitize_text_field( isset( $_POST['monthly_volume'] ) ? $_POST['monthly_volume'] : '' ),
        'tier'           => get_option( 'pcs_b2b_default_tier', 'b2b_a' ),
        'status'         => 'pending',
    );

    $store_id = PCS_DB::save_store( $data );
    if ( ! $store_id ) {
        wp_send_json_error( array( 'message' => 'Something went wrong. Please try again.' ) );
    }

    PCS_DB::add_member( $store_id, $user_id, 'admin', 0 );

    if ( get_option( 'pcs_b2b_auto_approve' ) === '1' ) {
        $default_tier = get_option( 'pcs_b2b_default_tier', 'b2b_a' );
        if ( class_exists( 'PCS_B2B_Stores' ) ) {
            PCS_B2B_Stores::approve_store( $store_id, array( 'tier' => $default_tier, 'team_size' => 5 ) );
        }
        wp_send_json_success( array(
            'message'      => 'Your B2B account has been activated!',
            'auto_approved' => true,
        ) );
    }

    // Notify admin
    $admin_email = get_option( 'admin_email' );
    $site_name   = get_bloginfo( 'name' );
    wp_mail(
        $admin_email,
        sprintf( '[%s] New B2B application from %s', $site_name, $company_name ),
        sprintf(
            "New B2B application:\n\nCompany: %s\nContact: %s\nEmail: %s\nIndustry: %s\n\nReview: %s",
            $company_name,
            $data['contact_name'],
            $data['contact_email'],
            $data['industry'],
            admin_url( 'admin.php?page=printcart-stores-b2b-stores&status=pending' )
        )
    );

    wp_send_json_success( array(
        'message' => 'Your B2B application has been submitted! We will review and respond within 24 hours.',
    ) );
}
