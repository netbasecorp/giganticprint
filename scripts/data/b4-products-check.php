<?php
/**
 * B4 private-products checks.
 *   scripts/data/run.sh b4-products-check.php [--dry-run|--apply]
 *
 * --apply only (creates and ALWAYS removes temporary rows): store 'GP Products Test' owned by
 * gp_test_b2b with gp_test_b2c as member, product 1263 assigned to it; asserts visibility for
 * owner / member / gp_test_reseller / guest / admin (user 1), WP_Query results and the
 * add-to-cart validation, then unassigns and asserts the product is public again.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b4-products-check' );
global $wpdb;

$GLOBALS['gp_b4_fail'] = 0;

function gp_b4_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b4_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b4_in_query( $uid, $pid ) {
	wp_set_current_user( $uid );
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	return in_array( $pid, array_map( 'intval', $q->posts ), true );
}

$pid      = 1263;
$store_id = 0;
$had_opt  = ( null !== GP_Data::raw_option( 'pcs_store_settings' ) );
$opt_old  = $had_opt ? GP_Data::raw_option( 'pcs_store_settings' ) : null;

try {
	if ( ! GP_Data::writing() ) {
		GP_Data::say( '  SKIP (needs --apply: creates and removes a temporary store)' );
	} elseif ( ! class_exists( 'PCS_Private_Products' ) ) {
		gp_b4_assert( 'PCS_Private_Products loaded', false );
	} else {
		$owner    = get_user_by( 'login', 'gp_test_b2b' );
		$member   = get_user_by( 'login', 'gp_test_staff' );
		$reseller = get_user_by( 'login', 'gp_test_reseller' );
		gp_b4_assert( 'test users exist', $owner && $member && $reseller );
		gp_b4_assert( "product $pid exists", (bool) get_post( $pid ) );

		if ( $owner && $member && $reseller && get_post( $pid ) && ! PCS_DB::get_store_by_user( $owner->ID ) ) {
			PCS_Private_Products::maybe_create_table();
			$store_id = (int) PCS_DB::save_store( array(
				'user_id'       => $owner->ID,
				'company_name'  => 'GP Products Test',
				'company_slug'  => 'gp-products-test-temp',
				'tier'          => 'b2b_a',
				'status'        => 'active',
				'contact_name'  => 'GP Test',
				'contact_email' => $owner->user_email,
			) );
			gp_b4_assert( 'temporary store created', $store_id > 0 );

			if ( $store_id > 0 ) {
				PCS_DB::add_member( $store_id, $member->ID, 'member', 0 );
				gp_b4_assert( 'assign() ok', PCS_Private_Products::assign( $store_id, $pid ) );
				gp_b4_assert( 'get_for_store contains product', in_array( $pid, PCS_Private_Products::get_for_store( $store_id ), true ) );
				gp_b4_assert( 'get_stores_for_product contains store', in_array( $store_id, PCS_Private_Products::get_stores_for_product( $pid ), true ) );
				gp_b4_assert( 'private_product_ids contains product', in_array( $pid, PCS_Private_Products::private_product_ids(), true ) );

				PCS_Private_Products::set_store_setting( $store_id, 'hide_public_templates', 1 );
				gp_b4_assert( 'store setting round-trips', 1 === (int) PCS_Private_Products::get_store_setting( $store_id, 'hide_public_templates', 0 ) );

				$users = array(
					'owner'    => array( $owner->ID, true ),
					'member'   => array( $member->ID, true ),
					'reseller' => array( $reseller->ID, false ),
					'guest'    => array( 0, false ),
					'admin'    => array( 1, true ),
				);
				GP_Data::say( '-- private: is_visible / WP_Query / add-to-cart' );
				foreach ( $users as $name => $u ) {
					wp_set_current_user( $u[0] );
					gp_b4_assert( "is_visible($pid) $name = " . var_export( $u[1], true ), $u[1] === PCS_Private_Products::is_visible( $pid, $u[0] ) );
					gp_b4_assert( "WP_Query $name " . ( $u[1] ? 'contains' : 'does not contain' ) . " $pid", $u[1] === gp_b4_in_query( $u[0], $pid ) );
				}
				wp_set_current_user( $reseller->ID );
				gp_b4_assert( 'add-to-cart refused for stranger', false === apply_filters( 'woocommerce_add_to_cart_validation', true, $pid, 1 ) );
				wc_clear_notices();
				wp_set_current_user( $member->ID );
				gp_b4_assert( 'add-to-cart allowed for member', true === apply_filters( 'woocommerce_add_to_cart_validation', true, $pid, 1 ) );

				GP_Data::say( '-- after unassign: public again' );
				PCS_Private_Products::unassign( $store_id, $pid );
				gp_b4_assert( 'private_product_ids no longer contains product', ! in_array( $pid, PCS_Private_Products::private_product_ids(), true ) );
				gp_b4_assert( 'is_visible for guest again true', true === PCS_Private_Products::is_visible( $pid, 0 ) );
				gp_b4_assert( 'WP_Query guest contains product again', gp_b4_in_query( 0, $pid ) );
			}
		} else {
			gp_b4_assert( 'preconditions (gp_test_b2b has no store yet)', false );
		}
	}
} finally {
	GP_Data::say( '-- cleanup' );
	wp_set_current_user( 0 );
	if ( $store_id > 0 ) {
		PCS_Private_Products::set_for_store( $store_id, array() );
		PCS_DB::delete_store( $store_id );
		$wpdb->delete( PCS_Private_Products::table(), array( 'store_id' => $store_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'pcs_b2b_members', array( 'store_id' => $store_id ), array( '%d' ) );
	}
	// restore the settings option exactly as it was
	if ( $had_opt ) {
		$wpdb->update( $wpdb->options, array( 'option_value' => $opt_old ), array( 'option_name' => 'pcs_store_settings' ) );
	} else {
		delete_option( 'pcs_store_settings' );
	}
	wp_cache_delete( 'pcs_store_settings', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	delete_transient( 'pcs_private_product_ids' );
	GP_Data::say( '  temporary store/assignments/settings removed' );
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b4_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b4_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b4_fail'] ? 1 : 0 );
