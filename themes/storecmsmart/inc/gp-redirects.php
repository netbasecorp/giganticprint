<?php
/**
 * 301 redirects from renamed product category slugs to the new ones.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', function () {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$map = array(
		'vinyl'                         => 'adhesive',
		'magnetic-signsmagnetic-signs'  => 'magnet',
		'apparel-dtf'                   => 'apparel',
	);

	$uri   = wp_unslash( $_SERVER['REQUEST_URI'] );
	$path  = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );

	// Strip a subdirectory install prefix, if any.
	$home_path = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $home_path && 0 === strpos( $path, $home_path . '/' ) ) {
		$path = substr( $path, strlen( $home_path ) );
	}

	if ( ! preg_match( '#^/product-category/([^/]+)(/.*)?$#', $path, $m ) ) {
		return;
	}
	$old = rawurldecode( $m[1] );
	if ( ! isset( $map[ $old ] ) ) {
		return;
	}
	// Only when the old slug is gone (404 or term no longer exists).
	if ( ! is_404() && term_exists( $old, 'product_cat' ) ) {
		return;
	}

	$rest   = isset( $m[2] ) ? $m[2] : '/';
	$target = home_url( '/product-category/' . $map[ $old ] . $rest );
	if ( '' !== $query ) {
		$target .= '?' . $query;
	}
	wp_safe_redirect( $target, 301 );
	exit;
} );
