<?php
/**
 * B3 test fixture (company store, member, sent quote) for the e2e suite + invitation self test.
 *   scripts/data/run.sh b3-fixture.php create            -> prints a line "FIXTURE {json}" (ids)
 *   scripts/data/run.sh b3-fixture.php status            -> prints "STATUS {json}" (store / quote status, member discounts)
 *   scripts/data/run.sh b3-fixture.php selftest-invite   -> invitation takeover self test (creates and removes its own rows)
 *   scripts/data/run.sh b3-fixture.php clear-ratelimit   -> removes login rate-limit counters/locks
 *   scripts/data/run.sh b3-fixture.php cleanup           -> removes everything this script created
 * Only touches rows it created itself (company 'GP Test Company', quote title 'GP test quote', @example.test e-mails).
 * No backup JSON is written (throw-away test rows); mail is suppressed by the bootstrap.
 */
require __DIR__ . '/_lib.php';
$mode = '';
foreach ( array_slice( $argv, 1 ) as $a ) { if ( 0 !== strpos( $a, '--' ) ) { $mode = $a; } }
global $wpdb;

function gp_fx_say( $label, $data ) { echo $label . ' ' . wp_json_encode( $data ) . "\n"; }
function gp_fx_user( $login ) { $u = get_user_by( 'login', $login ); if ( ! $u ) { echo "missing user $login (run scripts/test-users.sh create)\n"; exit( 1 ); } return $u; }
function gp_fx_find_store() { global $wpdb; return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}pcs_b2b_stores WHERE company_name = 'GP Test Company' ORDER BY id DESC LIMIT 1" ); }

if ( 'create' === $mode ) {
	$b2b = gp_fx_user( 'gp_test_b2b' ); $b2c = gp_fx_user( 'gp_test_b2c' );
	$store = gp_fx_find_store();
	if ( ! $store ) {
		$sid = PCS_DB::save_store( array(
			'user_id' => $b2b->ID, 'company_name' => 'GP Test Company', 'company_slug' => 'gp-test-company', 'contact_name' => 'GP Test',
			'contact_email' => $b2b->user_email, 'tier' => 'b2b_a', 'status' => 'pending',
		) );
		PCS_DB::add_member( $sid, $b2b->ID, 'admin', 0 );
		PCS_B2B_Stores::approve_store( $sid, array( 'tier' => 'b2b_a', 'team_size' => 5 ) );
		$store = PCS_DB::get_store( $sid );
	}
	$sid = (int) $store->id;
	if ( ! PCS_DB::get_member_by_user( $sid, $b2c->ID ) ) { PCS_DB::add_member( $sid, $b2c->ID, 'member', 0 ); }
	$qid = (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pcs_quotes WHERE title = 'GP test quote' ORDER BY id DESC LIMIT 1" );
	if ( ! $qid ) {
		$qid = (int) PCS_Quotes::create( array( 'user_id' => $b2b->ID, 'store_id' => $sid, 'title' => 'GP test quote', 'message' => 'e2e fixture' ) );
	}
	PCS_Quotes::update( $qid, array( 'status' => 'sent', 'subtotal' => 50, 'total' => 50, 'valid_until' => gmdate( 'Y-m-d', time() + 10 * DAY_IN_SECONDS ), 'sent_at' => current_time( 'mysql' ), 'responded_at' => null, 'wc_order_id' => null ) );
	gp_fx_say( 'FIXTURE', array( 'store' => $sid, 'quote' => $qid, 'owner' => $b2b->ID, 'member' => $b2c->ID ) );
	exit( 0 );
}

if ( 'status' === $mode ) {
	$store = gp_fx_find_store();
	$q = $wpdb->get_row( "SELECT id, status, wc_order_id FROM {$wpdb->prefix}pcs_quotes WHERE title = 'GP test quote' ORDER BY id DESC LIMIT 1" );
	$b2c = get_user_by( 'login', 'gp_test_b2c' );
	gp_fx_say( 'STATUS', array(
		'store' => $store ? $store->status : null,
		'quote' => $q ? $q->status : null,
		'quote_order' => $q ? $q->wc_order_id : null,
		'member_discount' => $b2c ? PCS_Pricing::get_effective_discount( $b2c->ID ) : null,
		'orders' => count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'status' => 'any' ) ) ),
	) );
	exit( 0 );
}

if ( 'clear-ratelimit' === $mode ) {
	$n = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_gp\\_lf\\_%' OR option_name LIKE '\\_transient\\_timeout\\_gp\\_lf\\_%'" );
	echo "cleared $n option rows\n";
	exit( 0 );
}

if ( 'cleanup' === $mode ) {
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}pcs_quotes WHERE title = 'GP test quote'" ) as $qid ) {
		$oid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT wc_order_id FROM {$wpdb->prefix}pcs_quotes WHERE id = %d", $qid ) );
		if ( $oid && ( $o = wc_get_order( $oid ) ) ) { $o->delete( true ); echo "deleted order $oid\n"; }
		PCS_Quotes::delete( (int) $qid ); echo "deleted quote $qid\n";
	}
	// orders created for the fixture users by the e2e quote acceptance (staging has no real orders)
	foreach ( array( 'gp_test_b2c', 'gp_test_b2b', 'gp_test_reseller' ) as $login ) {
		$u = get_user_by( 'login', $login );
		if ( ! $u ) { continue; }
		foreach ( wc_get_orders( array( 'customer_id' => $u->ID, 'limit' => -1, 'status' => 'any' ) ) as $o ) { $oid = $o->get_id(); $o->delete( true ); echo "deleted order $oid of $login
"; }
	}
	$store = gp_fx_find_store();
	if ( $store ) {
		$wpdb->delete( $wpdb->prefix . 'pcs_b2b_members', array( 'store_id' => $store->id ) );
		$wpdb->delete( $wpdb->prefix . 'pcs_b2b_stores', array( 'id' => $store->id ) );
		echo "deleted store {$store->id} and its members\n";
	}
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_gp\\_lf\\_%' OR option_name LIKE '\\_transient\\_timeout\\_gp\\_lf\\_%'" );
	exit( 0 );
}

if ( 'selftest-invite' === $mode ) {
	$fail = 0;
	$ok = function ( $label, $cond ) use ( &$fail ) { echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } };
	$email = 'gp_invite_victim@example.test';
	$cleanup = function () use ( $email, $wpdb ) {
		$u = get_user_by( 'email', $email );
		if ( $u ) {
			$s = PCS_DB::get_store_by_user( $u->ID );
			if ( $s ) { $wpdb->delete( $wpdb->prefix . 'pcs_b2b_members', array( 'store_id' => $s->id ) ); $wpdb->delete( $wpdb->prefix . 'pcs_b2b_stores', array( 'id' => $s->id ) ); }
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $u->ID );
		}
		$wpdb->delete( $wpdb->prefix . 'pcs_invitations', array( 'email' => $email ) );
		unset( $_COOKIE['pcs_inv'] );
	};
	$cleanup();
	try {
		// 1) invitation for a tier that does not exist falls back to a valid tier
		$id = PCS_Invitations::send_invitation( $email, 'Victim Co', 'Victim', 'no_such_tier', '', 14 );
		$inv = PCS_DB::get_invitation_by_id( $id );
		$tiers = PCS_DB::get_tiers_from_rules();
		$ok( 'proposed tier validated (' . $inv->proposed_tier . ')', isset( $tiers[ $inv->proposed_tier ] ) );
		$wpdb->update( $wpdb->prefix . 'pcs_invitations', array( 'proposed_tier' => 'reseller' ), array( 'id' => $id ) );
		// 2) takeover: somebody registers with the invited e-mail but never saw the token
		$uid = wp_insert_user( array( 'user_login' => 'gp_invite_victim', 'user_email' => $email, 'user_pass' => wp_generate_password( 24 ), 'role' => 'customer' ) );
		$ok( 'user created', ! is_wp_error( $uid ) );
		$ok( 'registration WITHOUT the token does not grant the tier (tier ' . PCS_DB::get_customer_tier( $uid ) . ')', 'reseller' !== PCS_DB::get_customer_tier( $uid ) );
		$ok( 'registration WITHOUT the token does not create a store', ! PCS_DB::get_store_by_user( $uid ) );
		$still = PCS_DB::get_invitation_by_id( $id );
		$ok( 'invitation still pending after the takeover attempt', 'pending' === $still->status );
		// 3) legitimate path: cookie with the right token + same e-mail applies it, once
		$_COOKIE['pcs_inv'] = $still->token;
		do_action( 'user_register', $uid );
		$ok( 'registration WITH the matching token grants the tier (tier ' . PCS_DB::get_customer_tier( $uid ) . ')', 'reseller' === PCS_DB::get_customer_tier( $uid ) );
		$ok( 'invitation accepted and single use', 'accepted' === PCS_DB::get_invitation_by_id( $id )->status );
		// 4) token of another e-mail does nothing
		$wpdb->delete( $wpdb->prefix . 'pcs_invitations', array( 'email' => $email ) );
		$id2 = PCS_Invitations::send_invitation( 'gp_other_person@example.test', 'Other', 'Other', 'reseller', '', 14 );
		$inv2 = PCS_DB::get_invitation_by_id( $id2 );
		update_user_meta( $uid, '_pcs_tier', 'b2c' );
		$_COOKIE['pcs_inv'] = $inv2->token;
		do_action( 'user_register', $uid );
		$ok( 'token for a different e-mail does not apply (tier ' . PCS_DB::get_customer_tier( $uid ) . ')', 'reseller' !== PCS_DB::get_customer_tier( $uid ) );
		$wpdb->delete( $wpdb->prefix . 'pcs_invitations', array( 'email' => 'gp_other_person@example.test' ) );
	} catch ( Throwable $e ) { echo 'FAIL exception ' . $e->getMessage() . "\n"; $fail++; }
	$cleanup();
	echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS\n";
	exit( $fail ? 1 : 0 );
}

echo "usage: b3-fixture.php create|status|selftest-invite|clear-ratelimit|cleanup\n";
exit( 2 );
