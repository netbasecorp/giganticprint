<?php
// Prints JSON with the addresses of the existing demo orders (read-only): the Order received page needs the order key,
// so it cannot be guessed. Used by capture.mjs as ${URL:name}. No order is created or changed.
if ( PHP_SAPI !== 'cli' ) { exit( 1 ); }
$_SERVER['HTTP_HOST'] = 'giganticprints.printcart.com'; $_SERVER['HTTPS'] = 'on'; $_SERVER['REQUEST_URI'] = '/';
define( 'WP_USE_THEMES', false );
require '/home/giganticprints/public_html/wp-load.php';
$out = array();
foreach ( array( 'b2c', 'b2b', 'reseller', 'staff' ) as $who ) {
	$u = get_user_by( 'login', 'gp_test_' . $who );
	if ( ! $u ) { continue; }
	$orders = wc_get_orders( array( 'customer_id' => $u->ID, 'limit' => 1, 'orderby' => 'date', 'order' => 'ASC', 'status' => 'completed' ) );
	if ( ! $orders ) { continue; }
	$o = $orders[0];
	$out[ 'order_received_' . $who ] = $o->get_checkout_order_received_url();
	$out[ 'order_view_' . $who ]     = $o->get_view_order_url();
	$out[ 'order_edit_' . $who ]     = admin_url( 'post.php?post=' . $o->get_id() . '&action=edit' );
	$out[ 'order_id_' . $who ]       = $o->get_id();
}
echo wp_json_encode( $out ) . "\n";
