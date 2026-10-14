<?php
/**
 * Fixture for the reorder e2e: a paid-looking order of gp_test_b2c with printing options and a stub design folder.
 *   scripts/data/run.sh b4-reorder-fixture.php create   -> prints "ORDER <id>"
 *   scripts/data/run.sh b4-reorder-fixture.php cleanup  -> removes the orders of gp_test_b2c and the stub design folders
 */
require __DIR__ . '/_lib.php';
$mode = '';
foreach ( array_slice( $argv, 1 ) as $a ) { if ( 0 !== strpos( $a, '--' ) ) { $mode = $a; } }
$user = get_user_by( 'login', 'gp_test_b2c' );
if ( ! $user ) { echo "missing gp_test_b2c\n"; exit( 1 ); }
function gp_rf_rm( $d ) { if ( ! is_dir( $d ) ) { return; } foreach ( scandir( $d ) as $f ) { if ( '.' === $f || '..' === $f ) { continue; } is_dir( "$d/$f" ) ? gp_rf_rm( "$d/$f" ) : @unlink( "$d/$f" ); } @rmdir( $d ); }

if ( 'create' === $mode ) {
	$key = 'gprf' . strtolower( wp_generate_password( 10, false ) );
	$dir = NBDESIGNER_CUSTOMER_DIR . '/' . $key;
	wp_mkdir_p( $dir . '/preview' );
	file_put_contents( $dir . '/config.json', '{"gp":"reorder-fixture"}' );
	copy( ABSPATH . 'wp-content/uploads/2025/12/logo.png', $dir . '/preview/1.png' );
	$fields = array( 'f_size' => '48x96', 'f_pole' => '1' );
	$op = array( 'fields' => array(
		array( 'id' => 'f_size', 'name' => 'Size', 'val' => '48x96', 'value_name' => '48x96', 'price' => 0, 'published' => 'y' ),
		array( 'id' => 'f_pole', 'name' => 'Pole Pockets', 'val' => '1', 'value_name' => 'Top & Bottom', 'price' => 0, 'published' => 'y' ),
	), 'total_price' => 0, 'discount_price' => 0, 'cart_image' => '', 'manual_pm' => false );
	$options = array( 'id' => 0, 'title' => 'GP reorder fixture', 'fields' => base64_encode( serialize( array( 'fields' => array() ) ) ) );
	$order = wc_create_order( array( 'customer_id' => $user->ID, 'status' => 'pending' ) );
	$iid = $order->add_product( wc_get_product( 1168 ), 1 );
	$order->calculate_totals(); $order->set_status( 'processing' ); $order->save();
	$item = $order->get_item( $iid );
	$item->add_meta_data( '_nbo_option_price', $op ); $item->add_meta_data( '_nbo_field', $fields );
	$item->add_meta_data( '_nbo_options', wp_slash( $options ) ); $item->add_meta_data( '_nbo_original_price', 10.0 ); $item->add_meta_data( '_nbd', $key );
	$item->save();
	echo 'ORDER ' . $order->get_id() . ' KEY ' . $key . "\n";
	exit( 0 );
}
if ( 'cleanup' === $mode ) {
	foreach ( wc_get_orders( array( 'customer_id' => $user->ID, 'limit' => -1, 'status' => 'any' ) ) as $o ) {
		foreach ( $o->get_items() as $it ) { $k = $it->get_meta( '_nbd' ); if ( $k && 0 === strpos( $k, 'gprf' ) ) { gp_rf_rm( NBDESIGNER_CUSTOMER_DIR . '/' . $k ); } }
		$id = $o->get_id(); $o->delete( true ); echo "deleted order $id\n";
	}
	exit( 0 );
}
echo "usage: create|cleanup\n"; exit( 2 );
