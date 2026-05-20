<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('NBD_Printcart_General')) {

    class NBD_Printcart_General
    {

        protected static $instance;

        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {}

        public function init()
        {
            if (is_admin()) {
                $this->admin_hook();
                $this->ajax();
            }
        }
        public function admin_hook()
        {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 40, 1);
            add_action('nbd_menu_top', array($this, 'add_menu'), 10, 1);
        }
        public function ajax()
        {
            $ajax_events = array(
                'printcart_generate_key_api' => true,
                'printcart_check_connection_dashboard' => true,
                'printcart_save_api_key' => true,
                'printcart_get_store' => true,
                'printcart_import_product' => true,
                'printcart_save_settings' => true,
                'printcart_login_account' => true,
                'printcart_get_stores' => true,
                'printcart_create_store' => true,
            );
            foreach ($ajax_events as $ajax_event => $nopriv) {
                add_action('wp_ajax_' . $ajax_event, array($this, $ajax_event));
                if ($nopriv) {
                    add_action('wp_ajax_nopriv_' . $ajax_event, array($this, $ajax_event));
                }
            }
        }
        public function admin_enqueue_scripts($hook)
        {
            if ($hook == 'toplevel_page_nbdesigner') {
                $tabs = array(
                    'connect',
                    'import',
                    'general',
                    'setting_pages',
                    'overview',
                );
                $tab = isset($_GET['tab']) ? $_GET['tab'] : 'connect';
                if (!in_array($tab, $tabs)) {
                    $tab = 'connect';
                }

                $sid = nbdesigner_get_option('nbdesigner_printcart_api_sid');
                $secret = nbdesigner_get_option('nbdesigner_printcart_api_secret');
                $token = nbdesigner_get_option('nbdesigner_printcart_api_unauth_token');
                $dimension_unit = nbdesigner_get_option('nbdesigner_dimensions_unit', 'cm');
                $default_font_subset = nbdesigner_get_option('nbdesigner_default_font_subset');
                $first_imported_product = nbdesigner_get_option('nbdesigner_printcart_first_imported_products');
                $create_your_own_page_id = nbd_get_page_id('create_your_own');
                $designer_page_id = nbd_get_page_id('designer');
                $gallery_page_id = nbd_get_page_id('gallery');
                $logged_page_id = nbd_get_page_id('logged');
                $user = wp_get_current_user();
                $user_email = $user->user_email;
                $user_name = ($user->user_firstname ? $user->user_firstname . ' ' : '') . $user->user_lastname;
                $name = $user->display_name ? $user->display_name : $user_name;
                $site_title = get_bloginfo();
                $home_url = home_url();
                $dashboard_url = NBD_Printcart_API::get_dashboard_url();
                $is_https = NBD_Printcart_API::is_https();
                $demo_data_path = NBDESIGNER_PLUGIN_DIR . 'data/demo_datas.json';
                $products = json_decode(file_get_contents($demo_data_path), true);
                $product_sample = array();
                foreach ($products as $key => $product) {
                    $templates = [];
                    if (isset($product['templates']) && !empty($product['templates'])) {
                        $templates_str = nbd_file_get_contents($product['templates']);
                        if ($templates_str) {
                            $templates = json_decode($templates_str, true);
                        }
                    }
                    $product_sample[] = array(
                        'id' => $key,
                        'name' => $product['name'],
                        'image' => $product['image'],
                        'templates' => count($templates),
                        'print_options' => isset($product['print_options']) ? 1 : 0,
                    );
                }

                wp_enqueue_media();
                wp_register_script('printcart-general', NBDESIGNER_PLUGIN_URL . 'assets/js/printcart-general.js', array(), NBDESIGNER_VERSION);
                wp_register_style('pc-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
                wp_register_script('pc-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3', true);
                wp_localize_script('printcart-general', 'printcart_detail', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('nbdesigner_add_cat'),
                    'sid' => $sid,
                    'secret' => $secret,
                    'token' => $token,
                    'user_email' => $user_email,
                    'name' => $name,
                    'site_title' => $site_title,
                    'home_url' => $home_url,
                    'dashboard_url' => $dashboard_url,
                    'is_https' => $is_https,
                    'product_sample' => $product_sample,
                    'current_tab' => $tab,
                    'dimension_unit' => $dimension_unit,
                    'default_font_subset' => $default_font_subset,
                    'create_your_own_page_id' => $create_your_own_page_id,
                    'designer_page_id' => $designer_page_id,
                    'gallery_page_id' => $gallery_page_id,
                    'logged_page_id' => $logged_page_id,
                    'first_imported_product' => $first_imported_product,
                ));
                wp_enqueue_script('angularjs');
                wp_enqueue_script('pc-bootstrap');
                wp_enqueue_style('pc-bootstrap');
                wp_enqueue_script('printcart-general');
                wp_enqueue_style('nbdesigner_settings_css', NBDESIGNER_CSS_URL . 'admin-settings.css', array(), NBDESIGNER_VERSION);
            }
        }
        public function add_menu()
        {
            if (current_user_can('manage_options')) {
                add_menu_page('PC Designer', 'PC Designer', 'manage_options', 'nbdesigner', array($this, 'nbdesigner_general'), NBDESIGNER_PLUGIN_URL . 'assets/images/logo-icon-r.svg', 26);
                $nbdesigner_manage = add_submenu_page(
                    'nbdesigner',
                    esc_html__('PCDesigner Settings', 'web-to-print-online-designer'),
                    esc_html__('General', 'web-to-print-online-designer'),
                    'manage_options',
                    'nbdesigner',
                    array($this, 'nbdesigner_general')
                );
                add_action('load-' . $nbdesigner_manage, array('Nbdesigner_Helper', 'settings_helper'));
            }
        }

        public function nbdesigner_general()
        {
            $tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

            include_once(NBDESIGNER_PLUGIN_DIR . 'views/printcart-general.php');
        }

        /**
         *  Callback ajax generate key
         */
        public function printcart_generate_key_api()
        {
            global $wpdb;

            $name = isset($_POST['userName']) ? $_POST['userName'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $store_name = isset($_POST['storeName']) ? $_POST['storeName'] : '';
            $shop_url = isset($_POST['shopUrl']) ? $_POST['shopUrl'] : '';

            $description = __('Printcart integration', 'web-to-print-online-designer');
            $permissions = 'read_write';
            $user_id = get_current_user_id();
            $response = array();
            $consumer_key = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();

            if (!$user_id || ($user_id && !current_user_can('edit_user', $user_id))) {
                throw new Exception(esc_html__('You do not have permission to assign API Keys to the selected user.', 'web-to-print-online-designer'));
            }

            $data = array(
                'user_id' => $user_id,
                'description' => $description,
                'permissions' => $permissions,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7),
            );

            // Delete all previously generated keys
            $wpdb->delete(
                $wpdb->prefix . 'woocommerce_api_keys',
                array(
                    'user_id' => $user_id,
                    'description' => $description,
                )
            );

            $wpdb->insert(
                $wpdb->prefix . 'woocommerce_api_keys',
                $data,
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );

            $response['consumer_key'] = $consumer_key;
            $response['consumer_secret'] = $consumer_secret;
            $pass = NBD_Printcart_API::create_user_with_store(array(
                'name' => $name,
                'email' => $email,
                'store_name' => $store_name,
                'shop_url' => $shop_url,
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
            ));
            wp_send_json_success($pass);
            die();
        }

        public static function printcart_create_store()
        {
            $access_token = nbdesigner_get_option('nbdesigner_printcart_api_access_token');

            $response = array(
                'flag' => 0,
                'message' => '',
                'store' => array(),
            );

            if (!$access_token) {
                $response['message'] =  esc_html__('Access token is missing.', 'web-to-print-online-designer');

                wp_send_json_success($response);
                die();
            }

            global $wpdb;

            $description = __('Printcart integration', 'web-to-print-online-designer');
            $permissions = 'read_write';
            $user_id = get_current_user_id();
            $consumer_key = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();

            if (!$user_id || ($user_id && !current_user_can('edit_user', $user_id))) {
                throw new Exception(esc_html__('You do not have permission to assign API Keys to the selected user.', 'web-to-print-online-designer'));
            }

            $data = array(
                'user_id' => $user_id,
                'description' => $description,
                'permissions' => $permissions,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7),
            );

            // Delete all previously generated keys
            $wpdb->delete(
                $wpdb->prefix . 'woocommerce_api_keys',
                array(
                    'user_id' => $user_id,
                    'description' => $description,
                )
            );

            $wpdb->insert(
                $wpdb->prefix . 'woocommerce_api_keys',
                $data,
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );

            $name = isset($_POST['storeName']) ? sanitize_text_field(wp_unslash($_POST['storeName'])) : '';
            $shop_url = isset($_POST['shopUrl']) ? sanitize_text_field(wp_unslash($_POST['shopUrl'])) : '';
            $consumer_key = isset($data['consumer_key']) ? $data['consumer_key'] : '';
            $consumer_secret = isset($data['consumer_secret']) ? $data['consumer_secret'] : '';
            $integration = 'woocommerce';
            $integration_details = array(
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
            );

            $store = NBD_Printcart_API::response(wp_remote_request(NBD_Printcart_API::$api_url . '/stores', array(
                'body' => array(
                    'store_name' => $name,
                    'shop_url' => $shop_url,
                    'integration' => $integration,
                    'integration_details' => $integration_details,
                ),
                'method' => "POST",
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                ),
            )));

            $response['store'] = $store;
            if (isset($store['data']) && isset($store['data']['sid']) && isset($store['data']['secret']) && isset($store['data']['unauth_token']) && $store['data']['sid'] && $store['data']['secret'] && $store['data']['unauth_token']) {
                $sid = $store['data']['sid'];
                $secret = $store['data']['secret'];
                $unauth_token = $store['data']['unauth_token'];
                $response['flag'] = 1;
                update_option('nbdesigner_printcart_api_sid', $sid);
                update_option('nbdesigner_printcart_api_secret', $secret);
                update_option('nbdesigner_printcart_api_unauth_token', $unauth_token);
            } else {
                if (!empty($store['error']['shop_url'][0])) {
                    $response['message'] = $store['error']['shop_url'][0];
                } elseif (isset($store['error'])) {
                    $response['message'] = $store['error'];
                } else {
                    $response['message'] = esc_html__('Store creation failed.', 'web-to-print-online-designer');
                }
            }
            wp_send_json_success($response);
            die();
        }

        public function printcart_login_account()
        {
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            $reference = array(
                'flag' => 0,
                'message' => esc_html__('Login failed!', 'web-to-print-online-designer'),
                'data' => array(),
            );

            if (!$email || !is_email($email) || !$password) {
                $reference['message'] = esc_html__('Please enter email and password', 'web-to-print-online-designer');
                wp_send_json_success($reference);
                die();
            }

            $response = NBD_Printcart_API::login($email, $password);

            if (is_wp_error($response)) {
                $reference['message'] = $response->get_error_message();
                wp_send_json_success($reference);
                die();
            }

            if (!empty($response['data']['access_token'])) {
                update_option('nbdesigner_printcart_api_access_token', $response['data']['access_token']);
                $reference['flag'] = 1;
                $reference['message'] = esc_html__('Login successful!', 'web-to-print-online-designer');
                $reference['data'] = $response['data'];
            }

            wp_send_json_success($reference);
            die();
        }

        /**
         *  Check connection to dashboard
         */
        public function printcart_check_connection_dashboard()
        {
            $result = array(
                'flag' => 0,
                'message' => esc_html__('Connection failed!', 'web-to-print-online-designer'),
                'store' => array()
            );
            $sid = isset($_POST['sid']) ? $_POST['sid'] : '';
            $secret = isset($_POST['secret']) ? $_POST['secret'] : '';
            $unauth_token = isset($_POST['unauth_token']) ? $_POST['unauth_token'] : '';
            if ($sid && $secret && $unauth_token) {
                $store = NBD_Printcart_API::check_connection_with_printcart_by_key($sid, $secret, $unauth_token);

                if ($store) {
                    $result = array(
                        'flag' => 1,
                        'message' => esc_html__('Connection successful!', 'web-to-print-online-designer'),
                        'store' => $store
                    );
                }
            }

            wp_send_json_success($result);
            die();
        }
        public function printcart_save_api_key()
        {
            $sid = isset($_POST['sid']) ? $_POST['sid'] : '';
            $secret = isset($_POST['secret']) ? $_POST['secret'] : '';
            $unauth_token = isset($_POST['unauth_token']) ? $_POST['unauth_token'] : '';

            update_option('nbdesigner_printcart_api_sid', $sid);
            update_option('nbdesigner_printcart_api_secret', $secret);
            update_option('nbdesigner_printcart_api_unauth_token', $unauth_token);

            wp_send_json_success(array('flag' => 1));
            die();
        }
        public function printcart_get_store()
        {
            $result = array(
                'flag' => 0,
                'message' => esc_html__('Connection failed!', 'web-to-print-online-designer'),
                'store' => array(),
                'account' => array()
            );
            $sid = nbdesigner_get_option('nbdesigner_printcart_api_sid');
            $secret = nbdesigner_get_option('nbdesigner_printcart_api_secret');
            $token = nbdesigner_get_option('nbdesigner_printcart_api_unauth_token');

            $basic_auth = array();
            if ($token && $sid && $secret) {
                $basic_auth = array(
                    "Authorization" => 'Basic ' . base64_encode($sid . ':' . $secret),
                );
            }

            if (count($basic_auth)) {
                $store_detail = NBD_Printcart_API::fetchStoreDetailWithAuth($basic_auth);
                $account = NBD_Printcart_API::fetchAccountWithBasicAuth($basic_auth);
                if (!is_wp_error($account)) {
                    if (!empty($account['data']['tier'])) {
                        $tier = $account['data']['tier'];
                        update_option('printcart_store_tier', $tier);
                    }
                }
                if (!empty($store_detail['data']['id']) && !empty($account['data']['id'])) {
                    $result = array(
                        'flag' => 1,
                        'message' => esc_html__('Connection successful!', 'web-to-print-online-designer'),
                        'store' => $store_detail['data'],
                        'account' => $account['data'],
                    );
                }
            }

            wp_send_json_success($result);
            die();
        }

        public function printcart_get_stores()
        {
            $result = array(
                'flag' => 0,
                'message' => esc_html__('Connection failed!', 'web-to-print-online-designer'),
                'stores' => array()
            );

            $stores = NBD_Printcart_API::fetchStores();

            if (!is_wp_error($stores) && !empty($stores['data'])) {
                $result = array(
                    'flag' => 1,
                    'message' => esc_html__('Connection successful!', 'web-to-print-online-designer'),
                    'stores' => $stores['data']
                );
            }

            wp_send_json_success($result);
            die();
        }

        public function printcart_import_product()
        {
            $result = array(
                'flag' => 0,
            );
            $product_id = $_POST['product_id'];

            $demo_data_path = NBDESIGNER_PLUGIN_DIR . 'data/demo_datas.json';
            $demo_datas = json_decode(file_get_contents($demo_data_path), true);
            $settings_str = nbd_file_get_contents($demo_datas[$product_id]['settings']);
            $data = maybe_unserialize($settings_str);
            $new_product_id = $this->add_product($data);

            if ($new_product_id) {
                update_option('nbdesigner_printcart_first_imported_products', 'yes');

                $result['flag'] = 1;
                if ($data['nbo_enable'] && isset($demo_datas[$product_id]['print_options'])) {
                    $print_options_str = nbd_file_get_contents($demo_datas[$product_id]['print_options']);
                    $print_options_data = maybe_unserialize($print_options_str);
                    $this->create_or_update_print_option($new_product_id, $print_options_data);
                }

                if (isset($demo_datas[$product_id]['templates'])) {
                    $templates_str = nbd_file_get_contents($demo_datas[$product_id]['templates']);

                    $templates = json_decode($templates_str, true);

                    $this->add_templates($templates, $product_id, $new_product_id);
                }
            }

            wp_send_json($result);
            die();
        }

        public function add_product($data)
        {
            $product = new WC_Product();

            $product->set_name($data['name']);
            $product->set_description($data['description']);
            $product->set_regular_price($data['regular_price']);
            $product->set_sale_price($data['sale_price']);
            $product->set_status("publish");
            $product->set_catalog_visibility("visible");
            $product->set_stock_status("instock");

            if ($data['image']) {
                $media_id = nbd_add_attachment($data['image']);
                if ($media_id) {
                    $product->set_image_id($media_id);
                }
            }

            $product_id = $product->save();

            update_post_meta($product_id, '_nbdesigner_enable', $data['enable_design']);
            update_post_meta($product_id, '_nbdesigner_enable_upload', $data['enable_upload']);
            update_post_meta($product_id, '_nbdesigner_enable_upload_without_design', $data['upload_without_design']);
            update_post_meta($product_id, '_nbo_enable', $data['nbo_enable']);

            if ($data['setting_upload']) {
                update_post_meta($product_id, '_nbdesigner_upload', $data['setting_upload']);
            }

            if ($data['option']) {
                update_post_meta($product_id, '_nbdesigner_option', $data['option']);
            }

            if ($data['setting_design']) {
                $product_config = maybe_unserialize($data['setting_design']);
                $default_bg_id = get_option('nbdesigner_default_background');
                $default_ov_id = get_option('nbdesigner_default_overlay');
                foreach ($product_config as $key => $_config) {
                    $im_id = nbd_add_attachment($_config['img_src']);
                    $product_config[$key]['img_src'] = $im_id ? $im_id : $default_bg_id;

                    $ov_id = nbd_add_attachment($_config['img_overlay']);
                    $product_config[$key]['img_overlay'] = $ov_id ? $ov_id : $default_ov_id;
                }

                $setting_design = serialize($product_config);
                update_post_meta($product_id, '_designer_setting', $setting_design);
            }

            return $product_id;
        }
        public function create_or_update_print_option($new_product_id, $data)
        {
            $media_objects = maybe_unserialize($data['media_objects']);

            if (count($media_objects)) {
                $option_fields = maybe_unserialize($data['fields']);
                foreach ($media_objects as $key => $media) {
                    $key_arr = explode('-', $key);
                    $url = $media;
                    $uploaded_id = nbd_add_attachment($url);
                    $reference = &$option_fields;
                    foreach ($key_arr as $k) {
                        if (!array_key_exists($k, $reference)) {
                            $reference[$k] = [];
                        }
                        $reference = &$reference[$k];
                    }

                    $reference = $uploaded_id;
                    unset($reference);
                }

                $data['fields'] = serialize($option_fields);

                $this->save_print_option($new_product_id, $data);
            } else {
                $this->save_print_option($new_product_id, $data);
            }
        }
        public function save_print_option($new_product_id, $data)
        {
            global $wpdb;

            unset($data['media_objects']);
            unset($data['id']);
            $date = new DateTime();
            $data['modified'] = $date->format('Y-m-d H:i:s');
            $data['created'] = $date->format('Y-m-d H:i:s');
            $data['created_by'] = wp_get_current_user()->ID;
            $data['product_cats'] = serialize(array());
            $data['product_ids'] = serialize(array($new_product_id));

            $wpdb->insert("{$wpdb->prefix}nbdesigner_options", $data);
            $option_id = $wpdb->insert_id;
            set_transient('nbo_product_' . $new_product_id, $option_id);
        }
        public function add_templates($templates, $product_id, $new_product_id)
        {
            global $wpdb;
            if (!extension_loaded('zip')) {
                return false;
            }

            foreach ($templates as $key => $tem) {
                $temp_name = substr(md5(uniqid()), 0, 5) . rand(1, 100) . time();
                $temp_path = NBDESIGNER_DATA_DIR . '/import/' . $product_id . '/' . $tem['folder'] . '.zip';
                if (!file_exists(NBDESIGNER_DATA_DIR . '/import/' . $product_id)) {
                    mkdir(NBDESIGNER_DATA_DIR . '/import/' . $product_id, 0777, true);
                }
                $temp_dir = NBDESIGNER_CUSTOMER_DIR . '/' . $temp_name;
                nbd_download_remote_file($tem['temp_url'], $temp_path);

                $zip = new ZipArchive();
                if (!$zip->open($temp_path, ZIPARCHIVE::CREATE)) {
                    return false;
                }

                $zip->extractTo($temp_dir);
                $zip->close();

                unset($tem['temp_url']);
                $tem['product_id'] = $new_product_id;
                $tem['variation_id'] = 0;
                $tem['folder'] = $temp_name;
                $user_id = wp_get_current_user()->ID;
                $tem['user_id'] = $user_id;
                $date = new DateTime();
                $tem['created_date'] = $date->format('Y-m-d H:i:s');

                $wpdb->insert("{$wpdb->prefix}nbdesigner_templates", $tem);
            }
            return true;
        }

        public function printcart_save_settings()
        {
            $nbdesigner_create_your_own_page_id = sanitize_text_field($_POST['create_your_own_page_id']);
            $nbdesigner_designer_page_id = sanitize_text_field($_POST['designer_page_id']);
            $nbdesigner_gallery_page_id = sanitize_text_field($_POST['gallery_page_id']);
            $nbdesigner_logged_page_id = sanitize_text_field($_POST['logged_page_id']);
            update_option('nbdesigner_create_your_own_page_id', $nbdesigner_create_your_own_page_id);
            update_option('nbdesigner_designer_page_id', $nbdesigner_designer_page_id);
            update_option('nbdesigner_gallery_page_id', $nbdesigner_gallery_page_id);
            update_option('nbdesigner_logged_page_id', $nbdesigner_logged_page_id);

            $nbdesigner_dimensions_unit = sanitize_text_field($_POST['dimension_unit']);
            $nbdesigner_default_font_subset = sanitize_text_field($_POST['default_font_subset']);
            update_option('nbdesigner_dimensions_unit', $nbdesigner_dimensions_unit);
            update_option('nbdesigner_default_font_subset', $nbdesigner_default_font_subset);
            $result = array(
                'flag' => 1,
            );
            wp_send_json($result);
            die();
        }
    }
}

$printcart_admin_general = NBD_Printcart_General::instance();
$printcart_admin_general->init();
