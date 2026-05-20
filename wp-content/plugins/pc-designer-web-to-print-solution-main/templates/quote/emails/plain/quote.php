<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo( $email_heading . "\n\n" );
$quote_number = $raq_data['order-number'];
echo sprintf( esc_html__( '%s n. %d', 'web-to-print-online-designer' ), $email_title, $quote_number ) . "\n\n";
echo( $email_description . "\n\n" );
echo sprintf( esc_html__( 'Request date: %s', 'web-to-print-online-designer' ), $raq_data['order-date'] ) . "\n\n";
if ( $raq_data['expiration_data'] != '' ) {
    echo sprintf( esc_html__( 'Expiration date: %s', 'web-to-print-online-designer' ), $raq_data['expiration_data-date'] ) . "\n\n";
}
if ( ! empty( $raq_data['admin_message'] ) ) {
    echo( $raq_data['admin_message'] . "\n\n" );
}
nbdesigner_get_template('quote/emails/plain/request-quote-items.php', array(
    'raq_data' => $raq_data
));
echo esc_html__( 'Accept', 'web-to-print-online-designer' ) . "\n";
$raq_page_url = '';
echo esc_url(
    add_query_arg(
        array(
            'request_quote' => $raq_data['order-number'],
            'status'        => 'accepted',
            'raq_nonce'     => nbdq_get_token( 'accept-request-quote', $raq_data['order-number'], $raq_data['user_email'] )
        ), $raq_page_url
    )
);
echo "\n\n";
echo esc_html__( 'Reject', 'web-to-print-online-designer' );
echo 'reject url';
echo "\n\n";
echo( $raq_data['order-id'] . "\n\n" );

$user_name       = $order->get_meta('_nbdq_customer_name');
$user_email      = $order->get_billing_mail();
$billing_company = $order->get_billing_company();

$billing_address_1 = $order->get_billing_address1();
$billing_address_2 = $order->get_billing_address2();

$billing_address = $billing_address_1 . ( ( $billing_address_1 != '' ) ? ' ' : '' ) . $billing_address_2;
if ( $billing_address == '' ) {
    $billing_address = $order->get_address();
}
$billing_city     = $order->get_billing_city();
$billing_postcode = $order->get_billing_postcode();
$billing_country  = $order->get_billing_country();
$billing_state    = $order->get_billing_state();
$billing_phone    = $order->get_billing_phone();
$billing_vat      = '';
echo esc_html__( 'Customer\'s details', 'web-to-print-online-designer' ) . "\n";
echo esc_html__( 'Name:', 'web-to-print-online-designer' );
echo( $user_name . "\n" );
if ( $billing_company != '' ) {
    echo esc_html__( 'Company:', 'web-to-print-online-designer' );
    echo( $billing_company . "\n" );
}
if ( $billing_address != '' ) {
    echo esc_html__( 'Address:', 'web-to-print-online-designer' );
    echo( $billing_address . "\n" );
}
if ( $billing_city != '' ) {
    echo esc_html__( 'City:', 'web-to-print-online-designer' );
    echo( $billing_city . "\n" );
}
if ( $billing_postcode != '' ) {
    echo esc_html__( 'Postcode:', 'web-to-print-online-designer' );
    echo( $billing_postcode . "\n" );
}
if ( $billing_state != '' ) {
    if ( $billing_country != '' ) {
        $states        = WC()->countries->get_states( $billing_country );
        $billing_state = ( $states[ $billing_state ] != '' ? $states[ $billing_state ] : $billing_state );
    }
    echo esc_html__( 'State:', 'web-to-print-online-designer' );
    echo( $billing_state . "\n" );
}
if ( $billing_country != '' ) {
    $countries = WC()->countries->get_countries();
    echo esc_html__( 'Country:', 'web-to-print-online-designer' );
    echo( $countries[ $billing_country ] . "\n" );
}
echo esc_html__( 'Email:', 'web-to-print-online-designer' );
echo( $user_email . "\n" );
if ( $billing_phone != '' ) {
    echo esc_html__( 'Billing Phone:', 'web-to-print-online-designer' );
    echo( $billing_phone . "\n" );
}
if ( $billing_vat != '' ) {
    echo esc_html__( 'Billing VAT:', 'web-to-print-online-designer' );
    echo( $billing_vat . "\n" );
}
echo "\n----------------------------------------------------\n\n";
echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );