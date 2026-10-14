<?php
/**
 * Plugin Name: Printcart B2B & B2C Store
 * Plugin URI:  https://cmsmart.net/
 * Description: B2B Corporate store management, customer tiers, pricing rules, team workspaces, and invitations — works alongside CMSmart & WooCommerce.
 * Version:     1.0.0
 * Author:      NetbaseTeam
 * Author URI:  https://cmsmart.net/
 * License:     GPLv2 or later
 * Text Domain: printcart-b2bstore
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PCS_VERSION',          '1.0.0' );
define( 'PCS_PLUGIN_FILE',      __FILE__ );
define( 'PCS_PLUGIN_DIR',       plugin_dir_path( __FILE__ ) );
define( 'PCS_PLUGIN_URL',       plugin_dir_url( __FILE__ ) );
define( 'PCS_PLUGIN_BASENAME',  plugin_basename( __FILE__ ) );

/* ------------------------------------------------------------------ */
/* Activation / Deactivation                                           */
/* ------------------------------------------------------------------ */
require_once PCS_PLUGIN_DIR . 'includes/class-activator.php';
register_activation_hook( __FILE__,   array( 'PCS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PCS_Activator', 'deactivate' ) );

/* ------------------------------------------------------------------ */
/* Autoload                                                            */
/* ------------------------------------------------------------------ */
function pcs_autoload() {
    $files = array(
        'includes/class-db.php',
        'includes/class-b2b-stores.php',
        'includes/class-customers.php',
        'includes/class-pricing.php',
        'includes/class-invitations.php',
        'includes/class-team-invitations.php',
        'includes/class-quotes.php',
        'includes/class-reorder.php',
        'includes/class-ajax.php',
        'includes/class-admin.php',
        'includes/class-frontend.php',
        'includes/class-b2b-portal.php',
    );
    foreach ( $files as $file ) {
        require_once PCS_PLUGIN_DIR . $file;
    }
}

/* ------------------------------------------------------------------ */
/* Init                                                                */
/* ------------------------------------------------------------------ */
function pcs_init() {
    pcs_autoload();

    PCS_Admin::init();
    PCS_B2B_Stores::init();
    PCS_Customers::init();
    PCS_Pricing::init();
    PCS_Invitations::init();
    PCS_Team_Invitations::init();
    PCS_Quotes::init();
    PCS_Ajax::init();
    add_action( 'admin_init', array( 'PCS_Activator', 'maybe_run_migrations' ) );

    /* Frontend — only when WooCommerce is active */
    if ( class_exists( 'WooCommerce' ) ) {
        PCS_Frontend::init();
        PCS_B2B_Portal::init();
        PCS_Reorder::init();
    }
}
add_action( 'plugins_loaded', 'pcs_init' );
