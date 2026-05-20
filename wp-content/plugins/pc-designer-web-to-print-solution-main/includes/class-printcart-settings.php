<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('NBD_Printcart_Settings')) {

    class NBD_Printcart_Settings
    {

        protected static $instance;

        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
        }

        public function init()
        {
            add_filter('nbdesigner_general_settings', array($this, 'printcart_general_settings'), 30, 1);

            add_filter('nbdesigner_settings_blocks', array($this, 'printcart_settings_blocks'), 30, 1);

            add_filter('nbdesigner_settings_options', array($this, 'printcart_settings_options'), 30, 1);
        }

        public function printcart_general_settings($settings)
        {
            $user       = wp_get_current_user();
            $user_email = $user->user_email;
            $user_name  = ($user->user_firstname ? $user->user_firstname . ' ' : '') . $user->user_lastname;
            $name       = $user->display_name ? $user->display_name : $user_name;
            $site_title = get_bloginfo();
            $home_url   = home_url();
            $is_https   = NBD_Printcart_API::is_https();
            $result_Check = !$is_https  ? '<br>' . wp_kses(__('<div style="color: red"><p>Please use HTTPS. You cannot create a store on Printcart.</p></div>' , 'web-to-print-online-designer'), array('p' => array(), 'div' => array( 'style' => array() ))) : '';
            $is_connected = NBD_Printcart_API::check_connection_with_printcart(true);
            $connect_label = $is_connected ? __('Go to Dashboard', 'web-to-print-online-designer') : __('Connect to Dashboard', 'web-to-print-online-designer');
            $settings['printcart'] = array(
                array(
                    'title'         => esc_html__('Get Printcart API key', 'web-to-print-online-designer'),
                    'description'   => '<div><div data-shop-url="'. esc_attr($home_url) . '" data-is-https="'. $is_https .'" data-is-connected ="'. $is_connected .'" data-user-email="'. esc_attr($user_email) . '" data-store-name="'. esc_attr($site_title) . '" data-user-name="'. esc_attr($name) . '" id="printcart-connection-width-dashboard" class="button' . esc_attr($is_connected ? ' button-primary' : ' button-secondary') . esc_attr(!$is_https ? ' disabled' : '') .'" target="_blank">' . esc_html($connect_label) . '</div>' . $result_Check . '<div class="printcart-results"></div><div>'. esc_html($is_connected ? __('Your website has been successfully connected to the Printcart dashboard.', 'web-to-print-online-designer') : __('Click button to connect the Printcart Dashboard or you can enter the API key below.', 'web-to-print-online-designer')) .'</div></div>',
                    'id'            => 'nbdesigner_enable_upload_printcart_api',
                    'default'       => 'no',
                    'type'          => 'no',
                ),
                array(
                    'title'         => esc_html__('Sid', 'web-to-print-online-designer'),
                    'id'            => 'nbdesigner_printcart_api_sid',
                    'description'   => '',
                    'default'       => '',
                    'type'          => 'text',
                    'class'         => 'regular-text'
                ),
                array(
                    'title'         => esc_html__('Secret', 'web-to-print-online-designer'),
                    'id'            => 'nbdesigner_printcart_api_secret',
                    'description'   => '',
                    'default'       => '',
                    'type'          => 'text',
                    'class'         => 'regular-text'
                ),
                array(
                    'title'         => esc_html__('Unauth token ', 'web-to-print-online-designer'),
                    'id'            => 'nbdesigner_printcart_api_unauth_token',
                    'description'   => '<span class="nbd-check-connection-wrap"><div class="button button-secondary pc-check-connection-dashboard">' . esc_html__("Test connect", "web-to-print-online-designer") . '</div><div class="printcart-results-check"></div></span>',
                    'default'       => '',
                    'type'          => 'text',
                    'class'         => 'regular-text'
                ),
            );
            return $settings;
        }

        public function printcart_settings_blocks($blocks)
        {
            $blocks['general']['printcart'] = esc_html__('Printcart API', 'web-to-print-online-designer');

            return $blocks;
        }

        public function printcart_settings_options($options)
        {
            $general_options        = Nbdesigner_Settings_General::get_options();
            $options['printcart']   = $general_options['printcart'];

            return $options;
        }
    }
}

$printcart_admin_settings = NBD_Printcart_Settings::instance();
$printcart_admin_settings->init();
