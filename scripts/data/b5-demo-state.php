<?php
/**
 * B5 clean demo state of the Gigantic Prints staging store (what the customer sees at hand-over).
 *   scripts/data/run.sh b5-demo-state.php status
 *   scripts/data/run.sh b5-demo-state.php finalize gp_test_b2c=<order_id> gp_test_b2b=<order_id> gp_test_reseller=<order_id> gp_test_staff=<order_id>
 *
 * finalize (idempotent):
 *  - per test user: keeps the ONE given order (set to Completed), deletes every other order of the test users and the
 *    design folders of the deleted orders; removes the fixture quote 'GP test quote'
 *  - company 'Demo Signs Co': one Image Zone image ('Demo logo.png'), one template ('Demo banner template', product 1168,
 *    made from the design of the b2b sample order), private product 1263 assigned
 *  - carts of the test users emptied (persistent cart + WooCommerce session rows), login sessions cleared
 * Throw-away demo data: removed again by `b3-fixture.php cleanup` + `test-users.sh cleanup` at go-live.
 * No backup JSON (test data only); e-mails are suppressed by the bootstrap / gp_mail_sink.
 */
require __DIR__ . '/_lib.php';
global $wpdb;
$mode = '';
$keep = array();
foreach ( array_slice( $argv, 1 ) as $a ) {
	if ( 0 === strpos( $a, '--' ) ) { continue; }
	if ( false !== strpos( $a, '=' ) ) { list( $l, $id ) = explode( '=', $a, 2 ); $keep[ $l ] = (int) $id; }
	elseif ( '' === $mode ) { $mode = $a; }
}
const GP_DS_USERS = array( 'gp_test_b2c', 'gp_test_b2b', 'gp_test_reseller', 'gp_test_staff' );

function gp_ds_rm( $d ) { if ( ! is_dir( $d ) ) { return; } foreach ( scandir( $d ) as $f ) { if ( '.' === $f || '..' === $f ) { continue; } is_dir( "$d/$f" ) ? gp_ds_rm( "$d/$f" ) : @unlink( "$d/$f" ); } @rmdir( $d ); }
function gp_ds_store( $name ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pcs_b2b_stores WHERE company_name = %s ORDER BY id DESC LIMIT 1", $name ) ); }

if ( 'status' === $mode ) {
	foreach ( GP_DS_USERS as $l ) {
		$u = get_user_by( 'login', $l );
		if ( ! $u ) { echo "USER $l missing\n"; continue; }
		$orders = array();
		foreach ( wc_get_orders( array( 'customer_id' => $u->ID, 'limit' => -1, 'status' => 'any' ) ) as $o ) { $orders[] = '#' . $o->get_id() . ' ' . $o->get_status() . ' ' . $o->get_formatted_order_total() . ' (' . $o->get_payment_method_title() . ')'; }
		$st = $wpdb->get_row( $wpdb->prepare( "SELECT s.company_name, m.role FROM {$wpdb->prefix}pcs_b2b_members m JOIN {$wpdb->prefix}pcs_b2b_stores s ON s.id = m.store_id WHERE m.user_id = %d", $u->ID ) );
		$cart = get_user_meta( $u->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		$sess = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (string) $u->ID ) );
		echo 'USER ' . $l . ' #' . $u->ID . ' tier=' . get_user_meta( $u->ID, '_pcs_tier', true ) . ' company=' . ( $st ? $st->company_name . ' (' . $st->role . ')' : '-' )
			. ' discount=' . PCS_Pricing::get_effective_discount( $u->ID ) . ' cart_items=' . ( is_array( $cart ) && ! empty( $cart['cart'] ) ? count( $cart['cart'] ) : 0 ) . ' wc_session_rows=' . $sess
			. ' orders=[' . implode( '; ', $orders ) . "]\n";
	}
	foreach ( array( 'Demo Signs Co', 'Demo Reseller Co' ) as $n ) {
		$s = gp_ds_store( $n );
		if ( ! $s ) { echo "COMPANY $n missing\n"; continue; }
		$img = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Images::table() . ' WHERE store_id = %d', $s->id ) );
		$tpl = $wpdb->get_results( $wpdb->prepare( 'SELECT id, name, product_id FROM ' . PCS_Company_Templates::table() . ' WHERE store_id = %d', $s->id ), ARRAY_A );
		$prv = $wpdb->get_col( $wpdb->prepare( 'SELECT product_id FROM ' . PCS_Private_Products::table() . ' WHERE store_id = %d', $s->id ) );
		echo 'COMPANY ' . $n . ' #' . $s->id . ' tier=' . $s->tier . ' status=' . $s->status . ' images=' . $img . ' templates=' . wp_json_encode( $tpl ) . ' private_products=' . wp_json_encode( $prv ) . "\n";
	}
	echo 'quotes=' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pcs_quotes" ) . ' all_orders=' . count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'status' => 'any' ) ) ) . ' gp_mail_sink=' . get_option( 'gp_mail_sink' ) . "\n";
	exit( 0 );
}

if ( 'finalize' !== $mode ) { echo "usage: status | finalize login=orderid ...\n"; exit( 2 ); }
$fail = 0;
function gp_ds_ok( $label, $ok ) { global $fail; echo ( $ok ? '  PASS ' : '  FAIL ' ) . $label . "\n"; if ( ! $ok ) { $fail++; } }

echo "-- orders\n";
$keep_ids = array_values( $keep );
foreach ( GP_DS_USERS as $l ) {
	$u = get_user_by( 'login', $l );
	if ( ! $u ) { gp_ds_ok( "user $l exists", false ); continue; }
	if ( empty( $keep[ $l ] ) ) { gp_ds_ok( "sample order given for $l", false ); continue; }
	$sample = wc_get_order( $keep[ $l ] );
	gp_ds_ok( "sample order #{$keep[$l]} belongs to $l", $sample && (int) $sample->get_customer_id() === (int) $u->ID );
	if ( ! $sample || (int) $sample->get_customer_id() !== (int) $u->ID ) { continue; }
	foreach ( wc_get_orders( array( 'customer_id' => $u->ID, 'limit' => -1, 'status' => 'any' ) ) as $o ) {
		if ( in_array( $o->get_id(), $keep_ids, true ) ) { continue; }
		foreach ( $o->get_items() as $it ) { $k = $it->get_meta( '_nbd' ); if ( $k && preg_match( '/^[A-Za-z0-9_\-]+$/', $k ) ) { gp_ds_rm( NBDESIGNER_CUSTOMER_DIR . '/' . $k ); } }
		echo '  deleted order #' . $o->get_id() . " of $l\n";
		$o->delete( true );
	}
	if ( 'completed' !== $sample->get_status() ) { $sample->update_status( 'completed', 'Demo sample order completed. ' ); }
	gp_ds_ok( "$l has exactly one order, completed", 1 === count( wc_get_orders( array( 'customer_id' => $u->ID, 'limit' => -1, 'status' => 'any', 'return' => 'ids' ) ) ) && 'completed' === wc_get_order( $keep[ $l ] )->get_status() );
}
// fixture quotes ('GP test quote') and the orders they created
foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}pcs_quotes WHERE title = 'GP test quote'" ) as $qid ) {
	$oid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT wc_order_id FROM {$wpdb->prefix}pcs_quotes WHERE id = %d", $qid ) );
	if ( $oid && ! in_array( $oid, $keep_ids, true ) && ( $o = wc_get_order( $oid ) ) ) { $o->delete( true ); echo "  deleted quote order #$oid\n"; }
	PCS_Quotes::delete( (int) $qid ); echo "  deleted fixture quote #$qid\n";
}

echo "-- company Demo Signs Co\n";
$store = gp_ds_store( 'Demo Signs Co' );
$owner = get_user_by( 'login', 'gp_test_b2b' );
gp_ds_ok( 'company Demo Signs Co exists', (bool) $store );
if ( $store && $owner ) {
	$sid = (int) $store->id;
	// Image Zone image
	$have = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Images::table() . ' WHERE store_id = %d', $sid ) );
	if ( 0 === $have ) {
		$src = ABSPATH . 'wp-content/uploads/2025/12/logo.png';
		$tmp = '/tmp/gp-demo-logo-' . getmypid() . '.png';
		gp_ds_ok( 'sample logo source exists', is_file( $src ) && copy( $src, $tmp ) );
		$item = PCS_Company_Images::store_file( $tmp, 'Demo logo.png', $sid, $owner->ID );
		@unlink( $tmp );
		gp_ds_ok( 'Image Zone image stored', is_array( $item ) && ! empty( $item['id'] ) );
	} else { echo "  Image Zone already holds $have image(s)\n"; }
	gp_ds_ok( 'Image Zone has exactly one image', 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Images::table() . ' WHERE store_id = %d', $sid ) ) );
	// template made from the design of the b2b sample order
	$has_tpl = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Templates::table() . ' WHERE store_id = %d', $sid ) );
	if ( 0 === $has_tpl ) {
		$cfg = null; $thumb = '';
		$o = isset( $keep['gp_test_b2b'] ) ? wc_get_order( $keep['gp_test_b2b'] ) : null;
		if ( $o ) {
			foreach ( $o->get_items() as $it ) {
				$k = $it->get_meta( '_nbd' ); if ( ! $k ) { continue; }
				$dir = NBDESIGNER_CUSTOMER_DIR . '/' . $k;
				$dj  = json_decode( (string) @file_get_contents( $dir . '/design.json' ), true );
				if ( is_array( $dj ) ) {
					$cfg = array( 'v' => 1, 'product_id' => 1168, 'qty' => 1, 'options' => new stdClass(), 'stages' => array_values( $dj ), 'dims' => null, 'artMode' => null );
					if ( is_file( $dir . '/frame_0.png' ) && class_exists( 'Imagick' ) ) {
						try {
							$im = new Imagick( $dir . '/frame_0.png' );
							$im->setImageBackgroundColor( 'white' ); $im = $im->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
							$im->thumbnailImage( 240, 240, true ); $im->setImageFormat( 'jpeg' ); $im->setImageCompressionQuality( 70 );
							$thumb = 'data:image/jpeg;base64,' . base64_encode( $im->getImageBlob() ); $im->clear();
						} catch ( Exception $e ) { $thumb = ''; }
					}
				}
				break;
			}
		}
		gp_ds_ok( 'design of the b2b sample order available for the template', is_array( $cfg ) );
		if ( is_array( $cfg ) ) {
			$t = PCS_Company_Templates::save( $sid, $owner->ID, 'Demo banner template', 1168, wp_json_encode( $cfg ), $thumb );
			gp_ds_ok( 'template saved', is_array( $t ) && ! empty( $t['id'] ) );
		}
	} else { echo "  company already has $has_tpl template(s)\n"; }
	gp_ds_ok( 'company has exactly one template', 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . PCS_Company_Templates::table() . ' WHERE store_id = %d', $sid ) ) );
	// private product
	PCS_Private_Products::assign( $sid, 1263 );
	gp_ds_ok( 'private product 1263 assigned to Demo Signs Co', in_array( 1263, array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT product_id FROM ' . PCS_Private_Products::table() . ' WHERE store_id = %d', $sid ) ) ), true ) );
}

echo "-- carts and sessions\n";
foreach ( GP_DS_USERS as $l ) {
	$u = get_user_by( 'login', $l );
	if ( ! $u ) { continue; }
	delete_user_meta( $u->ID, '_woocommerce_persistent_cart_' . get_current_blog_id() );
	$wpdb->delete( $wpdb->prefix . 'woocommerce_sessions', array( 'session_key' => (string) $u->ID ) );
	delete_user_meta( $u->ID, 'session_tokens' );
	$c = get_user_meta( $u->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
	gp_ds_ok( "$l cart empty", empty( $c ) );
}
delete_transient( 'pcs_private_product_ids' );
echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS\n";
exit( $fail ? 1 : 0 );
