<?php
/**
 * B4 one-click reorder check (read-only unless --apply).
 *   scripts/data/run.sh b4-reorder-check.php [--dry-run|--apply]
 *
 * --apply only (creates and ALWAYS removes everything it created):
 *   - a pending order for gp_test_b2c with a banner line (product 1168, qty 2) carrying fake printing-option
 *     meta written the way NBDesigner's order_line_item() saves it (_nbo_option_price, _nbo_field,
 *     _nbo_options, _nbo_original_price) + _nbd pointing to a stub design folder, and a line of a deleted product
 *   - PCS_Reorder::user_can_reorder (owner yes, guest/other user no)
 *   - PCS_Reorder::build_cart_item_data (pure): nbo_meta values + a NEW design key whose folder is a copy
 *   - PCS_Reorder::add_order_to_cart in CLI (wc_load_cart): cart line has nbo_meta with the same field values,
 *     the cart image, qty 2, a design key different from the order's, the old folder untouched; the deleted
 *     product line is reported as skipped
 *
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b4-reorder-check' );

$GLOBALS['gp_b4_fail'] = 0;

function gp_b4_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b4_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b4_val( $v ) {
	return is_scalar( $v ) || null === $v ? var_export( $v, true ) : wp_json_encode( $v );
}

function gp_b4_rmdir( $dir ) {
	if ( '' === $dir || ! is_dir( $dir ) ) { return; }
	foreach ( scandir( $dir ) as $f ) {
		if ( '.' === $f || '..' === $f ) { continue; }
		$p = $dir . '/' . $f;
		if ( is_dir( $p ) ) { gp_b4_rmdir( $p ); } else { @unlink( $p ); }
	}
	@rmdir( $dir );
}

$order_id = 0;
$old_key  = 'gpb4chk' . strtolower( wp_generate_password( 8, false ) );
$dirs     = array(); // every design folder to remove at the end
$user     = null;

try {

	if ( ! GP_Data::writing() ) {
		GP_Data::say( '-- SKIP (needs --apply: creates and removes a throw-away order, design folders and cart lines)' );
	} elseif ( ! class_exists( 'PCS_Reorder' ) || ! function_exists( 'WC' ) ) {
		gp_b4_assert( 'PCS_Reorder + WooCommerce loaded', false );
	} elseif ( ! defined( 'NBDESIGNER_CUSTOMER_DIR' ) ) {
		gp_b4_assert( 'NBDESIGNER_CUSTOMER_DIR defined (NBDesigner active)', false );
	} else {
		$user    = get_user_by( 'login', 'gp_test_b2c' );
		$other   = get_user_by( 'login', 'gp_test_reseller' );
		$product = wc_get_product( 1168 );
		gp_b4_assert( 'gp_test_b2c exists and product 1168 is purchasable', $user && $product && $product->is_purchasable() );

		if ( $user && $product && $product->is_purchasable() ) {
			/* ---- fixture: design folder stub + order ---- */
			$old_dir = NBDESIGNER_CUSTOMER_DIR . '/' . $old_key;
			$dirs[]  = $old_dir;
			wp_mkdir_p( $old_dir . '/preview' );
			file_put_contents( $old_dir . '/config.json', '{"gp":"b4-reorder-check"}' );
			file_put_contents( $old_dir . '/preview/stub.txt', 'art' );
			$img = 'https://example.invalid/gp-b4-art-preview.jpg';

			$fields       = array( 'f_size' => '48x96', 'f_pole' => '1' );
			$option_price = array(
				'fields'         => array(
					array( 'id' => 'f_size', 'name' => 'Size', 'val' => '48x96', 'value_name' => '48x96', 'price' => 0, 'published' => 'y' ),
					array( 'id' => 'f_pole', 'name' => 'Pole Pockets', 'val' => '1', 'value_name' => 'Top & Bottom', 'price' => 0, 'published' => 'y' ),
				),
				'total_price'    => 0,
				'discount_price' => 0,
				'cart_image'     => $img,
				'manual_pm'      => false,
			);
			$options = array( 'id' => 0, 'title' => 'GP B4 check', 'fields' => base64_encode( serialize( array( 'fields' => array() ) ) ) );

			$order = wc_create_order( array( 'customer_id' => $user->ID, 'status' => 'pending' ) );
			$order_id = $order->get_id();
			$item_id  = $order->add_product( $product, 2 );
			$order->add_item( gp_b4_gone_item() );
			$order->calculate_totals();
			$order->save();

			$item = $order->get_item( $item_id );
			$item->add_meta_data( '_nbo_option_price', $option_price );
			$item->add_meta_data( '_nbo_field', $fields );
			$item->add_meta_data( '_nbo_options', wp_slash( $options ) );
			$item->add_meta_data( '_nbo_original_price', 10.0 );
			$item->add_meta_data( '_nbd', $old_key );
			$item->save();
			$order = wc_get_order( $order_id );
			$item  = $order->get_item( $item_id );
			gp_b4_assert( 'fixture order has 2 line items', 2 === count( $order->get_items() ) );

			/* ---- access ---- */
			GP_Data::say( '-- access' );
			gp_b4_assert( 'owner may reorder', true === PCS_Reorder::user_can_reorder( $order, $user->ID ) );
			gp_b4_assert( 'guest (0) may not reorder', false === PCS_Reorder::user_can_reorder( $order, 0 ) );
			if ( $other ) {
				$shared = gp_b4_share_store( $user->ID, $other->ID );
				if ( $shared ) {
					GP_Data::say( '  SKIP other-user check (gp_test_reseller shares a company store with gp_test_b2c)' );
				} else {
					gp_b4_assert( 'unrelated user may not reorder', false === PCS_Reorder::user_can_reorder( $order, $other->ID ) );
				}
			}

			/* ---- pure builder ---- */
			GP_Data::say( '-- build_cart_item_data (pure)' );
			$missing = true;
			$data    = PCS_Reorder::build_cart_item_data( $item, $order, $missing );
			gp_b4_assert( 'builder returns nbo_meta', isset( $data['nbo_meta'] ) && is_array( $data['nbo_meta'] ) );
			gp_b4_assert( 'nbo_meta.field equals the order values', isset( $data['nbo_meta']['field'] ) && $data['nbo_meta']['field'] === $fields, gp_b4_val( isset( $data['nbo_meta']['field'] ) ? $data['nbo_meta']['field'] : null ) );
			gp_b4_assert( 'nbo_meta.original_price restored', isset( $data['nbo_meta']['original_price'] ) && 10.0 === (float) $data['nbo_meta']['original_price'] );
			gp_b4_assert( 'nbo_meta.option_price.cart_image restored', isset( $data['nbo_meta']['option_price']['cart_image'] ) && $img === $data['nbo_meta']['option_price']['cart_image'] );
			$pure_key = isset( $data['nbd_item_meta_ds']['nbd'] ) ? (string) $data['nbd_item_meta_ds']['nbd'] : '';
			if ( '' !== $pure_key ) { $dirs[] = NBDESIGNER_CUSTOMER_DIR . '/' . $pure_key; }
			gp_b4_assert( 'design key is NEW (old order folder is not shared)', '' !== $pure_key && $pure_key !== $old_key, $pure_key );
			gp_b4_assert( 'new design folder is a copy (config.json)', '' !== $pure_key && is_file( NBDESIGNER_CUSTOMER_DIR . '/' . $pure_key . '/config.json' ) );
			gp_b4_assert( 'art_missing flag is false', false === $missing );

			/* ---- full reorder in CLI ---- */
			GP_Data::say( '-- add_order_to_cart (CLI cart)' );
			wp_set_current_user( $user->ID );
			if ( ! WC()->cart ) { wc_load_cart(); }
			if ( ! WC()->cart ) {
				gp_b4_assert( 'WC()->cart available in CLI', false, 'only the pure builder was tested' );
			} else {
				WC()->cart->empty_cart( false );
				$result = PCS_Reorder::add_order_to_cart( $order );
				gp_b4_assert( 'one line added', 1 === $result['added'], gp_b4_val( $result ) );
				gp_b4_assert( 'deleted-product line reported as skipped', 1 === count( $result['skipped'] ), gp_b4_val( $result['skipped'] ) );
				gp_b4_assert( 'no art_missing report', array() === $result['art_missing'] );

				$line = null;
				foreach ( WC()->cart->get_cart() as $ci ) {
					if ( 1168 === (int) $ci['product_id'] ) { $line = $ci; break; }
				}
				gp_b4_assert( 'cart has the banner line', null !== $line );
				if ( $line ) {
					$cart_key = isset( $line['nbd_item_meta_ds']['nbd'] ) ? (string) $line['nbd_item_meta_ds']['nbd'] : '';
					if ( '' !== $cart_key ) { $dirs[] = NBDESIGNER_CUSTOMER_DIR . '/' . $cart_key; }
					gp_b4_assert( 'quantity 2', 2 === (int) $line['quantity'], gp_b4_val( $line['quantity'] ) );
					gp_b4_assert( 'cart nbo_meta.field has the same values', isset( $line['nbo_meta']['field'] ) && $line['nbo_meta']['field'] === $fields, gp_b4_val( isset( $line['nbo_meta']['field'] ) ? $line['nbo_meta']['field'] : null ) );
					gp_b4_assert( 'cart nbo_meta.option_price.fields keep the selections', isset( $line['nbo_meta']['option_price']['fields'][1]['value_name'] ) && 'Top & Bottom' === $line['nbo_meta']['option_price']['fields'][1]['value_name'] );
					gp_b4_assert( 'cart thumbnail data (cart_image) kept', isset( $line['nbo_meta']['option_price']['cart_image'] ) && $img === $line['nbo_meta']['option_price']['cart_image'] );
					gp_b4_assert( 'cart line has a design key, different from the order', '' !== $cart_key && $cart_key !== $old_key, $cart_key );
					gp_b4_assert( 'cart design folder exists with the art', '' !== $cart_key && is_file( NBDESIGNER_CUSTOMER_DIR . '/' . $cart_key . '/config.json' ) && is_file( NBDESIGNER_CUSTOMER_DIR . '/' . $cart_key . '/preview/stub.txt' ) );
					gp_b4_assert( 'order design folder untouched', is_file( $old_dir . '/config.json' ) && is_file( $old_dir . '/preview/stub.txt' ) );
					$img_html = apply_filters( 'woocommerce_cart_item_thumbnail', '', $line, 'gp-b4' );
					gp_b4_assert( 'cart thumbnail renders the art preview', false !== strpos( (string) $img_html, 'gp-b4-art-preview.jpg' ), substr( (string) $img_html, 0, 120 ) );
				}
			}
		}
	}

} finally {

	/* ALWAYS clean up --------------------------------------------------------- */
	GP_Data::say( '-- cleanup' );
	if ( function_exists( 'WC' ) && WC()->cart ) {
		WC()->cart->empty_cart( false );
	}
	if ( $order_id > 0 ) {
		$o = wc_get_order( $order_id );
		if ( $o ) { $o->delete( true ); }
		GP_Data::say( '  fixture order #' . $order_id . ' deleted' );
	}
	foreach ( array_unique( $dirs ) as $d ) {
		gp_b4_rmdir( $d );
	}
	if ( $dirs ) { GP_Data::say( '  ' . count( array_unique( $dirs ) ) . ' design folder(s) removed' ); }
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b4_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b4_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b4_fail'] ? 1 : 0 );

/** A line item whose product does not exist any more. */
function gp_b4_gone_item() {
	$it = new WC_Order_Item_Product();
	$it->set_product_id( 999999991 );
	$it->set_name( 'GP B4 deleted product' );
	$it->set_quantity( 1 );
	return $it;
}

/** True when both users own / belong to the same company store. */
function gp_b4_share_store( $a, $b ) {
	global $wpdb;
	$ids = array();
	foreach ( array( $a, $b ) as $uid ) {
		$ids[ $uid ] = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}pcs_b2b_stores WHERE user_id = %d UNION SELECT store_id FROM {$wpdb->prefix}pcs_b2b_members WHERE user_id = %d",
			$uid, $uid
		) ) );
	}
	return count( array_intersect( $ids[ $a ], $ids[ $b ] ) ) > 0;
}
