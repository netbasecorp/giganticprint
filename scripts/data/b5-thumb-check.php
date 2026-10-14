<?php
/**
 * B5 template-thumbnail proxy check (image processing of the gp_img_thumb endpoint, no HTTP, no data written).
 *   scripts/data/run.sh b5-thumb-check.php [--dry-run|--apply]
 *
 * Uses a temporary 3600x2400 PNG (with a transparent corner) in /tmp, always removed.
 * Prints PASS/FAIL per assertion, exits 1 when anything failed.
 */
require __DIR__ . '/_lib.php';
GP_Data::init( 'b5-thumb-check' );

$GLOBALS['gp_b5t_fail'] = 0;

function gp_b5t_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		GP_Data::say( '  PASS ' . $label );
	} else {
		$GLOBALS['gp_b5t_fail']++;
		GP_Data::say( '  FAIL ' . $label . ( '' !== $detail ? ' (' . $detail . ')' : '' ) );
	}
}

$tmp_png = '/tmp/gp-thumb-test-' . getmypid() . '.png';
$tmp_txt = '/tmp/gp-thumb-test-' . getmypid() . '.png.txt';
$tmp_bad = '/tmp/gp-thumb-test-' . getmypid() . '-bad.png';

try {
	$have = class_exists( 'PCS_Company_Templates' ) && method_exists( 'PCS_Company_Templates', 'make_thumb' ) && class_exists( 'Imagick' );
	gp_b5t_assert( 'PCS_Company_Templates::make_thumb and Imagick available', $have );
	gp_b5t_assert( 'wp_ajax_gp_img_thumb registered', has_action( 'wp_ajax_gp_img_thumb' ) !== false );
	gp_b5t_assert( 'no wp_ajax_nopriv_gp_img_thumb', false === has_action( 'wp_ajax_nopriv_gp_img_thumb' ) );

	if ( $have ) {
		gp_b5t_assert( 'allowlist contains files.printcart.com', in_array( 'files.printcart.com', PCS_Company_Templates::thumb_hosts(), true ) );
		gp_b5t_assert( 'https://files.printcart.com/x.png allowed', PCS_Company_Templates::thumb_url_allowed( 'https://files.printcart.com/a/x.png' ) );
		gp_b5t_assert( 'http:// refused', ! PCS_Company_Templates::thumb_url_allowed( 'http://files.printcart.com/x.png' ) );
		gp_b5t_assert( 'other host refused', ! PCS_Company_Templates::thumb_url_allowed( 'https://example.com/x.png' ) );
		gp_b5t_assert( 'look-alike host refused', ! PCS_Company_Templates::thumb_url_allowed( 'https://files.printcart.com.evil.test/x.png' ) );
		gp_b5t_assert( 'credentials trick refused', ! PCS_Company_Templates::thumb_url_allowed( 'https://files.printcart.com@evil.test/x.png' ) );
		gp_b5t_assert( 'odd port refused', ! PCS_Company_Templates::thumb_url_allowed( 'https://files.printcart.com:8443/x.png' ) );

		$im = new Imagick();
		$im->newImage( 3600, 2400, new ImagickPixel( '#3366cc' ), 'png' );
		$px = new ImagickPixel( 'transparent' );
		$dr = new ImagickDraw();
		$dr->setFillColor( $px );
		$dr->rectangle( 0, 0, 300, 300 );
		$im->setImageAlphaChannel( Imagick::ALPHACHANNEL_SET );
		$im->drawImage( $dr );
		$im->writeImage( $tmp_png );
		$im->clear();
		gp_b5t_assert( 'fixture PNG 3600x2400 written', is_file( $tmp_png ) );

		$r  = PCS_Company_Templates::make_thumb( $tmp_png );
		$ok = is_array( $r ) && ! empty( $r['bytes'] );
		gp_b5t_assert( 'local PNG processed', $ok, is_wp_error( $r ) ? $r->get_error_message() : '' );
		if ( $ok ) {
			$gi = getimagesizefromstring( $r['bytes'] );
			gp_b5t_assert( 'output is a valid JPEG/PNG', $gi && in_array( $gi['mime'], array( 'image/jpeg', 'image/png' ), true ) && $gi['mime'] === $r['mime'], $gi ? $gi['mime'] : 'unreadable' );
			gp_b5t_assert( 'longest side <= 800 px (and ~800)', $gi && max( $gi[0], $gi[1] ) <= 800 && max( $gi[0], $gi[1] ) >= 790, $gi ? $gi[0] . 'x' . $gi[1] : '' );
			gp_b5t_assert( 'aspect ratio kept (3:2)', $gi && abs( $gi[0] / $gi[1] - 1.5 ) < 0.01 );
			gp_b5t_assert( 'much smaller than the source', strlen( $r['bytes'] ) < filesize( $tmp_png ) );
			$chk = new Imagick();
			$chk->readImageBlob( $r['bytes'] );
			$p = $chk->getImagePixelColor( 5, 5 )->getColor();
			gp_b5t_assert( 'transparent corner flattened on white', $p['r'] > 240 && $p['g'] > 240 && $p['b'] > 240, wp_json_encode( $p ) );
			$chk->clear();
		}

		$r = PCS_Company_Templates::make_thumb( 'https://example.com/x.png' );
		gp_b5t_assert( 'non-allowlisted host refused', is_wp_error( $r ) && 'gp_thumb_host' === $r->get_error_code() );

		file_put_contents( $tmp_txt, 'hello, this is not an image' );
		$r = PCS_Company_Templates::make_thumb( $tmp_txt );
		gp_b5t_assert( 'text file refused', is_wp_error( $r ) );
		file_put_contents( $tmp_bad, '<?php echo 1; ?>' );
		$r = PCS_Company_Templates::make_thumb( $tmp_bad );
		gp_b5t_assert( 'fake .png (php content) refused', is_wp_error( $r ) );
		$r = PCS_Company_Templates::make_thumb( '/nonexistent/file.png' );
		gp_b5t_assert( 'missing file refused', is_wp_error( $r ) );
	}
} finally {
	@unlink( $tmp_png );
	@unlink( $tmp_txt );
	@unlink( $tmp_bad );
}

GP_Data::finish();
GP_Data::say( $GLOBALS['gp_b5t_fail'] ? ( '== FAILED: ' . $GLOBALS['gp_b5t_fail'] . ' assertion(s)' ) : '== all assertions passed' );
exit( $GLOBALS['gp_b5t_fail'] ? 1 : 0 );
