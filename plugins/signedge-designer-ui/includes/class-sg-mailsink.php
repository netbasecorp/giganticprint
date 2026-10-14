<?php
/**
 * Test-mail sink: while the option gp_mail_sink is '1', wp_mail() sends nothing (the message is only logged to the
 * PHP error log). Used during e2e test orders so that internal admin addresses and @example.test customers do not
 * receive test e-mails. Off by default; switch with: wp option / update_option('gp_mail_sink', '1' | '0').
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'pre_wp_mail', function ( $return, $atts ) {
	if ( '1' !== (string) get_option( 'gp_mail_sink', '0' ) ) {
		return $return;
	}
	$to = isset( $atts['to'] ) ? ( is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : (string) $atts['to'] ) : '';
	error_log( 'gp_mail_sink: suppressed mail to ' . $to . ' subject "' . ( isset( $atts['subject'] ) ? $atts['subject'] : '' ) . '"' );
	return true;
}, 5, 2 );
