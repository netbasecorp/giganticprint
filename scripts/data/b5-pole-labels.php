<?php
/**
 * B5: honest pole pocket / rope option text (idempotent, backup of every touched row, --restore supported):
 *   scripts/data/run.sh b5-pole-labels.php [--dry-run|--apply|--restore=<backup.json>]
 * The option sets carried stale hard-coded wording ("Pole Pocket ($1.00 per linear ft + $10 setup)", "Top only - quoted per linear ft").
 * The real rates are admin-editable (WooCommerce > Banner extras pricing) and the designer now writes the rule text and the
 * computed amount on each button from the live rates, so the stored titles/names are shortened to the plain words:
 *   field titles "Pole Pocket" / "Rope Reinforcement", options "Top only", "Bottom only", "Top & Bottom", "Add rope".
 * Pricing rules match on these words, so prices do not change.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b5-pole-labels' );
global $wpdb;
$T = $wpdb->prefix . 'nbdesigner_options';
foreach ( $wpdb->get_col( "SELECT id FROM $T" ) as $sid ) {
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT fields FROM $T WHERE id = %d", $sid ) );
	if ( false === stripos( (string) $raw, 'pole pocket' ) && false === stripos( (string) $raw, 'rope' ) ) { continue; }
	$f = maybe_unserialize( $raw );
	if ( ! is_array( $f ) || serialize( $f ) !== $raw || empty( $f['fields'] ) ) { GP_Data::say( "  set $sid: serialization does not round-trip, SKIPPED" ); continue; }
	$changed = array();
	foreach ( $f['fields'] as &$fl ) {
		if ( empty( $fl['general']['title'] ) ) { continue; }
		$title = $fl['general']['title'];
		if ( preg_match( '/^\s*pole\s*pocket/i', $title ) ) { $want = 'Pole Pocket'; }
		elseif ( preg_match( '/^\s*rope/i', $title ) ) { $want = 'Rope Reinforcement'; }
		else { continue; }
		if ( $title !== $want && preg_match( '/\(|quoted|linear/i', $title ) ) { $changed[] = "title '$title' -> '$want'"; $fl['general']['title'] = $want; }
		if ( empty( $fl['general']['attributes']['options'] ) ) { continue; }
		foreach ( $fl['general']['attributes']['options'] as &$op ) {
			if ( ! isset( $op['name'] ) || ! preg_match( '/quoted|linear/i', $op['name'] ) ) { continue; }
			$new = trim( preg_replace( '/\s*[\x{2014}\x{2013}-]+\s*quoted.*$/iu', '', $op['name'] ) );
			if ( '' !== $new && $new !== $op['name'] ) { $changed[] = "option '{$op['name']}' -> '$new'"; $op['name'] = $new; }
		}
		unset( $op );
	}
	unset( $fl );
	if ( $changed ) {
		GP_Data::say( "  set $sid: " . implode( '; ', $changed ) );
		GP_Data::row_update( $T, array( 'id' => $sid ), array( 'fields' => serialize( $f ), 'modified' => current_time( 'mysql' ) ), 'pole pocket / rope wording' );
	}
}
GP_Data::finish();
