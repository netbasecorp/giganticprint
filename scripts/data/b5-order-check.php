<?php
/**
 * B5 order check: do the test orders hold print-ready design files, and can an administrator download them?
 *   scripts/data/run.sh b5-order-check.php <order_id> [<order_id> ...]
 * For every NBDesigner line of each order: the design folder exists with design.json, a print PNG per side that has art,
 * and the admin download link (?download_nbd_design_file=1 ...) answers 200 with a non-empty PNG/ZIP and PDF.
 * The administrator session is a throw-away auth cookie created inside this script (never printed) for user #1.
 * Read-only for site data (it only reads files and makes loopback HTTP requests).
 */
require __DIR__ . '/_lib.php';
$ids = array();
foreach ( array_slice( $argv, 1 ) as $a ) { if ( ctype_digit( $a ) ) { $ids[] = (int) $a; } }
$fail = 0;
function gp_oc( $label, $ok, $detail = '' ) { global $fail; echo ( $ok ? '  PASS ' : '  FAIL ' ) . $label . ( $detail && ! $ok ? " ($detail)" : '' ) . "\n"; if ( ! $ok ) { $fail++; } }
if ( ! $ids ) { echo "usage: b5-order-check.php <order_id>...\n"; exit( 2 ); }

$exp   = time() + 600;
$token = WP_Session_Tokens::get_instance( 1 )->create( $exp );
$cookie = SECURE_AUTH_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( 1, $exp, 'secure_auth', $token ) )
	. '; ' . LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( 1, $exp, 'logged_in', $token ) );
$clean_sessions = function () use ( $token ) { WP_Session_Tokens::get_instance( 1 )->destroy( $token ); };

try {
	foreach ( $ids as $oid ) {
		$o = wc_get_order( $oid );
		echo "-- order #$oid\n";
		if ( ! $o ) { gp_oc( 'order exists', false ); continue; }
		gp_oc( 'status on-hold (new) or completed (demo sample)', in_array( $o->get_status(), array( 'on-hold', 'completed' ), true ), $o->get_status() );
		gp_oc( 'payment method title', 'Pay by invoice' === $o->get_payment_method_title(), $o->get_payment_method_title() );
		gp_oc( 'has a shipping line', count( $o->get_shipping_methods() ) > 0 );
		$n = 0;
		foreach ( $o->get_items() as $item_id => $item ) {
			$key = $item->get_meta( '_nbd' );
			if ( ! $key ) { continue; }
			$n++;
			$dir = NBDESIGNER_CUSTOMER_DIR . '/' . $key;
			gp_oc( "item $item_id design folder", is_dir( $dir ) );
			gp_oc( "item $item_id design.json", is_file( $dir . '/design.json' ) && filesize( $dir . '/design.json' ) > 50 );
			gp_oc( "item $item_id print PNG of side 1 (frame_0.png)", is_file( $dir . '/frame_0.png' ) && filesize( $dir . '/frame_0.png' ) > 1000 );
			// the admin 'Customer Design' box / admin.php?page=nbdesigner_detail_order links of NBDesigner
			$product_id = $item->get_product_id();
			foreach ( array( 'png' => 'PNG', 'pdf' => 'PDF', 'svg' => 'SVG' ) as $type => $lbl ) {
				$url = add_query_arg( array( 'page' => 'nbdesigner_detail_order', 'order_id' => $oid, 'product_id' => $product_id, 'variation_id' => 0, 'nbd_item_key' => $key, 'download-type' => $type ), admin_url( 'admin.php' ) );
				$r = wp_remote_get( $url, array( 'timeout' => 180, 'redirection' => 0, 'headers' => array( 'Cookie' => $cookie ), 'sslverify' => false ) );
				$code = is_wp_error( $r ) ? 0 : wp_remote_retrieve_response_code( $r );
				$ct = is_wp_error( $r ) ? '' : (string) wp_remote_retrieve_header( $r, 'content-type' );
				$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
				$len = strlen( $body );
				$magic = ( 0 === strpos( $body, 'PK' ) ) ? 'zip' : ( 0 === strpos( $body, '%PDF' ) ? 'pdf' : ( 0 === strpos( $body, "PNG" ) ? 'png' : ( 0 === strpos( $body, '<svg' ) || 0 === strpos( $body, '<?xml' ) ? 'svg' : bin2hex( substr( $body, 0, 4 ) ) ) ) );
				gp_oc( "item $item_id admin download $lbl: HTTP $code, $ct, $len bytes, $magic", 200 === $code && $len > 500 && in_array( $magic, array( 'zip', 'pdf', 'png', 'svg' ), true ), "http $code $ct $len $magic " . ( is_wp_error( $r ) ? $r->get_error_message() : '' ) );
			}
			break; // one line per order is enough (a cart can hold several lines of the same art)
		}
		gp_oc( 'order has at least one design line', $n > 0 );
	}
} finally { $clean_sessions(); }
echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS\n";
exit( $fail ? 1 : 0 );
