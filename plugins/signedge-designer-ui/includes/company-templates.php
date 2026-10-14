<?php
/**
 * Designer: company template toolbar (#sg-tpl-bar). window.SG_TPL is printed only for logged-in members
 * of an approved company (printcart-b2bstore); the script itself lives in assets/js/company-library.js.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Priority 4: right after window.SG_IZ (3), before the scripts load.
add_action( 'nbd_extra_js', 'sg_dui_tpl_globals', 4 );
function sg_dui_tpl_globals() {
    if ( ! function_exists( 'sg_dui_iz_store_id' ) || ! class_exists( 'PCS_Company_Templates' ) ) {
        return;
    }
    $store_id = sg_dui_iz_store_id();
    if ( ! $store_id ) {
        return;
    }
    echo '<script id="sg-tpl-data">window.SG_TPL=' . wp_json_encode( array(
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'pcs_ajax' ),
        'storeId'    => $store_id,
        'hidePublic' => PCS_Company_Templates::hide_public( $store_id ),
    ) ) . ';</script>' . "\n";
}

// Hide the public template UI as early as possible (the JS adds the body class again once it runs).
add_filter( 'body_class', 'sg_dui_tpl_body_class' );
function sg_dui_tpl_body_class( $classes ) {
    if ( function_exists( 'sg_dui_iz_store_id' ) && class_exists( 'PCS_Company_Templates' ) ) {
        $store_id = sg_dui_iz_store_id();
        if ( $store_id && PCS_Company_Templates::hide_public( $store_id ) ) {
            $classes[] = 'sg-hide-public-templates';
        }
    }
    return $classes;
}
