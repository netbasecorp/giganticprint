<?php
/**
 * B3 security / B2B checks (read-only unless --apply).
 *   scripts/data/run.sh b3-security-check.php [--dry-run|--apply]
 *
 * Always run (no persistent writes, transients are removed afterwards):
 *   - PCS_DB::store_is_approved() for 'active' / 'approved' / 'pending'
 *   - login rate limit: 6 bad wp_authenticate() calls -> the 6th returns too_many_attempts
 *   - REST /wp/v2/users routes are gone for logged-out requests
 * --apply only (creates and ALWAYS removes temporary rows):
 *   - pending store for gp_test_b2b -> PCS_B2B_Stores::approve_store() -> status 'active'
 *   - gp_test_b2c added as member -> effective discount 10, gp_test_reseller (personal tier) -> 20
 *
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b3-security-check' );
global $wpdb;

$GLOBALS['gp_b3_fail'] = 0;

function gp_b3_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b3_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b3_val( $v ) {
	return is_scalar( $v ) || null === $v ? var_export( $v, true ) : wp_json_encode( $v );
}

/* ---- state to clean up ---------------------------------------------------- */
$store_id         = 0;
$member_row_added = false;
$owner            = null;
$owner_tier_raw   = null;   // raw _pcs_tier meta of gp_test_b2b before approve_store() touched it
$owner_tier_had   = false;
$history_before   = 0;
$limit_user       = 'gp_b3_nobody_' . strtolower( wp_generate_password( 8, false ) );

try {

	/* 1. store status helper ------------------------------------------------ */
	GP_Data::say( '-- 1. PCS_DB::store_is_approved' );
	if ( ! class_exists( 'PCS_DB' ) || ! method_exists( 'PCS_DB', 'store_is_approved' ) ) {
		gp_b3_assert( 'PCS_DB::store_is_approved exists', false );
	} else {
		gp_b3_assert( "store_is_approved('active')",   true === PCS_DB::store_is_approved( 'active' ) );
		gp_b3_assert( "store_is_approved('approved')", true === PCS_DB::store_is_approved( 'approved' ) );
		gp_b3_assert( "!store_is_approved('pending')",  false === PCS_DB::store_is_approved( 'pending' ) );
		gp_b3_assert( "!store_is_approved('rejected')", false === PCS_DB::store_is_approved( 'rejected' ) );
	}

	/* 2. store approval + member discount (apply only) ------------------------ */
	GP_Data::say( '-- 2. approval + company tier discount' );
	if ( ! GP_Data::writing() ) {
		GP_Data::say( '  SKIP (needs --apply: creates and removes a temporary store row)' );
	} elseif ( ! class_exists( 'PCS_B2B_Stores' ) || ! class_exists( 'PCS_Pricing' ) ) {
		gp_b3_assert( 'printcart-b2bstore classes loaded', false );
	} else {
		$owner    = get_user_by( 'login', 'gp_test_b2b' );
		$member   = get_user_by( 'login', 'gp_test_b2c' );
		$reseller = get_user_by( 'login', 'gp_test_reseller' );
		gp_b3_assert( 'test users gp_test_b2b / gp_test_b2c / gp_test_reseller exist', $owner && $member && $reseller );

		if ( $owner && $member && $reseller ) {
			$stores_t  = $wpdb->prefix . 'pcs_b2b_stores';
			$members_t = $wpdb->prefix . 'pcs_b2b_members';
			$hist_t    = $wpdb->prefix . 'pcs_tier_history';

			if ( PCS_DB::get_store_by_user( $owner->ID ) ) {
				gp_b3_assert( 'gp_test_b2b has no store yet (refusing to touch an existing one)', false );
			} else {
				// snapshot what approve_store() will change on the owner
				$owner_tier_had = metadata_exists( 'user', $owner->ID, '_pcs_tier' );
				$owner_tier_raw = $owner_tier_had ? get_user_meta( $owner->ID, '_pcs_tier', true ) : null;
				$history_before = (int) $wpdb->get_var( "SELECT COALESCE(MAX(id),0) FROM $hist_t" );

				$store_id = (int) PCS_DB::save_store( array(
					'user_id'       => $owner->ID,
					'company_name'  => 'GP B3 Security Check (temporary)',
					'company_slug'  => 'gp-b3-security-check-temp',
					'tier'          => 'b2b_a',
					'status'        => 'pending',
					'contact_name'  => 'GP Test',
					'contact_email' => $owner->user_email,
				) );
				gp_b3_assert( 'temporary store created', $store_id > 0 );

				if ( $store_id > 0 ) {
					$s = PCS_DB::get_store_by_id( $store_id );
					gp_b3_assert( "new store has status 'pending'", $s && 'pending' === $s->status, $s ? $s->status : 'no row' );

					$ok = PCS_B2B_Stores::approve_store( $store_id, array( 'tier' => 'b2b_a', 'team_size' => 5 ) );
					gp_b3_assert( 'approve_store() returned true', true === $ok );

					$s = PCS_DB::get_store_by_id( $store_id );
					gp_b3_assert( "approved store has status 'active'", $s && 'active' === $s->status, $s ? $s->status : 'no row' );
					gp_b3_assert( 'store_is_approved(stored status)', $s && PCS_DB::store_is_approved( $s->status ) );

					$added = PCS_DB::add_member( $store_id, $member->ID, 'member', 0 );
					$member_row_added = (bool) $added;
					gp_b3_assert( 'gp_test_b2c added as member', $member_row_added );

					$d_member = (float) PCS_Pricing::get_effective_discount( $member->ID );
					gp_b3_assert( 'company member gets the company tier discount (10)', 10.0 === $d_member, 'got ' . gp_b3_val( $d_member ) );

					$d_res = (float) PCS_Pricing::get_effective_discount( $reseller->ID );
					gp_b3_assert( 'gp_test_reseller personal tier discount (20)', 20.0 === $d_res, 'got ' . gp_b3_val( $d_res ) );

					// legacy rows written as 'approved' must keep working
					$wpdb->update( $stores_t, array( 'status' => 'approved' ), array( 'id' => $store_id ), array( '%s' ), array( '%d' ) );
					$d_legacy = (float) PCS_Pricing::get_effective_discount( $member->ID );
					gp_b3_assert( "legacy status 'approved' still gives the member discount (10)", 10.0 === $d_legacy, 'got ' . gp_b3_val( $d_legacy ) );
				}
			}
		}
	}

	/* 3. login rate limit ------------------------------------------------------ */
	GP_Data::say( '-- 3. login rate limit' );
	if ( ! class_exists( 'GP_Hardening' ) ) {
		gp_b3_assert( 'GP_Hardening loaded (cmsmart-ui-override active)', false );
	} else {
		$codes = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$r = wp_authenticate( $limit_user, 'definitely-wrong-password-' . $i );
			$codes[] = is_wp_error( $r ) ? $r->get_error_code() : 'user';
		}
		gp_b3_assert( 'attempts 1-5 fail with a normal error', 'too_many_attempts' !== $codes[0] && 'too_many_attempts' !== $codes[4], implode( ',', $codes ) );
		gp_b3_assert( 'attempt 6 returns too_many_attempts', 'too_many_attempts' === $codes[5], implode( ',', $codes ) );

		$r = wp_authenticate( $limit_user, 'x' );
		gp_b3_assert( 'lockout message is generic', is_wp_error( $r ) && 'Too many failed attempts. Please try again in 15 minutes.' === $r->get_error_message() );
	}

	/* 4. REST users route hidden for anonymous --------------------------------- */
	GP_Data::say( '-- 4. REST user enumeration' );
	wp_set_current_user( 0 );
	$routes = rest_get_server()->get_routes();
	gp_b3_assert( '/wp/v2/users removed for logged-out requests', ! isset( $routes['/wp/v2/users'] ) );
	gp_b3_assert( '/wp/v2/users/(?P<id>[\d]+) removed for logged-out requests', ! isset( $routes['/wp/v2/users/(?P<id>[\d]+)'] ) );
	gp_b3_assert( 'other REST routes still present (/wp/v2/posts)', isset( $routes['/wp/v2/posts'] ) );

} finally {

	/* ALWAYS clean up --------------------------------------------------------- */
	GP_Data::say( '-- cleanup' );
	if ( class_exists( 'GP_Hardening' ) ) {
		GP_Hardening::clear_all( $limit_user, GP_Hardening::client_ip() );
		GP_Data::say( '  login counters/locks cleared' );
	}
	if ( $store_id > 0 ) {
		if ( $owner ) {
			// approve_store() set the owner tier + wrote tier history: put both back
			if ( $owner_tier_had ) {
				update_user_meta( $owner->ID, '_pcs_tier', $owner_tier_raw );
			} else {
				delete_user_meta( $owner->ID, '_pcs_tier' );
			}
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}pcs_tier_history WHERE id > %d AND user_id = %d AND note = %s",
				$history_before, $owner->ID, 'Store approved'
			) );
		}
		// delete_store() removes the store row and all its member rows
		PCS_DB::delete_store( $store_id );
		$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_stores WHERE id = %d", $store_id ) )
		      + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_b2b_members WHERE store_id = %d", $store_id ) );
		GP_Data::say( '  temporary store/member rows removed' . ( 0 === $left ? '' : " (WARNING $left row(s) left, store id $store_id)" ) );
	}
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b3_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b3_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b3_fail'] ? 1 : 0 );
