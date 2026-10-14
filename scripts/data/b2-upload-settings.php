<?php
/**
 * B2 data changes: upload limits in NBDesigner (the PHP limits are in b2-php-limits.sh).
 *   scripts/data/run.sh b2-upload-settings.php [--dry-run|--apply|--restore=<backup.json>]
 *
 * - global max upload size 2 MB -> 100 MB (designer "Photos" upload and "Upload design" on the product page);
 * - per-product _nbdesigner_upload.maxsize (80 on every product) -> 100.
 * The minimum-DPI blocking options stay off: low resolution is only WARNED about in the designer UI.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b2-upload-settings' );
global $wpdb;

GP_Data::say( '-- global limits' );
foreach ( array( 'nbdesigner_maxsize_upload', 'nbdesigner_maxsize_upload_file' ) as $name ) {
	$cur = get_option( $name, null );
	$new = is_float( $cur ) ? 100.0 : ( is_int( $cur ) ? 100 : '100' );
	GP_Data::option_set( $name, $new );
}

GP_Data::say( '-- per-product upload settings' );
$pids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nbdesigner_upload'" );
foreach ( $pids as $pid ) {
	$val = get_post_meta( $pid, '_nbdesigner_upload', true );
	$arr = maybe_unserialize( $val );
	if ( ! is_array( $arr ) || ! isset( $arr['maxsize'] ) ) { continue; }
	if ( serialize( $arr ) !== $val ) { GP_Data::say( "  product $pid: meta does not round-trip, SKIPPED" ); continue; }
	if ( (float) $arr['maxsize'] >= 100 ) { continue; }
	$arr['maxsize'] = '100';
	GP_Data::meta_set( (int) $pid, '_nbdesigner_upload', serialize( $arr ) );
}
GP_Data::finish();
