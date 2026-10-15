<?php
/**
 * B6 admin check through loopback requests with a throw-away administrator cookie (user #1, never printed):
 *   scripts/data/run.sh b6-admin-check.php [--orders=id,id,...]
 *  - wp-admin > Products: the price column shows the SAME "From" price as the catalog (no "From $0.00")
 *  - for every given order: the order edit screen shows the Customer Design box ("Custom design", no "No design or upload file"),
 *    and the admin PNG / PDF downloads contain the real art (background pixel of the PNG matches the sample banner's
 *    gradient colour of the order's customer, big colour variance, PDF has real size)
 * Read-only for site data. Exit 1 on any failure.
 */
require __DIR__ . '/_lib.php';
$orders = array();
foreach ( array_slice( $argv, 1 ) as $a ) {
	if ( 0 === strpos( $a, '--orders=' ) ) { $orders = array_filter( array_map( 'intval', explode( ',', substr( $a, 9 ) ) ) ); }
}
$fail = 0;
function gp_ac( $label, $ok, $detail = '' ) {
	global $fail;
	echo ( $ok ? '  PASS ' : '  FAIL ' ) . $label . ( $detail && ! $ok ? " ($detail)" : '' ) . "\n";
	if ( ! $ok ) { $fail++; }
}

$exp    = time() + 900;
$token  = WP_Session_Tokens::get_instance( 1 )->create( $exp );
$cookie = SECURE_AUTH_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( 1, $exp, 'secure_auth', $token ) ) . '; ' . LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( 1, $exp, 'logged_in', $token ) );
function gp_ac_get( $url ) {
	global $cookie;
	return wp_remote_get( $url, array( 'timeout' => 180, 'redirection' => 0, 'headers' => array( 'Cookie' => $cookie ), 'sslverify' => false ) );
}
$base_colour = array(
	'gp_test_b2b'      => array( 10, 38, 96 ),
	'gp_test_staff'    => array( 150, 20, 40 ),
	'gp_test_b2c'      => array( 20, 90, 60 ),
	'gp_test_reseller' => array( 60, 30, 110 ),
);

try {
	echo "-- Products list\n";
	$r    = gp_ac_get( admin_url( 'edit.php?post_type=product&posts_per_page=100' ) );
	$html = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
	gp_ac( 'products list answers 200', ! is_wp_error( $r ) && 200 === wp_remote_retrieve_response_code( $r ) );
	$bad  = 0;
	$rows = 0;
	foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) ) as $pid ) {
		if ( ! preg_match( '#<tr id="post-' . $pid . '".*?</tr>#s', $html, $m ) ) { continue; }
		if ( ! preg_match( '#<td class=.price column-price.[^>]*>(.*?)</td>#s', $m[0], $pm ) ) { continue; }
		$rows++;
		$cell = trim( wp_strip_all_tags( html_entity_decode( $pm[1] ) ) );
		$from = gp_from_price_compute( wc_get_product( $pid ) );
		$want = $from > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $from ) ) ) : '';
		if ( $want && false === strpos( $cell, $want ) ) { $bad++; echo "    product $pid: admin '$cell' catalog '$want'\n"; }
		if ( preg_match( '/\$0\.00/', $cell ) ) { $bad++; echo "    product $pid shows 0.00: '$cell'\n"; }
	}
	gp_ac( "price column of $rows products equals the catalog From price, no 0.00", $rows > 0 && 0 === $bad );

	foreach ( $orders as $oid ) {
		echo "-- order #$oid\n";
		$o = wc_get_order( $oid );
		if ( ! $o ) { gp_ac( 'order exists', false ); continue; }
		$u     = get_userdata( $o->get_customer_id() );
		$login = $u ? $u->user_login : '';
		$er    = gp_ac_get( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $oid ) );
		$eh    = is_wp_error( $er ) ? '' : wp_remote_retrieve_body( $er );
		if ( 200 !== ( is_wp_error( $er ) ? 0 : wp_remote_retrieve_response_code( $er ) ) ) {
			$er = gp_ac_get( admin_url( 'post.php?post=' . $oid . '&action=edit' ) );
			$eh = is_wp_error( $er ) ? '' : wp_remote_retrieve_body( $er );
		}
		gp_ac( 'order edit screen loads', false !== strpos( $eh, 'order_data' ) );
		gp_ac( 'Customer Design box lists "Custom design"', false !== strpos( $eh, 'nbdesigner_order_info' ) && false !== strpos( $eh, 'Custom design' ) );
		gp_ac( 'no "No design or upload file in this order"', false === strpos( $eh, 'No design or upload file' ) );
		$key = '';
		$pid = 0;
		foreach ( $o->get_items() as $it ) {
			$k = $it->get_meta( '_nbd' );
			if ( $k ) { $key = $k; $pid = $it->get_product_id(); break; }
		}
		gp_ac( 'order carries the design flag (_nbd) and a design key', (bool) $o->get_meta( '_nbd', true ) && '' !== $key );
		foreach ( array( 'png', 'pdf' ) as $type ) {
			$url = add_query_arg( array( 'page' => 'nbdesigner_detail_order', 'order_id' => $oid, 'product_id' => $pid, 'variation_id' => 0, 'nbd_item_key' => $key, 'download-type' => $type ), admin_url( 'admin.php' ) );
			$dr  = gp_ac_get( $url );
			$ok  = ! is_wp_error( $dr ) && 200 === wp_remote_retrieve_response_code( $dr );
			gp_ac( strtoupper( $type ) . ' download answers 200', $ok );
			if ( ! $ok ) { continue; }
			$zf = '/tmp/gp-b6-' . getmypid() . $type . '.zip';
			file_put_contents( $zf, wp_remote_retrieve_body( $dr ) );
			$z = new ZipArchive();
			if ( true !== $z->open( $zf ) ) { gp_ac( strtoupper( $type ) . ' zip opens', false ); @unlink( $zf ); continue; }
			$best = null;
			for ( $n = 0; $n < $z->numFiles; $n++ ) {
				$st = $z->statIndex( $n );
				if ( ! $best || $st['size'] > $best['size'] ) { $best = $st; }
			}
			gp_ac( strtoupper( $type ) . ' zip main file is big enough to hold art (' . ( $best ? $best['name'] . ' ' . $best['size'] . ' B' : 'empty' ) . ')', $best && $best['size'] > 20000 );
			if ( 'png' === $type && $best && class_exists( 'Imagick' ) ) {
				try {
					$im = new Imagick();
					$im->readImageBlob( $z->getFromIndex( $z->locateName( $best['name'] ) ) );
					$im->setImageBackgroundColor( 'white' );
					$im   = $im->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
					$stat = $im->getImageChannelStatistics();
					$sd   = max( $stat[ Imagick::CHANNEL_RED ]['standardDeviation'], $stat[ Imagick::CHANNEL_BLUE ]['standardDeviation'] ) / 257;
					gp_ac( 'PNG has real colour variance (stddev ' . round( $sd, 1 ) . ')', $sd > 25 );
					if ( isset( $base_colour[ $login ] ) ) {
						$w = $im->getImageWidth();
						$h = $im->getImageHeight();
						$c = $im->getImagePixelColor( (int) ( $w * 0.03 ), (int) ( $h * 0.12 ) )->getColor();
						$e = $base_colour[ $login ];
						$d = abs( $c['r'] - $e[0] ) + abs( $c['g'] - $e[1] ) + abs( $c['b'] - $e[2] );
						gp_ac( "PNG background is the $login sample banner colour (" . $c['r'] . ',' . $c['g'] . ',' . $c['b'] . ')', $d < 120, 'expected about ' . implode( ',', $e ) );
					}
					$im->clear();
				} catch ( Throwable $ex ) {
					gp_ac( 'PNG readable', false, $ex->getMessage() );
				}
			}
			$z->close();
			@unlink( $zf );
		}
	}
} finally {
	WP_Session_Tokens::get_instance( 1 )->destroy( $token );
}
echo $fail ? "RESULT FAIL ($fail)\n" : "RESULT PASS\n";
exit( $fail ? 1 : 0 );
