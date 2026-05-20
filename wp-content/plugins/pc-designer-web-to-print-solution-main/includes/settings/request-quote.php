<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( !class_exists('Nbdesigner_Request_quote') ) {
    class Nbdesigner_Request_quote{
        public static function get_options() {
            return apply_filters('nbdesigner_request_quote_settings', array(
                'general' => array(
                    array(
                        'title'         => esc_html__( 'Hide "Add to cart" button', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_hide_add_to_cart',
                        'description'   => esc_html__('Hide "Add to cart" for all products which enable "Get quote"', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide the price in product detail page', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_hide_price',
                        'description'   => esc_html__('Hide the price for all products which enable "Get quote"', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide the product price in the quote email', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_hide_price_in_email',
                        'description'   => '',
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Allow "Request a quote" even if the product out of stock', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_allow_out_of_stock',
                        'description'   => '',
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Show button request quote in checkout page', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_checkout_button',
                        'description'   => '',
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'request-form' => array(
                    array(
                        'title'         => esc_html__( 'Enable registration on the "Request a Quote" page', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_enable_registration',
                        'description'   => '',
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Enable reCAPTCHA in the default form', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enable_recaptcha_quote',
                        'description'   => sprintf(wp_kses(__( 'To start using reCAPTCHA V2, you need to sign up for an <a target="_blank" href="%s"> API key pair for your site</a>', 'web-to-print-online-designer'), array( 'a' => array( 'href' => array(), 'target' => array() )) ), esc_url( 'https://www.google.com/recaptcha/admin' )),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'reCAPTCHA site key', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_recaptcha_key',
                        'class'         => 'regular-text',
                        'default'       => '',
                        'type'          => 'text'
                    ),
                    array(
                        'title'         => esc_html__( 'reCAPTCHA secret key', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_recaptcha_secret_key',
                        'class'         => 'regular-text',
                        'default'       => '',
                        'type'          => 'text'
                    ),
                    array(
                        'title'         => esc_html__( 'Autocomplete Form', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_autocomplete_form',
                        'description'   => esc_html__('Check this option if you want that the fields connected to WooCommerce fields will be filled automatically', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'pdf-quote' => array(
                    array(
                        'title'         => esc_html__( 'Allow creating PDF document download in My Account page', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_allow_download_pdf',
                        'description'   => esc_html__('A button "Download PDF" will be added in the quote detail page', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Attach PDF quote to the email', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_attach_pdf',
                        'description'   => esc_html__('The quote will be sent as PDF attachment', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Remove the list with products from the email', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_remove_list_in_email',
                        'description'   => esc_html__('Hide list product in the email if it has been sent as PDF attachment', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'PDF Logo', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_pdf_logo',
                        'description'   => esc_html__('Upload the logo you want to show in the PDF document. Only .png and .jpeg extensions are allowed', 'web-to-print-online-designer'),
                        'default'       => '',
                        'type'          => 'nbd-media'
                    ),
                    array(
                        'title'         => esc_html__( 'PDF note', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_quote_pdf_note',
                        'type'          => 'textarea',
                        'description'   => '',
                        'default'       => '',
                        'css'           => 'width: 50em; height: 10em;'
                    )
                )
            ));
        }
    }
}