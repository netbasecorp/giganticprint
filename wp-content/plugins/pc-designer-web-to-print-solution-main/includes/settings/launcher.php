<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( !class_exists('Nbdesigner_Launcher') ) {
    class Nbdesigner_Launcher{
        public static function get_options() {
            return apply_filters('nbdesigner_laucher_settings', array(
                'general' => array(
                    array(
                        'title'         => esc_html__( 'Enable designer store', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enable_designer_store',
                        'description'   => sprintf(wp_kses(__( 'Allow customers become designers who create and sell their designs. After "Save options" go to <a target="_blank" href="%s">Permalink</a> choose pretty permalinks and "Save changes". Default permalinks will not work.', 'web-to-print-online-designer'), array( 'a' => array( 'href' => array(), 'target' => array() ))), esc_url(admin_url('options-permalink.php'))),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'admin' => array(
                    array(
                        'title'         => esc_html__( 'Commission Type', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_commission_type',
                        'description'   => esc_html__('Select a commission type for designer.', 'web-to-print-online-designer'),
                        'default'       => 'percentage',
                        'type'          => 'select',
                        'class'         => 'depend_trigger',
                        'options'       => array(
                            'percentage'    => esc_html__('Percentage', 'web-to-print-online-designer'),
                            'flat'          => esc_html__('Flat', 'web-to-print-online-designer'),
                            'combine'       => esc_html__('Combine', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Default commission', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_default_commission',
                        'default'       => 0,
                        'description'   => esc_html__( 'Amount designers get from each sale has their designs.', 'web-to-print-online-designer'),
                        'type'          => 'text',
                        'class'         => 'regular-text',
                        'css'           => 'width: 85px',
                        'depend_on'     => array(
                            'id'        => 'nbdesigner_commission_type',
                            'value'     => 'combine',
                            'operator'  => '#'
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Default commission', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_default_commission2',
                        'description'   => esc_html__( 'Amount designers will get from sales has their designs in both percentage and fixed fee', 'web-to-print-online-designer' ),
                        'css'           => 'width: 85px',
                        'default'       => '0|0',
                        'type'          => 'multivalues',
                        'options'       => array(
                            0           => '',
                            1           => esc_html__( '%  +', 'web-to-print-online-designer')
                        ),
                        'depend_on'     => array(
                            'id'        => 'nbdesigner_commission_type',
                            'value'     => 'combine',
                            'operator'  => '='
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Minimum Withdraw Limit', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_minimum_withdraw',
                        'default'       => 0,
                        'description'   => esc_html__( 'Minimum balance required to make a withdraw request. Leave blank to set no minimum limits.', 'web-to-print-online-designer'),
                        'type'          => 'text',
                        'css'           => 'width: 85px',
                        'class'         => 'regular-text'
                    ),
                    array(
                        'title'         => esc_html__( 'Withdraw Threshold', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_withdraw_threshold',
                        'default'       => 0,
                        'description'   => esc_html__( 'Days, ( Delay time to active order designer earning )', 'web-to-print-online-designer'),
                        'type'          => 'text',
                        'css'           => 'width: 85px',
                        'class'         => 'regular-text'
                    ),
                    array(
                        'title'         => esc_html__( 'Order Status for Withdraw', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_order_status_for_withdraw',
                        'default'       => json_encode(array(
                                'nbdesigner_order_status_for_withdraw_wc-completed'     => 1,
                                'nbdesigner_order_status_for_withdraw_wc-processing'    => 0,
                                'nbdesigner_order_status_for_withdraw_wc-on-hold'       => 0
                            )),
                        'description'   => '',
                        'type'          => 'multicheckbox',
                        'class'         => 'regular-text',
                        'options'       => array(
                            'nbdesigner_order_status_for_withdraw_wc-completed'     => esc_html__('Completed', 'web-to-print-online-designer'),
                            'nbdesigner_order_status_for_withdraw_wc-processing'    => esc_html__('Processing', 'web-to-print-online-designer'),
                            'nbdesigner_order_status_for_withdraw_wc-on-hold'       => esc_html__('On-hold', 'web-to-print-online-designer')
                        ),
                        'css' => 'margin: 0 15px 10px 5px;'
                    )
                ),
                'designer' => array(
                    array(
                        'title'         => esc_html__( 'Designer page banner width', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_designer_banner_width',
                        'default'       => 1050,
                        'description'   => '',
                        'type'          => 'number',
                        'class'         => 'regular-text',
                        'css'           => 'width: 85px',
                        'subfix'        => ' px'
                    ),
                    array(
                        'title'         => esc_html__( 'Designer page banner height', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_designer_banner_height',
                        'default'       => 200,
                        'description'   => '',
                        'type'          => 'number',
                        'class'         => 'regular-text',
                        'css'           => 'width: 85px',
                        'subfix'        => ' px'
                    )
                ),
                'design' => array(
                    array(
                        'title'         => esc_html__( 'Generate preview for product has print option color automatically', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_auto_generate_color_product_preview',
                        'description'   => esc_html__( 'Beware this option turn on the process which consumes a lot of system resources', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                )
            ));
        }
    }
}