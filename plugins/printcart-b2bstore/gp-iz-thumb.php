<?php
/**
 * Image Zone thumbnail server, deliberately WITHOUT WordPress (a WordPress request costs 5-15 s on this busy host,
 * this one a few milliseconds).
 *
 * Only serves the small JPEG thumbnail "<store>/<file>-t.jpg" (never the original), and only for a URL carrying a valid
 * HMAC signature that PCS_Company_Images::signed_thumb_url() hands out to members of the company when they load their
 * Image Zone. The signature covers store id, stored file name and an expiry (24-48 h). The key lives in
 * uploads/gp-imagezone/.thumbkey (denied to the web by .htaccess). The full file stays behind the membership-checked
 * admin-ajax endpoint gp_iz_file.
 *
 * Request: gp-iz-thumb.php?s=<store id>&f=<32 hex>.<ext>&e=<expiry unix>&t=<hmac sha256 hex>
 */
$fail = function ( $code ) {
	http_response_code( $code );
	header( 'Cache-Control: no-store' );
	exit;
};

$s = isset( $_GET['s'] ) ? $_GET['s'] : '';
$f = isset( $_GET['f'] ) ? $_GET['f'] : '';
$e = isset( $_GET['e'] ) ? $_GET['e'] : '';
$t = isset( $_GET['t'] ) ? $_GET['t'] : '';
if ( ! is_string( $s ) || ! ctype_digit( $s ) || ! is_string( $e ) || ! ctype_digit( $e ) || ! is_string( $t ) || ! preg_match( '/^[a-f0-9]{64}$/', $t )
	|| ! is_string( $f ) || ! preg_match( '/^[a-f0-9]{32}\.[a-z0-9]{2,5}$/', $f ) ) {
	$fail( 400 );
}
$exp = (int) $e;
if ( $exp < time() ) {
	$fail( 403 );
}
$base = realpath( __DIR__ . '/../../uploads/gp-imagezone' );
if ( ! $base ) {
	$fail( 404 );
}
$key = is_readable( $base . '/.thumbkey' ) ? trim( (string) file_get_contents( $base . '/.thumbkey' ) ) : '';
if ( strlen( $key ) < 32 ) {
	$fail( 404 );
}
if ( ! hash_equals( hash_hmac( 'sha256', $s . '|' . $f . '|' . $exp, $key ), $t ) ) {
	$fail( 403 );
}
$path = $base . '/' . (int) $s . '/' . $f . '-t.jpg';
$real = realpath( $path );
if ( ! $real || 0 !== strpos( $real, $base . DIRECTORY_SEPARATOR ) || ! is_file( $real ) ) {
	$fail( 404 );
}
$mtime = filemtime( $real );
$size  = filesize( $real );
$etag  = '"' . substr( hash( 'sha256', $real . '|' . $mtime . '|' . $size ), 0, 24 ) . '"';
$max   = min( 86400, max( 60, $exp - time() ) );
header( 'Cache-Control: private, max-age=' . $max );
header( 'ETag: ' . $etag );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
header( 'X-Content-Type-Options: nosniff' );
header( 'Content-Type: image/jpeg' );
if ( ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $etag ) ) {
	http_response_code( 304 );
	exit;
}
header( 'Content-Length: ' . $size );
readfile( $real );
