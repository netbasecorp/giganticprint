<?php
/**
 * B7 Image Zone thumbnails (idempotent, derived files only: nothing to restore, originals are never touched):
 *   scripts/data/run.sh b7-iz-thumbs.php [--dry-run|--apply]
 * Rebuilds the thumbnail ("<file>-t.jpg", longest side 400 px) of every stored image whose thumbnail is missing or
 * smaller than it should be, makes sure the signing key for gp-iz-thumb.php exists and prints, per image, the signed
 * URL path (without the signature) so the result can be checked. Re-runnable any time.
 */
require __DIR__ . '/_lib.php';
global $wpdb;
$apply = in_array( '--apply', $argv, true );
echo $apply ? "== apply\n" : "== dry-run (use --apply to write)\n";
if ( ! class_exists( 'PCS_Company_Images' ) ) { echo "PCS_Company_Images missing\n"; exit( 1 ); }
$key = $apply ? PCS_Company_Images::thumb_key() : '';
echo '  signing key: ' . ( $apply ? ( '' !== $key ? 'present' : 'CANNOT BE WRITTEN' ) : 'checked on --apply' ) . "\n";
$rows = $wpdb->get_results( 'SELECT * FROM ' . PCS_Company_Images::table() . ' ORDER BY id' );
$n = 0;
foreach ( (array) $rows as $r ) {
	$path  = PCS_Company_Images::file_path( $r );
	$thumb = $path ? $path . '-t.jpg' : '';
	$have  = ( $thumb && is_file( $thumb ) ) ? @getimagesize( $thumb ) : false;
	$long  = $have ? max( $have[0], $have[1] ) : 0;
	$orig  = max( (int) $r->width, (int) $r->height );
	$want  = min( PCS_Company_Images::THUMB_MAX, $orig > 0 ? $orig : PCS_Company_Images::THUMB_MAX );
	$need  = ! $have || $long < $want - 1;
	echo '  image #' . $r->id . ' store ' . $r->store_id . ' ' . $r->name . ': thumbnail ' . ( $have ? $have[0] . 'x' . $have[1] . ' (' . filesize( $thumb ) . ' B)' : 'missing' ) . ( $need ? ' -> rebuild' : ' ok' );
	if ( $need && $apply ) {
		$ok = PCS_Company_Images::rebuild_thumb( $r );
		clearstatcache();
		$gi = $ok ? @getimagesize( $thumb ) : false;
		echo $ok && $gi ? ' => ' . $gi[0] . 'x' . $gi[1] . ' (' . filesize( $thumb ) . ' B)' : ' => FAILED (no renderer for this type: placeholder is used)';
		$n++;
	}
	echo "\n";
}
echo "== " . ( $apply ? "rebuilt $n thumbnail(s)" : 'dry-run: nothing written' ) . "\n";
