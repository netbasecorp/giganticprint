<?php
/**
 * B1 data changes: pricing + catalog (idempotent, backup of every touched value).
 *   scripts/data/run.sh b1-pricing-catalog.php [--dry-run|--apply|--restore=<backup.json>]
 *
 * 1. NBDesigner option set #86 (13oz banner): the 1000+ sq ft tier started at "1000" sq in (6.94 sq ft) instead of "144000".
 * 2. Option sets with a hole in the measurement range (HDPE "43999" typo -> "143999"); every other gap is only reported.
 * 3. Acrylic: "No Rounded Corners" becomes the default (the $5 setup fee is an add-on).
 * 4. WooCommerce price format US style: $1,234.56.
 * 5. Vinyl products: designer unit "ft" -> "in" (ranges are per square inch; with ft the designer could not start).
 * 6. Category menu in Signs365 order (Banner, Rigid, Adhesive, Handheld, Magnet, Apparel, Misc), VINIL -> Adhesive.
 * 7. B2B tiers: returning 5 / b2b_a 10 / reseller 20 (old b2b_b 15, b2b_c 20, vip 24 deactivated, users remapped).
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b1-pricing-catalog' );
global $wpdb;
$T_SETS  = $wpdb->prefix . 'nbdesigner_options';
$T_RULES = $wpdb->prefix . 'pcs_pricing_rules';

/** Edit the unserialized `fields` of an option set with $fn(&$f); aborts that set when the serialization does not round-trip. */
function gp_nbo_edit( $set_id, $label, $fn ) {
	global $wpdb, $T_SETS;
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT fields FROM $T_SETS WHERE id = %d", $set_id ) );
	if ( null === $raw ) { GP_Data::say( "  set $set_id: NOT FOUND" ); return; }
	$f = maybe_unserialize( $raw );
	if ( ! is_array( $f ) || serialize( $f ) !== $raw ) { GP_Data::say( "  set $set_id: serialization does not round-trip, SKIPPED" ); return; }
	$before = serialize( $f );
	$fn( $f );
	if ( serialize( $f ) === $before ) { GP_Data::say( "  set $set_id $label: unchanged" ); return; }
	GP_Data::row_update( $T_SETS, array( 'id' => $set_id ), array( 'fields' => serialize( $f ), 'modified' => current_time( 'mysql' ) ), $label );
}

/* 1 + 2: measurement ranges ------------------------------------------------ */
GP_Data::say( '-- 1. 13oz banner range (set 86)' );
gp_nbo_edit( 86, '13oz 1000+ sq ft tier starts at 144000 sq in', function ( &$f ) {
	foreach ( $f['fields'] as &$fl ) {
		if ( 'f1787047001083' === $fl['id'] && isset( $fl['general']['mesure_range'][1] ) ) {
			$r = &$fl['general']['mesure_range'][1];
			if ( '1000' === (string) $r[0] ) { $r[0] = '144000'; $r[1] = ''; }
		}
	}
} );

GP_Data::say( '-- 2. gaps in measurement ranges' );
$sets = $wpdb->get_results( "SELECT id, title, fields FROM $T_SETS WHERE published = 1", ARRAY_A );
foreach ( $sets as $s ) {
	$f = maybe_unserialize( $s['fields'] );
	if ( ! is_array( $f ) || empty( $f['fields'] ) ) { continue; }
	foreach ( $f['fields'] as $fl ) {
		if ( empty( $fl['general']['mesure_range'] ) || ! is_array( $fl['general']['mesure_range'] ) ) { continue; }
		$rows = $fl['general']['mesure_range'];
		for ( $i = 0; $i < count( $rows ) - 1; $i++ ) {
			if ( '' !== (string) $rows[ $i ][1] && floatval( $rows[ $i + 1 ][0] ) > floatval( $rows[ $i ][1] ) + 1 ) {
				GP_Data::say( "  GAP set {$s['id']} ({$s['title']}) field {$fl['id']}: row $i ends {$rows[$i][1]}, row " . ( $i + 1 ) . " starts {$rows[$i+1][0]}" );
			}
		}
	}
}
foreach ( array( 78, 105 ) as $sid ) {
	gp_nbo_edit( $sid, 'HDPE range typo 43999 -> 143999', function ( &$f ) {
		foreach ( $f['fields'] as &$fl ) {
			if ( isset( $fl['general']['mesure_range'][0][1] ) && '43999' === (string) $fl['general']['mesure_range'][0][1] ) { $fl['general']['mesure_range'][0][1] = '143999'; }
		}
	} );
}

/* 3: acrylic default ----------------------------------------------------------- */
GP_Data::say( '-- 3. acrylic default: no rounded corners' );
gp_nbo_edit( 81, 'default = No Rounded Corners', function ( &$f ) {
	foreach ( $f['fields'] as &$fl ) {
		if ( 'f1700000010005' !== $fl['id'] ) { continue; }
		foreach ( $fl['general']['attributes']['options'] as $k => &$op ) {
			if ( preg_match( '/^no rounded/i', $op['name'] ) ) { $op['selected'] = 'on'; } else { unset( $op['selected'] ); }
		}
	}
} );

/* 4: price format ------------------------------------------------------------------ */
GP_Data::say( '-- 4. US price format' );
GP_Data::option_set( 'woocommerce_currency_pos', 'left' );
GP_Data::option_set( 'woocommerce_price_thousand_sep', ',' );
GP_Data::option_set( 'woocommerce_price_decimal_sep', '.' );

/* 5: vinyl unit ----------------------------------------------------------------------- */
GP_Data::say( '-- 5. designer unit ft -> in' );
$pids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nbdesigner_option'" );
foreach ( $pids as $pid ) {
	$val = get_post_meta( $pid, '_nbdesigner_option', true );
	$arr = maybe_unserialize( $val );
	if ( ! is_array( $arr ) || ! isset( $arr['unit'] ) || 'ft' !== $arr['unit'] ) { continue; }
	if ( serialize( $arr ) !== $val ) { GP_Data::say( "  product $pid: meta does not round-trip, SKIPPED" ); continue; }
	$arr['unit'] = 'in';
	GP_Data::meta_set( (int) $pid, '_nbdesigner_option', serialize( $arr ) );
}

/* 6: categories ------------------------------------------------------------------------ */
GP_Data::say( '-- 6. category menu order' );
GP_Data::term_update( 39, 'product_cat', 'Banner', 'banner', 1 );
GP_Data::term_update( 46, 'product_cat', 'Rigid', 'rigid', 2 );
GP_Data::term_update( 21, 'product_cat', 'Adhesive', 'adhesive', 3 );
GP_Data::term_create( 'product_cat', 'Handheld', 'handheld', 4 );
GP_Data::term_update( 56, 'product_cat', 'Magnet', 'magnet', 5 );
GP_Data::term_update( 73, 'product_cat', 'Apparel', 'apparel', 6 );
GP_Data::term_create( 'product_cat', 'Misc', 'misc', 7 );

/* 7: tiers ----------------------------------------------------------------------------------- */
GP_Data::say( '-- 7. B2B tiers' );
GP_Data::row_update( $T_RULES, array( 'target_value' => 'b2b_a', 'rule_type' => 'tier' ), array( 'name' => 'B2B (10%)', 'discount_value' => '10.0000' ), 'b2b_a' );
foreach ( array( 'b2b_b', 'b2b_c', 'vip' ) as $old ) {
	GP_Data::row_update( $T_RULES, array( 'target_value' => $old, 'rule_type' => 'tier' ), array( 'status' => 'inactive' ), "$old deactivated" );
}
$new_tiers = array(
	'returning' => array( 'name' => 'Returning customer (5%)', 'disc' => '5.0000',  'terms' => 'prepay', 'free_ship' => '200.00', 'prio' => 95 ),
	'reseller'  => array( 'name' => 'Reseller (20%)',          'disc' => '20.0000', 'terms' => 'net30',  'free_ship' => '0.00',   'prio' => 85 ),
);
foreach ( $new_tiers as $key => $t ) {
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $T_RULES WHERE rule_type = 'tier' AND target_value = %s", $key ) );
	if ( $exists ) {
		GP_Data::row_update( $T_RULES, array( 'id' => $exists ), array( 'name' => $t['name'], 'discount_value' => $t['disc'], 'status' => 'active', 'priority' => $t['prio'] ), $key );
		continue;
	}
	GP_Data::row_insert( $T_RULES, array(
		'name' => $t['name'], 'rule_type' => 'tier', 'target_type' => 'tier', 'target_value' => $key, 'discount_type' => 'percentage',
		'discount_value' => $t['disc'], 'min_order' => '0.00', 'payment_terms' => $t['terms'], 'free_shipping_threshold' => $t['free_ship'],
		'promo_code' => '', 'status' => 'active', 'priority' => $t['prio'], 'created_at' => current_time( 'mysql' ),
	), 'id', $key );
}
GP_Data::option_set( 'pcs_tiers', array(
	'b2c'       => array( 'label' => 'B2C',                 'discount' => 0,  'color' => '#6b7280', 'min_spend_upgrade' => 0 ),
	'returning' => array( 'label' => 'Returning customer',  'discount' => 5,  'color' => '#065f46', 'min_spend_upgrade' => 0 ),
	'b2b_a'     => array( 'label' => 'B2B',                 'discount' => 10, 'color' => '#2271b1', 'min_spend_upgrade' => 0 ),
	'reseller'  => array( 'label' => 'Reseller',            'discount' => 20, 'color' => '#3730a3', 'min_spend_upgrade' => 0 ),
) );
// users on a retired tier move to the closest live one
$map = array( 'b2b_b' => 'b2b_a', 'b2b_c' => 'reseller', 'vip' => 'reseller' );
$um  = $wpdb->get_results( "SELECT umeta_id, user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = '_pcs_tier' AND meta_value IN ('b2b_b','b2b_c','vip')", ARRAY_A );
foreach ( $um as $u ) {
	GP_Data::row_update( $wpdb->usermeta, array( 'umeta_id' => $u['umeta_id'] ), array( 'meta_value' => $map[ $u['meta_value'] ] ), "user {$u['user_id']} tier" );
}
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%pcs\\_tiers\\_cache%'" );

if ( GP_Data::writing() && function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); GP_Data::say( '  WP Rocket cache cleared' ); }
GP_Data::finish();
