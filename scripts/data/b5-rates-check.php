<?php
/**
 * B5 check: pole pocket / rope rates are editable (option gp_linear_rates) and drive the price.
 *   scripts/data/run.sh b5-rates-check.php
 * Temporarily changes the option and ALWAYS restores it. Prints PASS/FAIL, exit 1 on any failure.
 */
require __DIR__ . '/_lib.php';
$fail = 0;
function gp_rc( $label, $ok, $detail = '' ) { global $fail; echo ( $ok ? '  PASS ' : '  FAIL ' ) . $label . ( $detail && ! $ok ? " ($detail)" : '' ) . "\n"; if ( ! $ok ) { $fail++; } }
$orig_exists = false !== get_option( 'gp_linear_rates', false );
$orig        = get_option( 'gp_linear_rates', null );
try {
	delete_option( 'gp_linear_rates' );
	$r = GP_Linear_Pricing::rates();
	gp_rc( 'defaults $1/ft, $10 setup, rope $1/ft', 1.0 === (float) $r['pole_rate'] && 10.0 === (float) $r['pole_setup'] && 1.0 === (float) $r['rope_rate'], wp_json_encode( $r ) );
	$rules = GP_Linear_Pricing::rules();
	gp_rc( 'rules() carry the default rates', 1.0 === (float) $rules['pole']['rate'] && 10.0 === (float) $rules['pole']['setup'] && 1.0 === (float) $rules['rope']['rate'] );
	$base_pole = GP_Linear_Pricing::compute( 'pole', 24, 16, 'Top & Bottom' );
	$base_rope = GP_Linear_Pricing::compute( 'rope', 24, 16, 'Add rope' );
	gp_rc( 'pole Top & Bottom 24x16 in = 2 x 2 ft x $1 + $10 = $14', is_numeric( $base_pole ) && abs( $base_pole - 14.0 ) < 0.001, var_export( $base_pole, true ) );
	gp_rc( 'rope 24x16 in = perimeter 6.67 ft x $1 = $6.67', is_numeric( $base_rope ) && abs( $base_rope - 6.67 ) < 0.01, var_export( $base_rope, true ) );

	update_option( 'gp_linear_rates', GP_Linear_Pricing::sanitize_rates( array( 'pole_rate' => '2.50', 'pole_setup' => '15', 'rope_rate' => '1.5' ) ) );
	$pole2 = GP_Linear_Pricing::compute( 'pole', 24, 16, 'Top & Bottom' );
	$rope2 = GP_Linear_Pricing::compute( 'rope', 24, 16, 'Add rope' );
	gp_rc( 'changed rates change the pole price (2 x 2 ft x $2.50 + $15 = $25)', is_numeric( $pole2 ) && abs( $pole2 - 25.0 ) < 0.001, var_export( $pole2, true ) );
	gp_rc( 'changed rates change the rope price (6.67 ft x $1.50 = $10.00)', is_numeric( $rope2 ) && abs( $rope2 - 10.0 ) < 0.01, var_export( $rope2, true ) );

	$s = GP_Linear_Pricing::sanitize_rates( array( 'pole_rate' => '-5', 'pole_setup' => 'abc', 'rope_rate' => '99999' ) );
	gp_rc( 'negative / junk / huge values are clamped', 0.0 === (float) $s['pole_rate'] && 0.0 === (float) $s['pole_setup'] && 9999.0 === (float) $s['rope_rate'], wp_json_encode( $s ) );

	gp_rc( 'admin page class loaded and hooked', class_exists( 'GP_Linear_Admin' ) && false !== has_action( 'admin_post_gp_linear_rates_save' ) && false !== has_action( 'admin_menu', array( 'GP_Linear_Admin', 'add_menu' ) ) );
	gp_rc( 'admin save handler is admin-post only (no nopriv)', false === has_action( 'admin_post_nopriv_gp_linear_rates_save' ) );
	$prev = get_current_user_id();
	wp_set_current_user( 1 );
	ob_start();
	GP_Linear_Admin::render();
	$html = ob_get_clean();
	wp_set_current_user( $prev );
	gp_rc( 'admin page renders the three rate fields with the current values', 3 === substr_count( $html, 'name="gp_linear_rates[' ) && false !== strpos( $html, 'value="2.50"' ) && false !== strpos( $html, 'name="_wpnonce"' ) );
	$js = GP_Linear_Pricing::js_config();
	gp_rc( 'JS config exposes rules and currency symbol', ! empty( $js['rules']['pole'] ) && ! empty( $js['symbol'] ) );
} finally {
	if ( $orig_exists ) { update_option( 'gp_linear_rates', $orig ); } else { delete_option( 'gp_linear_rates' ); }
	echo "  gp_linear_rates restored\n";
}
echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS\n";
exit( $fail ? 1 : 0 );
