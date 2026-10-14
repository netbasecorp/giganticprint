<?php
// Handoff capture helper (read-only on content): prints Playwright cookies (logged_in + secure auth) for an admin user. No password involved.
if ( PHP_SAPI !== 'cli' ) { exit( 1 ); }
$_SERVER['HTTP_HOST'] = 'giganticprints.printcart.com'; $_SERVER['HTTPS'] = 'on'; $_SERVER['REQUEST_URI'] = '/';
define( 'WP_USE_THEMES', false );
require '/home/giganticprints/public_html/wp-load.php';
$mode = $argv[1] ?? 'login';
if ( 'list' === $mode ) { foreach ( get_users( array( 'role' => 'administrator' ) ) as $u ) { echo $u->ID . "\t" . $u->user_login . "\n"; } exit; }
$user = get_user_by( 'login', $argv[2] ?? '' );
if ( ! $user ) { fwrite( STDERR, "no user\n" ); exit( 1 ); }
$exp = time() + 3 * HOUR_IN_SECONDS;
$token = WP_Session_Tokens::get_instance( $user->ID )->create( $exp );
$host = parse_url( home_url(), PHP_URL_HOST );
$mk = function ( $name, $val ) use ( $host, $exp ) { return array( 'name' => $name, 'value' => $val, 'domain' => $host, 'path' => '/', 'httpOnly' => true, 'secure' => true, 'sameSite' => 'Lax', 'expires' => $exp ); };
echo wp_json_encode( array(
	$mk( LOGGED_IN_COOKIE, wp_generate_auth_cookie( $user->ID, $exp, 'logged_in', $token ) ),
	$mk( SECURE_AUTH_COOKIE, wp_generate_auth_cookie( $user->ID, $exp, 'secure_auth', $token ) ),
) ) . "\n";
