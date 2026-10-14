<?php
/**
 * Read-only price check: runs the REAL NBDesigner server calculation (option_processing, the same code the cart uses)
 * for a set of cases and compares with the customer's price sheet of 2026-06-18 (retail, qty 1).
 *   scripts/data/run.sh price-check.php [--all] [--json]
 * Exit code 1 when a strict case fails.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'price-check' );

// pre-deploy verification: load the repo copy of the linear pricing class when the live plugin does not have it yet
$gp_repo_class = ( getenv( 'GP_REPO' ) ?: '/srv/gp-dev/giganticprint' ) . '/plugins/cmsmart-ui-override/includes/class-gp-linear-pricing.php';
if ( ! class_exists( 'GP_Linear_Pricing' ) && is_readable( $gp_repo_class ) ) { require_once $gp_repo_class; echo "(using repo GP_Linear_Pricing)
"; }
$nbo  = NBD_FRONTEND_PRINTING_OPTIONS::instance();
$json = in_array( '--json', GP_Data::$arg, true );

/** Build the flat fields array (field id => value) for a product's option set with overrides. */
function gp_pc_fields( $nbo, $row, $ov ) {
	$f = maybe_unserialize( nbd_is_base64_string( $row['fields'] ) ? base64_decode( $row['fields'] ) : $row['fields'] );
	$f = NBD_FRONTEND_PRINTING_OPTIONS::recursive_stripslashes( $f );
	$fields = array();
	foreach ( $f['fields'] as $fl ) {
		$g = $fl['general'];
		if ( 'y' !== $g['enabled'] ) { continue; }
		$title = $g['title'];
		if ( isset( $fl['nbd_type'] ) && 'dimension' === $fl['nbd_type'] ) {
			$w = isset( $ov['size'] ) ? $ov['size'][0] : $g['default_width'];
			$h = isset( $ov['size'] ) ? $ov['size'][1] : $g['default_height'];
			$fields[ $fl['id'] ] = $w . 'x' . $h;
		} elseif ( 'm' === $g['data_type'] ) {
			$val = 0;
			foreach ( $g['attributes']['options'] as $k => $op ) { if ( isset( $op['selected'] ) && 'on' === $op['selected'] ) { $val = $k; } }
			foreach ( $ov as $pat => $idx ) {
				if ( 'size' === $pat || 'qty' === $pat ) { continue; }
				if ( preg_match( '/' . $pat . '/i', $title ) ) { $val = $idx; }
			}
			$fields[ $fl['id'] ] = (string) $val;
		} else {
			$fields[ $fl['id'] ] = isset( $g['input_option']['min'] ) ? $g['input_option']['min'] : '0';
		}
	}
	// drop fields hidden by conditional logic
	$fields = $nbo->validate_before_processing( $f, $fields );
	return $fields;
}

function gp_pc_price( $nbo, $pid, $ov ) {
	$p   = wc_get_product( $pid );
	$oid = NBD_FRONTEND_PRINTING_OPTIONS::get_product_option( $pid );
	$row = NBD_FRONTEND_PRINTING_OPTIONS::get_option( $oid );
	$fields = gp_pc_fields( $nbo, $row, $ov );
	$qty = isset( $ov['qty'] ) ? (int) $ov['qty'] : 1;
	if ( class_exists( 'GP_Linear_Pricing' ) ) { $row = GP_Linear_Pricing::patch_options( $row, $fields ); }
	$r = $nbo->option_processing( $row, (float) $p->get_price( 'edit' ), $fields, $qty, null, $p );
	return round( (float) $p->get_price( 'edit' ) + $r['total_price'] - $r['discount_price'], 2 );
}

$P = array( 'banner' => 1168, 'canvas' => 1149, 'hdpe' => 1156, 'poster' => 1160, 'nocurl' => 1164, 'mesh' => 1185,
	'cor' => 1193, 'acr' => 1208, 'pvc' => 1212, 'alu' => 1243, 'jb' => 1251, 'foam' => 1263,
	'vinyl3m' => 1271, 'wrap' => 1275, 'cling' => 1279, 'gf' => 1284, 'orajet' => 1292, 'perf' => 1300, 'refl' => 1305,
	'lowtac' => 1309, 'dry' => 1313, 'foot' => 1317, 'boot' => 1321, 'magnet' => 1342, 'film' => 1346 );

// [label, product key, overrides, expected (null = informational), strict]
$cases = array(
	array( '13oz 12x12',            'banner', array( 'size' => array( 12, 12 ) ), 6.63, true ),
	array( '13oz 24x16',            'banner', array( 'size' => array( 24, 16 ) ), 17.68, true ),
	array( '13oz 36x24 (6 sqft)',   'banner', array( 'size' => array( 36, 24 ) ), 39.78, true ),
	array( '13oz 36x30 (7.5 sqft)', 'banner', array( 'size' => array( 36, 30 ) ), 49.73, true ),
	array( '13oz 48x24 (8 sqft)',   'banner', array( 'size' => array( 48, 24 ) ), 53.04, true ),
	array( '13oz 60x60 (25 sqft)',  'banner', array( 'size' => array( 60, 60 ) ), 165.75, true ),
	array( '13oz 60x1200 (500)',    'banner', array( 'size' => array( 60, 1200 ) ), 3315.00, true ),
	array( '15oz 24x16',            'banner', array( 'size' => array( 24, 16 ), 'Print-Sided' => 1 ), 21.63, true ),
	array( '18oz SS 24x16',         'banner', array( 'size' => array( 24, 16 ), 'Print-Sided' => 2 ), 29.68, true ),
	array( '18oz DS 24x16',         'banner', array( 'size' => array( 24, 16 ), 'Print-Sided' => 3 ), 51.01, true ),
	array( '13oz 24x16 + pole pocket Top&Bottom', 'banner', array( 'size' => array( 24, 16 ), 'Pole Pocket' => 3 ), 17.68 + 14.00, true ),
	array( '13oz 48x24 + pole pocket Top&Bottom', 'banner', array( 'size' => array( 48, 24 ), 'Pole Pocket' => 3 ), 53.04 + 18.00, true ),
	array( '13oz 48x24 + pole pocket Top only',   'banner', array( 'size' => array( 48, 24 ), 'Pole Pocket' => 1 ), 53.04 + 14.00, true ),
	array( '13oz 24x16 + rope',     'banner', array( 'size' => array( 24, 16 ), 'Rope' => 1 ), 17.68 + 6.67, true ),
	array( '13oz 24x16 + wind slits (informational)', 'banner', array( 'size' => array( 24, 16 ), 'Wind Slits' => 1 ), null, false ),
	array( '13oz 24x16 + rush 100%', 'banner', array( 'size' => array( 24, 16 ), 'Rush' => 1 ), 35.36, true ),
	array( '13oz 24x16 + pole T&B + rope + rush', 'banner', array( 'size' => array( 24, 16 ), 'Pole Pocket' => 3, 'Rope' => 1, 'Rush' => 1 ), round( ( 17.68 + 14.00 + 6.67 ) * 2, 2 ), true ),
	array( 'Canvas 12x12',          'canvas', array( 'size' => array( 12, 12 ) ), 14.94, true ),
	array( 'HDPE 12x12',            'hdpe',   array( 'size' => array( 12, 12 ) ), 4.50, true ),
	array( 'HDPE 60x700 (291.7 sqft)', 'hdpe', array( 'size' => array( 60, 700 ) ), round( 60 * 700 / 144 * 4.50, 2 ), true ),
	array( 'HDPE 60x800 (333 sqft)', 'hdpe',  array( 'size' => array( 60, 800 ) ), round( 60 * 800 / 144 * 4.50, 2 ), true ),
	array( 'Poster 12x12',          'poster', array( 'size' => array( 12, 12 ) ), 6.00, true ),
	array( 'NoCurl 12x12',          'nocurl', array( 'size' => array( 12, 12 ) ), 9.00, true ),
	array( 'Mesh 12x12',            'mesh',   array( 'size' => array( 12, 12 ) ), 10.98, true ),
	array( 'Acrylic 4x4 (min 25)',  'acr',    array( 'size' => array( 4, 4 ), 'Rounded' => 1 ), 25.00, true ),
	array( 'Acrylic 10x10',         'acr',    array( 'size' => array( 10, 10 ), 'Rounded' => 1 ), 30.00, true ),
	array( 'Magnet default (72x24)', 'magnet', array(), 224.25, true ),
	array( 'Transfer film 22x12',   'film',   array( 'size' => array( 22, 12 ) ), 18.00, true ),
	array( 'Vinyl 3M IJ-35C 12x12 (1 sqft)', 'vinyl3m', array( 'size' => array( 12, 12 ), 'Laminate' => 2 ), 8.97, true ),
	array( 'Vinyl 3M IJ-35C 24x24 (4 sqft)', 'vinyl3m', array( 'size' => array( 24, 24 ), 'Laminate' => 2 ), 35.88, true ),
	array( 'Controltac wrap 12x12',  'wrap',   array( 'size' => array( 12, 12 ), 'Laminate' => 2 ), 14.97, false ),
	array( 'Window cling 12x12',     'cling',  array( 'size' => array( 12, 12 ), 'Laminate' => 2 ), 8.64, false ),
	array( 'Dry erase 12x12',        'dry',    array( 'size' => array( 12, 12 ) ), 10.50, false ),
	array( 'Bootprints 12x12',       'boot',   array( 'size' => array( 12, 12 ) ), 37.50, false ),
);

$fail = 0; $out = array();
foreach ( $cases as $c ) {
	list( $label, $key, $ov, $exp, $strict ) = $c;
	try { $got = gp_pc_price( $nbo, $P[ $key ], $ov ); } catch ( Throwable $e ) { $got = 'ERR ' . $e->getMessage(); }
	$ok = null === $exp ? 'info' : ( is_numeric( $got ) && abs( $got - $exp ) < 0.011 ? 'OK' : 'FAIL' );
	if ( 'FAIL' === $ok && $strict ) { $fail++; }
	$out[] = array( 'case' => $label, 'got' => $got, 'expected' => $exp, 'result' => $ok );
	if ( ! $json ) { printf( "%-48s got %-10s expected %-10s %s\n", $label, is_numeric( $got ) ? number_format( $got, 2, '.', '' ) : $got, null === $exp ? '-' : number_format( $exp, 2, '.', '' ), $ok ); }
}
if ( in_array( '--all', GP_Data::$arg, true ) ) {
	echo "\n-- default configuration of every product --\n";
	foreach ( $P as $k => $pid ) { printf( "%-8s #%d  %s\n", $k, $pid, gp_pc_price( $nbo, $pid, array() ) ); }
}
if ( $json ) { echo json_encode( $out ) . "\n"; }
echo "strict failures: $fail\n";
exit( $fail ? 1 : 0 );
