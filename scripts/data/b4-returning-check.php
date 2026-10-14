<?php
/**
 * B4 check of the automatic promotion to the 'returning' tier (read-only unless --apply).
 *   scripts/data/run.sh b4-returning-check.php [--dry-run|--apply]
 *
 * --apply only (creates and ALWAYS removes throw-away orders, restores gp_test_b2c's tier + tier history):
 *   - first order of gp_test_b2c reaches 'processing'  -> tier stays b2c (the order itself is not "other")
 *   - second order reaches 'processing'                -> tier becomes 'returning', history row written
 *   - PCS_Pricing::get_effective_discount() is 5 for the promoted user
 *   - promoted user: running the hook again is a no-op (no extra history row)
 *   - a user in tier b2b_a or reseller is never changed by the hook
 *   - a guest order (customer id 0) does nothing
 * gp_test_b2c may already own qualifying orders: the threshold filter is raised by that number so the
 * assertions stay meaningful.
 *
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b4-returning-check' );
global $wpdb;

$GLOBALS['gp_b4r_fail'] = 0;

function gp_b4r_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b4r_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b4r_hist_count( $user_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_tier_history WHERE user_id = %d", $user_id ) );
}

$user       = null;
$order_ids  = array();
$snap       = array(); // meta_key => array( had, raw )
$hist_t     = $wpdb->prefix . 'pcs_tier_history';
$hist_max   = 0;
$min_filter = null;

try {

	if ( ! GP_Data::writing() ) {
		GP_Data::say( '-- SKIP (needs --apply: creates and removes throw-away orders)' );
	} elseif ( ! class_exists( 'PCS_Customers' ) || ! class_exists( 'PCS_Pricing' ) || ! function_exists( 'wc_create_order' ) ) {
		gp_b4r_assert( 'printcart-b2bstore + WooCommerce loaded', false );
	} else {
		$user = get_user_by( 'login', 'gp_test_b2c' );
		gp_b4r_assert( 'gp_test_b2c exists', (bool) $user );
	}

	if ( $user ) {
		$uid = (int) $user->ID;

		// snapshot everything the orders / hook may touch on the user
		foreach ( array( '_pcs_tier', '_money_spent', '_order_count' ) as $k ) {
			$had = metadata_exists( 'user', $uid, $k );
			$snap[ $k ] = array( $had, $had ? get_user_meta( $uid, $k, true ) : null );
		}
		$hist_max = (int) $wpdb->get_var( "SELECT COALESCE(MAX(id),0) FROM $hist_t" );

		// start from plain b2c
		delete_user_meta( $uid, '_pcs_tier' );
		gp_b4r_assert( 'start tier is b2c', 'b2c' === PCS_DB::get_customer_tier( $uid ) );

		// orders that already count as "prior" must not make the test meaningless
		$existing = count( wc_get_orders( array(
			'customer_id' => $uid,
			'status'      => array( 'completed', 'processing', 'on-hold' ),
			'limit'       => -1,
			'return'      => 'ids',
		) ) );
		$threshold  = $existing + 1;
		$min_filter = function () use ( $threshold ) { return $threshold; };
		add_filter( 'gp_returning_min_prior_orders', $min_filter, 99 );
		GP_Data::say( "  existing qualifying orders: $existing -> threshold set to $threshold prior order(s) for this run" );

		/* ---- build $existing + 1 more filler orders so that the "first" test order has $existing prior ones ----
		   (existing ones already are in the database; nothing to create) */

		/* ---- 1. first order: not promoted ---- */
		GP_Data::say( '-- first order' );
		// With threshold = existing + 1 the first test order sees `existing` other orders -> no promotion.
		$o1 = wc_create_order( array( 'customer_id' => $uid, 'status' => 'pending' ) );
		$order_ids[] = $o1->get_id();
		$o1->update_status( 'processing' );
		gp_b4r_assert( 'tier stays b2c after the first order reaches processing', 'b2c' === PCS_DB::get_customer_tier( $uid ), PCS_DB::get_customer_tier( $uid ) );
		$h_after_1 = gp_b4r_hist_count( $uid );

		/* ---- 2. second order: promoted ---- */
		GP_Data::say( '-- second order' );
		$o2 = wc_create_order( array( 'customer_id' => $uid, 'status' => 'pending' ) );
		$order_ids[] = $o2->get_id();
		$o2->update_status( 'processing' );
		gp_b4r_assert( "tier is 'returning' after the second order reaches processing", 'returning' === PCS_DB::get_customer_tier( $uid ), PCS_DB::get_customer_tier( $uid ) );
		gp_b4r_assert( 'one tier history row b2c -> returning written', gp_b4r_hist_count( $uid ) === $h_after_1 + 1 );

		$tiers = PCS_DB::get_tiers_from_rules();
		gp_b4r_assert( "pricing rule for tier 'returning' exists with 5%", isset( $tiers['returning']['discount'] ) && 5.0 === (float) $tiers['returning']['discount'], isset( $tiers['returning'] ) ? wp_json_encode( $tiers['returning'] ) : 'no rule' );
		$d = (float) PCS_Pricing::get_effective_discount( $uid );
		$in_store = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_members WHERE user_id = %d", $uid
		) ) > 0;
		gp_b4r_assert( 'effective discount is 5 for the promoted user' . ( $in_store ? ' (>= 5, user also in a company store)' : '' ), $in_store ? $d >= 5.0 : 5.0 === $d, 'got ' . $d );

		/* ---- 3. idempotent ---- */
		GP_Data::say( '-- idempotence' );
		$h = gp_b4r_hist_count( $uid );
		PCS_Customers::maybe_promote_returning( $o2->get_id(), wc_get_order( $o2->get_id() ) );
		do_action( 'woocommerce_order_status_completed', $o2->get_id(), wc_get_order( $o2->get_id() ) );
		gp_b4r_assert( 'hook run again for a returning customer writes nothing', gp_b4r_hist_count( $uid ) === $h && 'returning' === PCS_DB::get_customer_tier( $uid ) );

		/* ---- 4. other tiers are never changed ---- */
		GP_Data::say( '-- other tiers untouched' );
		foreach ( array( 'b2b_a', 'reseller' ) as $t ) {
			update_user_meta( $uid, '_pcs_tier', $t );
			$h = gp_b4r_hist_count( $uid );
			do_action( 'woocommerce_order_status_processing', $o2->get_id(), wc_get_order( $o2->get_id() ) );
			do_action( 'woocommerce_order_status_completed', $o2->get_id() ); // order object omitted (older callers)
			gp_b4r_assert( "tier $t is never changed", $t === PCS_DB::get_customer_tier( $uid ) && gp_b4r_hist_count( $uid ) === $h, PCS_DB::get_customer_tier( $uid ) );
		}
		delete_user_meta( $uid, '_pcs_tier' );

		/* ---- 5. guest order ---- */
		GP_Data::say( '-- guest order' );
		$hist_all = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $hist_t" );
		$g = wc_create_order( array( 'customer_id' => 0, 'status' => 'pending' ) );
		$order_ids[] = $g->get_id();
		$g->update_status( 'processing' );
		gp_b4r_assert( 'guest order: no tier history written', (int) $wpdb->get_var( "SELECT COUNT(*) FROM $hist_t" ) === $hist_all );
		gp_b4r_assert( 'guest order: gp_test_b2c unchanged (b2c)', 'b2c' === PCS_DB::get_customer_tier( $uid ) );
	}

} finally {

	/* ALWAYS clean up --------------------------------------------------------- */
	GP_Data::say( '-- cleanup' );
	if ( null !== $min_filter ) {
		remove_filter( 'gp_returning_min_prior_orders', $min_filter, 99 );
	}
	foreach ( $order_ids as $oid ) {
		$o = wc_get_order( $oid );
		if ( $o ) { $o->delete( true ); }
	}
	if ( $order_ids ) { GP_Data::say( '  ' . count( $order_ids ) . ' throw-away order(s) deleted' ); }
	if ( $user ) {
		foreach ( $snap as $k => $s ) {
			if ( $s[0] ) { update_user_meta( $user->ID, $k, $s[1] ); } else { delete_user_meta( $user->ID, $k ); }
		}
		$wpdb->query( $wpdb->prepare( "DELETE FROM $hist_t WHERE id > %d AND user_id = %d", $hist_max, $user->ID ) );
		GP_Data::say( '  gp_test_b2c tier restored to ' . ( $snap['_pcs_tier'][0] ? var_export( $snap['_pcs_tier'][1], true ) : '(unset => b2c)' ) . ', new tier history rows removed' );
	}
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b4r_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b4r_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b4r_fail'] ? 1 : 0 );
