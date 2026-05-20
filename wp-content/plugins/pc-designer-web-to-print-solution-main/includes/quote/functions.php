<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function nbdq_get_token( $action, $order_id, $email ) {
    return wp_hash( $action . '|' . $order_id . '|' . $email, 'web-to-print-online-designer' );
}
function nbdq_verify_token( $token, $action, $order_id, $email ) {
    $expected = wp_hash( $action . '|' . $order_id . '|' . $email, 'web-to-print-online-designer' );
    if ( hash_equals( $expected, $token ) ) {
        return 1;
    }
    return 0;
}
function nbdq_pdf(){
    require_once(NBDESIGNER_PLUGIN_DIR.'lib/tcpdf/tcpdf.php');
    class NBDQPDF extends TCPDF {
        public function Footer() {
            $this->SetY(-15);
            $this->Cell(0, 10, esc_html__('Page ', 'web-to-print-online-designer' ) . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }
    $pdf = new NBDQPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    return $pdf;
}
function nbd_is_true( $value ) {
    return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
}
function nbd_get_quote_action_url( $action, $order_id, $email, $pay_for_order_url = '' ){
    $target_url = $pay_for_order_url != '' ? $pay_for_order_url : getUrlPageNBD('raq');
    $url = add_query_arg(array(
        'quote_id'           =>  $order_id,
        'action'             =>  $action,
        'raq_nonce'          =>  nbdq_get_token( $action, $order_id, $email )
    ), $target_url);
    return $url;
}
function nbd_replace_policy_page_link_placeholders( $text ) {
    return function_exists( 'wc_replace_policy_page_link_placeholders' ) ? wc_replace_policy_page_link_placeholders( $text ) : $text;
}
function nbdq_get_default_form_fields() {
    return apply_filters( 'nbdq_default_form_fields', array(
        'first_name' => array(
            'id'          => 'first_name',
            'type'        => 'text',
            'class'       => array(),
            'label'       => esc_html__( 'First Name', 'web-to-print-online-designer' ),
            'placeholder' => '',
            'enabled'     => 1,
            'validate'    => array(),
            'required'    => 1
        ),
        'last_name'  => array(
            'id'          => 'last_name',
            'type'        => 'text',
            'class'       => array(),
            'label'       => esc_html__( 'Last Name', 'web-to-print-online-designer' ),
            'placeholder' => '',
            'enabled'     => 1,
            'validate'    => array(),
            'required'    => 0
        ),
        'email'      => array(
            'id'          => 'email',
            'type'        => 'email',
            'class'       => array(),
            'label'       => esc_html__( 'Email', 'web-to-print-online-designer' ),
            'placeholder' => '',
            'enabled'     => 1,
            'validate'    => array( 'email' ),
            'required'    => 1
        ),
        'message'    => array(
            'id'          => 'message',
            'type'        => 'textarea',
            'class'       => array(),
            'label'       => esc_html__( 'Message', 'web-to-print-online-designer' ),
            'placeholder' => '',
            'validate'    => array(),
            'enabled'     => 1,
            'required'    => 0
        ),

    ) );
}
function nbdq_get_form_field_type() {
    $types = array(
        'text'              => esc_html__('Text', 'web-to-print-online-designer'),
        'tel'               => esc_html__('Phone', 'web-to-print-online-designer'),
        'textarea'          => esc_html__('Textarea', 'web-to-print-online-designer'),
        'radio'             => esc_html__('Radio', 'web-to-print-online-designer'),
        'checkbox'          => esc_html__('Checkbox', 'web-to-print-online-designer'),
        'select'            => esc_html__('Select', 'web-to-print-online-designer'),
        'country'           => esc_html__('Country', 'web-to-print-online-designer'),
        'state'             => esc_html__('State', 'web-to-print-online-designer'),
        'nbdq_multiselect'  => esc_html__('Multi select', 'web-to-print-online-designer'),
        'nbdq_datepicker'   => esc_html__('Date', 'web-to-print-online-designer'),
        'nbdq_timepicker'   => esc_html__('Time', 'web-to-print-online-designer'),
        'nbdq_acceptance'   => esc_html__('Acceptance', 'web-to-print-online-designer'),
        'nbdq_heading'      => esc_html__('Heading', 'web-to-print-online-designer')
    );
    return apply_filters('nbdq_form_field_types', $types);
}
function nbdq_get_connect_fields() {
    $fields             = array('' => array());
    $fields_billing     = WC()->countries->get_address_fields('', 'billing_');
    $fields_shipping    = WC()->countries->get_address_fields('', 'shipping_');
    $fields_billing     = is_array($fields_billing) ? $fields_billing : array();
    $fields_shipping    = is_array($fields_shipping) ? $fields_shipping : array();
    $fields             = array_merge($fields, $fields_billing, $fields_shipping);
    return array_keys($fields);
}
function nbdq_get_array_positions_form_field() {
    return array(
        'form-row-first' => esc_html__('First', 'web-to-print-online-designer'),
        'form-row-last'  => esc_html__('Last', 'web-to-print-online-designer'),
        'form-row-wide'  => esc_html__('Wide', 'web-to-print-online-designer')
    );
}
function nbdq_get_order_status_tag($status) {
    switch ($status) {
        case 'nbdq-new':
            echo '<span class="raq_status new">' . esc_html__('new', 'web-to-print-online-designer') . '</span>';
            break;
        case 'nbdq-pending':
            echo '<span class="raq_status pending">' . esc_html__('pending', 'web-to-print-online-designer') . '</span>';
            break;
        case 'nbdq-expired':
            echo '<span class="raq_status expired">' . esc_html__('expired', 'web-to-print-online-designer') . '</span>';
            break;
        case 'nbdq-rejected':
            echo '<span class="raq_status rejected">' . esc_html__('rejected', 'web-to-print-online-designer') . '</span>';
            break;
        case 'pending':
            echo '<span class="raq_status accepted">' . esc_html__('accepted', 'web-to-print-online-designer') . '</span>';
            break;
        default:
            echo '<span class="raq_status accepted">' . esc_html__('accepted', 'web-to-print-online-designer') . '</span>';
    }
}