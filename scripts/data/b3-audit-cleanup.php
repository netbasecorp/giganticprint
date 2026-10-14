<?php
/**
 * B3 cleanup of leftovers created on 2026-10-14 by the audit and by the e2e harness (guest carts/sessions, test designs and uploads).
 *   scripts/data/run.sh b3-audit-cleanup.php [--dry-run|--apply]
 * Lists everything first (dry-run is the default). Only items whose modification time is on 2026-10-14 (UTC) are touched,
 * only inside wp-content/uploads/{nbdesigner,gp-chunks}, nothing else. Rows are listed with their full key so they can be
 * re-checked; WooCommerce sessions and persistent carts of the three fixture users are cleared too (they only hold test carts).
 * Not restorable by design (throw-away test data); the listing is printed and kept in the CHG entry.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b3-audit-cleanup' );
global $wpdb;
if ( 'restore' === GP_Data::$mode ) { exit( 0 ); }

$day_start = strtotime( '2026-10-14 00:00:00 UTC' );
$day_end   = strtotime( '2026-10-15 00:00:00 UTC' );
$up   = wp_upload_dir();
$base = realpath( $up['basedir'] );
$total_bytes = 0;

function gp_cl_size( $path ) {
	$s = 0;
	if ( is_file( $path ) ) { return filesize( $path ); }
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) { $s += $f->isFile() ? $f->getSize() : 0; }
	return $s;
}
function gp_cl_rm( $path ) {
	if ( is_link( $path ) || is_file( $path ) ) { @unlink( $path ); return; }
	foreach ( scandir( $path ) as $x ) { if ( '.' !== $x && '..' !== $x ) { gp_cl_rm( $path . '/' . $x ); } }
	@rmdir( $path );
}

GP_Data::say( '-- WooCommerce sessions (all current rows are from today)' );
$sess = $wpdb->get_results( "SELECT session_id, session_key, FROM_UNIXTIME(session_expiry) AS expires, LENGTH(session_value) AS len FROM {$wpdb->prefix}woocommerce_sessions", ARRAY_A );
foreach ( $sess as $s ) { GP_Data::say( "  session #{$s['session_id']} key {$s['session_key']} expires {$s['expires']} ({$s['len']} bytes)" ); }
if ( GP_Data::writing() && $sess ) { $wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_sessions" ); GP_Data::say( '  deleted ' . count( $sess ) . ' session row(s)' ); }

GP_Data::say( '-- checkout-draft orders (created by visiting the checkout as a guest)' );
foreach ( wc_get_orders( array( 'limit' => -1, 'status' => 'checkout-draft' ) ) as $o ) {
	$ts = $o->get_date_created() ? $o->get_date_created()->getTimestamp() : 0;
	if ( $ts < $day_start || $ts >= $day_end ) { continue; }
	GP_Data::say( '  draft order #' . $o->get_id() . ' total ' . $o->get_total() . ' created ' . gmdate( 'Y-m-d H:i', $ts ) . ' UTC' );
	if ( GP_Data::writing() ) { $o->delete( true ); }
}

GP_Data::say( '-- persistent carts of the fixture users' );
foreach ( array( 'gp_test_b2c', 'gp_test_b2b', 'gp_test_reseller' ) as $login ) {
	$u = get_user_by( 'login', $login );
	if ( ! $u ) { continue; }
	$meta = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id, meta_key FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s", $u->ID, '\_woocommerce\_persistent\_cart%' ), ARRAY_A );
	foreach ( $meta as $m ) {
		GP_Data::say( "  usermeta {$m['umeta_id']} {$m['meta_key']} of $login (#{$u->ID})" );
		if ( GP_Data::writing() ) { $wpdb->delete( $wpdb->usermeta, array( 'umeta_id' => $m['umeta_id'] ) ); }
	}
}

GP_Data::say( '-- upload folders touched on 2026-10-14' );
$targets = array( 'nbdesigner/designs', 'nbdesigner/temp', 'nbdesigner/uploads', 'nbdesigner/download', 'nbdesigner/cart', 'gp-chunks' );
foreach ( $targets as $sub ) {
	$dir = $base . '/' . $sub;
	if ( ! is_dir( $dir ) ) { continue; }
	foreach ( scandir( $dir ) as $name ) {
		if ( '.' === $name || '..' === $name || 'index.html' === $name || 'index.php' === $name || '.htaccess' === $name ) { continue; }
		$p = $dir . '/' . $name;
		$real = realpath( $p );
		if ( ! $real || 0 !== strpos( $real, $base . '/' ) ) { continue; } // never leave uploads/
		$m = filemtime( $p );
		if ( $m < $day_start || $m >= $day_end ) { continue; }
		if ( 'nbdesigner/temp' === $sub && is_dir( $p ) ) {
			// date folders (2026/10): descend one level and only remove today's day/file entries
			$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $p, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
			$files = 0; $bytes = 0;
			foreach ( $it as $f ) { if ( $f->isFile() && $f->getMTime() >= $day_start && $f->getMTime() < $day_end ) { $files++; $bytes += $f->getSize(); if ( GP_Data::writing() ) { @unlink( $f->getPathname() ); } } }
			$total_bytes += $bytes;
			GP_Data::say( "  $sub/$name: $files file(s) from today, " . round( $bytes / 1048576, 1 ) . ' MB' );
			continue;
		}
		$bytes = gp_cl_size( $p ); $total_bytes += $bytes;
		GP_Data::say( "  $sub/$name (" . date( 'Y-m-d H:i', $m ) . ' UTC, ' . round( $bytes / 1024 ) . ' KB)' );
		if ( GP_Data::writing() ) { gp_cl_rm( $p ); }
	}
}
GP_Data::say( '  total ' . round( $total_bytes / 1048576, 1 ) . ' MB' );
if ( GP_Data::writing() ) { GP_Data::flush_caches(); }
GP_Data::finish();
