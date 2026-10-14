<?php
/**
 * Designer: "Your company images" block in the Photos tab (Image Zone of the visitor's B2B company).
 * Everything is printed only for logged-in members of an approved company (printcart-b2bstore).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Store id of the visitor's first approved company, 0 for guests / non-members. */
function sg_dui_iz_store_id() {
    if ( ! is_user_logged_in() || ! class_exists( 'PCS_Company_Images' ) ) {
        return 0;
    }
    $ids = PCS_Company_Images::user_store_ids( get_current_user_id() );
    return $ids ? (int) $ids[0] : 0;
}

// Priority 3: window.SG_IZ (before our scripts load).
add_action( 'nbd_extra_js', 'sg_dui_iz_globals', 3 );
function sg_dui_iz_globals() {
    $store_id = sg_dui_iz_store_id();
    if ( ! $store_id ) {
        return;
    }
    echo '<script id="sg-iz-data">window.SG_IZ=' . wp_json_encode( array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'pcs_ajax' ),
        'storeId' => $store_id,
    ) ) . ';</script>' . "\n";
}

add_action( 'nbd_extra_js', 'sg_dui_iz_css', 7 );
function sg_dui_iz_css() {
    if ( ! sg_dui_iz_store_id() ) {
        return;
    }
    echo '<link rel="stylesheet" id="sg-iz-css" href="'
        . esc_url( SG_DUI_URL . 'assets/css/company-library.css' )
        . '?v=' . esc_attr( sg_dui_asset_ver( 'assets/css/company-library.css' ) ) . '" />' . "\n";
}

add_action( 'nbd_extra_js', 'sg_dui_iz_js', 12 );
function sg_dui_iz_js() {
    if ( ! sg_dui_iz_store_id() ) {
        return;
    }
    echo '<script id="sg-iz-js" src="'
        . esc_url( SG_DUI_URL . 'assets/js/company-library.js' )
        . '?v=' . esc_attr( sg_dui_asset_ver( 'assets/js/company-library.js' ) ) . '"></script>' . "\n";
}
