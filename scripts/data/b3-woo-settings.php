<?php
/**
 * B3 data changes: customer account flow + status vocabulary (idempotent, backup of every touched value).
 *   scripts/data/run.sh b3-woo-settings.php [--dry-run|--apply|--restore=<backup.json>]
 *
 * Flow chosen by the customer: visitors may browse, upload and design WITHOUT logging in; checkout needs an account:
 *   - guest checkout OFF; account creation ON in My Account and at checkout; login reminder ON;
 *   - the customer chooses the password at registration (no generated password mail);
 *   - WordPress registration ON with default role customer (needed by the B2B invitation link).
 * B2B store status vocabulary: 'approved' (legacy) -> 'active'.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b3-woo-settings' );
global $wpdb;

GP_Data::say( '-- WooCommerce account flow' );
GP_Data::option_set( 'woocommerce_enable_guest_checkout', 'no' );
GP_Data::option_set( 'woocommerce_enable_myaccount_registration', 'yes' );
GP_Data::option_set( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
GP_Data::option_set( 'woocommerce_enable_checkout_login_reminder', 'yes' );
GP_Data::option_set( 'woocommerce_registration_generate_username', 'yes' );
GP_Data::option_set( 'woocommerce_registration_generate_password', 'no' );
GP_Data::option_set( 'users_can_register', '1' );
GP_Data::option_set( 'default_role', 'customer' );

GP_Data::say( '-- B2B store status vocabulary' );
$T = $wpdb->prefix . 'pcs_b2b_stores';
$rows = $wpdb->get_results( "SELECT id, status FROM $T WHERE status = 'approved'", ARRAY_A );
foreach ( $rows as $r ) {
	GP_Data::row_update( $T, array( 'id' => $r['id'] ), array( 'status' => 'active' ), 'approved -> active' );
}
if ( ! $rows ) { GP_Data::say( '  no store with status approved' ); }
GP_Data::finish();
