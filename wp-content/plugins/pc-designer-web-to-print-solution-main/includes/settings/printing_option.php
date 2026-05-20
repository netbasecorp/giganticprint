<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( !class_exists('Nbdesigner_Printing_Options') ) {
    class Nbdesigner_Printing_Options{
        public static function get_options() {
            return apply_filters('nbdesigner_printing_options_settings', array(
                'general' => array(
                    array(
                        'title'         => esc_html__('Number of decimals', 'web-to-print-online-designer'),
                        'description'   => esc_html__( 'This sets the number of decinal points show in displayed option prices', 'web-to-print-online-designer' ),
                        'id'            => 'nbdesigner_number_of_decimals',
                        'default'       => wc_get_price_decimals(),
                        'css'           => 'width: 65px',
                        'type'          => 'number'
                    ),
                    array(
                        'title'         => esc_html__( 'Enable rich snippet price', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enbale_rich_snippet_price',
                        'description'   => esc_html__('Enable default rich snippet price for search engine because sometime base price is zero.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Options display style', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_option_display',
                        'description'   => esc_html__('This controls how options are displayed on the front-end .', 'web-to-print-online-designer'),
                        'default'       => '1',
                        'type'          => 'radio',
                        'options'       => array(
                            '1' => esc_html__('Sections', 'web-to-print-online-designer'),
                            '2' => esc_html__('Table', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide Add to cart button until all required options are chosen', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_add_cart_until_form_filled',
                        'description'   => esc_html__('Check this to show the add to cart button only when all required options are filled.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide summary options', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_summary_options',
                        'description'   => esc_html__('Hide summary options in product detail page.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Float summary options', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_float_summary_options',
                        'description'   => '',
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide table pricing', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_table_pricing',
                        'description'   => esc_html__('Hide table pricing in product detail page.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Table pricing type', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_table_pricing_type',
                        'description'   => '',
                        'default'       => '1',
                        'type'          => 'radio',
                        'options'       => array(
                            '1' => esc_html__('Quantity range', 'web-to-print-online-designer'),
                            '2' => esc_html__('Quantity breaks', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide option swatch description', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_option_swatch_label',
                        'description'   => esc_html__('Hide option swatch description in Style 1 in product detail page.', 'web-to-print-online-designer'),
                        'default'       => 'yes',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Change original product price', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_change_base_price_html',
                        'description'   => esc_html__('Overwrite the original product price when options are changing.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Auto hide price if zero', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_zero_price',
                        'description'   => esc_html__('Hide the option price display if it is zero.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'   => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Option description tooltip position', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_tooltip_position',
                        'description'   => '',
                        'default'       => 'top',
                        'type'          => 'radio',
                        'options'       => array(
                            'top'       => esc_html__('Top', 'web-to-print-online-designer'),
                            'right'     => esc_html__('Right', 'web-to-print-online-designer'),
                            'bottom'    => esc_html__('Bottom', 'web-to-print-online-designer'),
                            'left'      => esc_html__('Left', 'web-to-print-online-designer'),
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Advanced dropdown sub list position', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_ad_sublist_position',
                        'description'   => '',
                        'default'       => 'b',
                        'type'          => 'radio',
                        'options'       => array(
                            'b' => esc_html__('Bellow', 'web-to-print-online-designer'),
                            'r' => esc_html__('Right', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'jQuery selector for increase/decrease quantity button', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_selector_increase_qty_btn',
                        'description'   => esc_html__('This is used to re calculate quantity discount price, example: .quantity-plus, .quantity-minus', 'web-to-print-online-designer'),
                        'default'       => '',
                        'type'          => 'text',
                        'class'         => 'regular-text',
                        'placeholder'   => '.quantity-plus, .quantity-minus'
                    ),
                    array(
                        'title'         => esc_html__('Display product options on', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_display_product_option',
                        'description'   => esc_html__( 'Display product options on popup or product tab in modern layout.', 'web-to-print-online-designer'),
                        'default'       => '1',
                        'type'          => 'radio',
                        'options'       => array(
                            '1' => esc_html__('Popup', 'web-to-print-online-designer'),
                            '2' => esc_html__('Product Tab', 'web-to-print-online-designer')
                        ) 
                    ),
                    array(
                        'title'         => esc_html__( 'Enable map Print options with product attributes', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enable_map_print_options',
                        'description'   => esc_html__('Enable map print options fields with variable product attributes.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'   => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Show number of cart items in favicon', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enable_favicon_badge',
                        'description'   => esc_html__('Show and update number of cart items in favicon width badge.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'   => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'catalog'   => array(
                    array(
                        'title'         => esc_html__( 'Force Select Options', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_force_select_options',
                        'description'   => esc_html__('This changes the add to cart button on shop and archive pages to display select options when the product has extra product options.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Show options in archive shop pages', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_show_options_in_archive_pages',
                        'description'   => esc_html__('Choose to show options selection in archive shop pages as swatches.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'cart' => array(
                    array(
                        'title'         => esc_html__( 'Ajax cart', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enable_ajax_cart',
                        'description'   => esc_html__('Enable ajax add to cart in the product detail page.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Turn off persistent cart', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_turn_off_persistent_cart',
                        'description'   => esc_html__('Enable this if the product has a lot of options.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Clear cart button', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_enable_clear_cart_button',
                        'description'   => esc_html__('Enables or disables the clear cart button.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide options in cart', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_options_in_cart',
                        'description'   => esc_html__('Enables or disables the display of options in cart.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide option price in the cart', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_option_price_in_cart',
                        'description'   => esc_html__('Enables or disables the display of option price in the cart.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'order' => array(
                    array(
                        'title'         => esc_html__( 'Hide option price in the order', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_option_price_in_order',
                        'description'   => esc_html__('Enables or disables the display of option price in the the order, email, invoice...', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'editor' => array(
                    array(
                        'title'         => esc_html__( 'Auto hide Preview printing options in design editor', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_print_option_in_editor',
                        'description'   => '',
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
                        'title'         => esc_html__( 'Manage large amount of print options fields', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_print_option_large_amount',
                        'description'   => esc_html__( 'Enable this option if your print options has a large amount of fields.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    )
                ),
                'summary'   => array(
                    array(
                        'title'         => esc_html__( 'Hide summary Options price', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_summary_options_price',
                        'description'   => esc_html__('Hide Options price in Summary options table.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide summary Quantity Discount', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_summary_quantity_discount',
                        'description'   => esc_html__('Hide Quantity Discount in Summary options table.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                    array(
                        'title'         => esc_html__( 'Hide summary Final price', 'web-to-print-online-designer'),
                        'id'            => 'nbdesigner_hide_summary_final_price',
                        'description'   => esc_html__('Hide Final price in Summary options table.', 'web-to-print-online-designer'),
                        'default'       => 'no',
                        'type'          => 'radio',
                        'options'       => array(
                            'yes'   => esc_html__('Yes', 'web-to-print-online-designer'),
                            'no'    => esc_html__('No', 'web-to-print-online-designer')
                        )
                    ),
                ),
            ));
        }
    }
}