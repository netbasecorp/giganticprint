<?php
/**
 * B3 pricing checks: ONE tier discount, always from the RAW unit price (read-only, no side effects).
 *   scripts/data/run.sh b3-pricing-check.php
 *
 * Unit-tests the pure pieces of the cart pricing (no browser, no WC cart session):
 *   - PCS_Pricing::discounted_unit_price( $raw, $user_id, $qty ) for gp_test_b2c (0%), gp_test_b2b (10%),
 *     gp_test_reseller (20%) -> reference numbers, rounding, idempotency
 *   - PCS_Pricing::apply_tier_price() leaves a product flagged as a cart item (NBDesigner) alone, and still
 *     discounts a plain product; get_price() of a flagged product returns the price that was set (once discounted)
 *
 * Reference unit prices (retail -> 10% -> 20%): 17.68 -> 15.91 -> 14.14, 25.00 -> 22.50 -> 20.00,
 * 31.68 -> 28.51 -> 25.34. Needs no bulk rule with min qty 1 (none exist). The current user is switched in memory
 * only and reset at the end. Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b3-pricing-check' );

$GLOBALS['gp_b3p_fail'] = 0;

function gp_b3p_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b3p_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

function gp_b3p_eq( $label, $got, $want ) {
	gp_b3p_assert( $label, abs( (float) $got - (float) $want ) < 0.00001, 'got ' . var_export( $got, true ) . ' want ' . var_export( $want, true ) );
}

GP_Data::say( '-- 0. prerequisites' );
$have_class = class_exists( 'PCS_Pricing' )
	&& method_exists( 'PCS_Pricing', 'discounted_unit_price' )
	&& method_exists( 'PCS_Pricing', 'flag_cart_product' );
gp_b3p_assert( 'PCS_Pricing::discounted_unit_price / flag_cart_product exist', $have_class );
$users = array(
	'gp_test_b2c'      => get_user_by( 'login', 'gp_test_b2c' ),
	'gp_test_b2b'      => get_user_by( 'login', 'gp_test_b2b' ),
	'gp_test_reseller' => get_user_by( 'login', 'gp_test_reseller' ),
);
gp_b3p_assert( 'test users exist', $users['gp_test_b2c'] && $users['gp_test_b2b'] && $users['gp_test_reseller'] );
gp_b3p_assert( 'pcs_apply_in_cart is not disabled', get_option( 'pcs_apply_in_cart', '1' ) !== '0' );

if ( $have_class && $users['gp_test_b2c'] && $users['gp_test_b2b'] && $users['gp_test_reseller'] ) {
	$orig_user = get_current_user_id();
	$b2c  = (int) $users['gp_test_b2c']->ID;
	$b2b  = (int) $users['gp_test_b2b']->ID;
	$res  = (int) $users['gp_test_reseller']->ID;

	GP_Data::say( '-- 1. tier discounts' );
	gp_b3p_eq( 'gp_test_b2c discount 0', PCS_Pricing::get_effective_discount( $b2c ), 0 );
	gp_b3p_eq( 'gp_test_b2b discount 10', PCS_Pricing::get_effective_discount( $b2b ), 10 );
	gp_b3p_eq( 'gp_test_reseller discount 20', PCS_Pricing::get_effective_discount( $res ), 20 );
	gp_b3p_eq( 'no bulk extra at qty 1', PCS_Pricing::get_bulk_discount_for_qty( 1 ), 0 );

	GP_Data::say( '-- 2. discounted_unit_price reference numbers' );
	$refs = array(
		'13oz banner 24x16'          => array( 17.68, 17.68, 15.91, 14.14 ),
		'acrylic 4x4 (base 25.00)'   => array( 25.00, 25.00, 22.50, 20.00 ),
		'banner + pole pocket'       => array( 31.68, 31.68, 28.51, 25.34 ),
	);
	foreach ( $refs as $name => $r ) {
		gp_b3p_eq( "$name retail", PCS_Pricing::discounted_unit_price( $r[0], $b2c, 1 ), $r[1] );
		gp_b3p_eq( "$name tier 10%", PCS_Pricing::discounted_unit_price( $r[0], $b2b, 1 ), $r[2] );
		gp_b3p_eq( "$name reseller 20%", PCS_Pricing::discounted_unit_price( $r[0], $res, 1 ), $r[3] );
	}
	gp_b3p_eq( 'logged-out user: no discount', PCS_Pricing::discounted_unit_price( 17.68, 0, 1 ), 17.68 );
	gp_b3p_eq( 'zero price stays zero', PCS_Pricing::discounted_unit_price( 0, $b2b, 1 ), 0 );

	GP_Data::say( '-- 3. idempotency (always computed from the raw price)' );
	$raw = 31.68;
	$a   = PCS_Pricing::discounted_unit_price( $raw, $b2b, 3 );
	$b   = PCS_Pricing::discounted_unit_price( $raw, $b2b, 3 );
	$c   = PCS_Pricing::discounted_unit_price( $raw, $b2b, 3 );
	gp_b3p_assert( 'same raw -> same result on repeated calls', $a === $b && $b === $c, "$a / $b / $c" );
	gp_b3p_eq( 'qty 3 (no bulk rule) equals qty 1', $a, PCS_Pricing::discounted_unit_price( $raw, $b2b, 1 ) );
	gp_b3p_assert( 'result is rounded to the shop decimals', abs( $a * pow( 10, wc_get_price_decimals() ) - round( $a * pow( 10, wc_get_price_decimals() ) ) ) < 0.0000001 );

	GP_Data::say( '-- 4. apply_tier_price vs NBDesigner cart items' );
	wp_set_current_user( $b2b );
	$plain = new WC_Product_Simple();
	$plain->set_price( 25 );
	gp_b3p_eq( 'plain product still discounted by the filter (25 -> 22.50)', PCS_Pricing::apply_tier_price( 25, $plain ), 22.5 );

	$cart_item_product = new WC_Product_Simple();
	$cart_item_product->set_price( 25 );
	gp_b3p_assert( 'not flagged before', ! PCS_Pricing::is_cart_product( $cart_item_product ) );
	// simulate core NBDesigner session load: cart item with nbo_meta -> flagged by the 20-priority filter
	$item = apply_filters( 'woocommerce_get_cart_item_from_session', array( 'nbo_meta' => array( 'price' => 25 ), 'data' => $cart_item_product ), array() );
	gp_b3p_assert( 'item with nbo_meta is flagged as cart product', PCS_Pricing::is_cart_product( $cart_item_product ) );
	gp_b3p_eq( 'flagged product is NOT discounted by apply_tier_price', PCS_Pricing::apply_tier_price( 25, $cart_item_product ), 25 );
	gp_b3p_assert( 'a different (unflagged) product object is still discounted', ! PCS_Pricing::is_cart_product( $plain ) );

	$cart_item_product->set_price( PCS_Pricing::discounted_unit_price( 25, $b2b, 1 ) );
	gp_b3p_eq( 'cart unit price after set_price: get_price() = 22.50 (not 20.25)', $cart_item_product->get_price(), 22.5 );
	$cart_item_product->set_price( PCS_Pricing::discounted_unit_price( 25, $b2b, 1 ) );
	gp_b3p_eq( 'recalculating from raw again: still 22.50', $cart_item_product->get_price(), 22.5 );
	gp_b3p_eq( 'plain product get_price() still goes through the filter', $plain->get_price(), 22.5 );

	wp_set_current_user( $orig_user );
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b3p_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b3p_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b3p_fail'] ? 1 : 0 );
