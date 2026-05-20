<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly 
if(!class_exists('NBD_Printing_Input_Field')){
    class NBD_Printing_Input_Field{
        public function __construct() {
            //todo
        }
        public static function get_options() {
            return apply_filters('nbd_printing_options_input', array(
                'general' => array(                          
                    array(
                        'title' => esc_html__( 'Title', 'web-to-print-online-designer'),
                        'field' => 'title',
                        'description'   =>  '',
                        'class' => '',
                        'css'         => '',
                        'value'	=> 'Title',
                        'type' 		=> 'input'
                    ),  
                    array(
                        'title' => esc_html__( 'Description', 'web-to-print-online-designer'),
                        'field' => 'description',
                        'class' => '',
                        'description'   =>  '',
                        'css'         => '',
                        'value'	=> 'Description',
                        'type' 		=> 'textarea'
                    ),     
                    array(
                        'title' => esc_html__( 'Data type', 'web-to-print-online-designer'),
                        'field' => 'type',
                        'class' => '',
                        'description'   =>  '',
                        'css'         => '',
                        'value'	=> 't',
                        'type' 		=> 'dropdown',
                        'options' =>    array(
                            't'   =>  esc_html__( 'Text', 'web-to-print-online-designer'),
                            'n'   =>  esc_html__( 'Number', 'web-to-print-online-designer'),
                            'e'   =>  esc_html__( 'Email', 'web-to-print-online-designer'),
                        )
                    ),                     
                    array(
                        'title' => esc_html__( 'Enabled', 'web-to-print-online-designer'),
                        'field' => 'enabled',
                        'class' => '',
                        'description'   =>  'Choose whether the option is enabled or not.',
                        'css'         => '',
                        'value'	=> 'y',
                        'type' 		=> 'radio',
                        'options' =>    array(
                            'y'   =>  esc_html__( 'Yes', 'web-to-print-online-designer'),
                            'n'   =>  esc_html__( 'No', 'web-to-print-online-designer')
                        )
                    ),   
                    array(
                        'title' => esc_html__( 'Required', 'web-to-print-online-designer'),
                        'field' => 'required',
                        'class' => '',
                        'description'   =>  'Choose whether the option is enabled or not.',
                        'css'         => '',
                        'value'	=> 'y',
                        'type' 		=> 'radio',
                        'options' =>    array(
                            'y'   =>  esc_html__( 'Yes', 'web-to-print-online-designer'),
                            'n'   =>  esc_html__( 'No', 'web-to-print-online-designer')
                        )
                    ), 
                    array(
                        'title' => esc_html__( 'Price type', 'web-to-print-online-designer'),
                        'field' => 'price_type',
                        'class' => '',
                        'description'   =>  '',
                        'css'         => '',
                        'value'	=> 'f',
                        'type' 		=> 'dropdown',
                        'options' =>    array(
                            'f'   =>  esc_html__( 'Fixed amount', 'web-to-print-online-designer'),
                            'p'   =>  esc_html__( 'Percent of the original price', 'web-to-print-online-designer'),
                            'p+'   =>  esc_html__( 'Percent of the original price + options', 'web-to-print-online-designer'),
                            'c'   =>  esc_html__( 'Current value * price', 'web-to-print-online-designer'),
                            'd'   =>  esc_html__( 'Price depend quantity', 'web-to-print-online-designer'),
                        )
                    ),                      
                    array(
                        'title' => esc_html__( 'Price', 'web-to-print-online-designer'),
                        'field' => 'price',
                        'description'   =>  'Enter the price for this field or leave it blank for no price.',
                        'class' => '',
                        'css'         => '',
                        'value'	=> '',
                        'type' 		=> 'number'
                    ),     
                    array(
                        'title' => esc_html__( 'Sale Price', 'web-to-print-online-designer'),
                        'field' => 'sale_price',
                        'description'   =>  'Enter the sale price for this field or leave it blankto use the default price.',
                        'class' => '',
                        'css'         => '',
                        'value'	=> '',
                        'type' 		=> 'number'
                    )                                      
                ),
                'conditional' => array(                          
                    array(
                        'title' => esc_html__( 'Title', 'web-to-print-online-designer'),
                        'class' => '',
                        'css'         => '',
                        'default'	=> '',
                        'type' 		=> 'input'
                    ),  
                ),
                'appearance' => array(                          
                    array(
                        'title' => esc_html__( 'Title', 'web-to-print-online-designer'),
                        'class' => '',
                        'css'         => '',
                        'default'	=> '',
                        'type' 		=> 'input'
                    ),   
                )                 
            ));
        }
    }
}