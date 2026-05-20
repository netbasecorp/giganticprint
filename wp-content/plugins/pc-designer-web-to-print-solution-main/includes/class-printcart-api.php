<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('NBD_Printcart_API')) {

    class NBD_Printcart_API
    {
        public static $api_url = 'https://api.printcart.com/v1';
        // public static $api_url = 'http://localhost:8888/backend-rest-api/public/v1';

        public static $dashboard_url = 'https://dashboard.printcart.com';
        // public static $dashboard_url = 'http://localhost:3000';

        public static $enabled_license = false;

        public static $mess_ssl = 'Your website does not use SSL. Please change your website to HTTPS and connect to Printcart. ';

        public function __construct() {}

        public static function get_dashboard_url()
        {
            return self::$dashboard_url;
        }

        public static function notice_not_acept_api_key($need_check = false)
        {
            $enable_printcart_api = false;
            $message = '';
            if ($need_check) {
                $enable_printcart_api = self::enable_printcart_api();
            }
            $is_https = self::is_https();
            if (!$is_https) {
                $mess_ssl = str_replace('SSL', '<b>SSL</b>', self::$mess_ssl);
                $mess_ssl = str_replace('HTTPS', '<b>HTTPS</b>', $mess_ssl);
                $message = $mess_ssl . '<br>';
            }
            if (!$enable_printcart_api) {
                $message .= wp_kses(__('To use cloud features, please update <b>Printcart API keys</b> ', 'web-to-print-online-designer'), array('b' => array())) . '<a href="' . esc_html__(admin_url('admin.php?page=nbdesigner') . '#printcart') . '">' . esc_html__('Update now', 'web-to-print-online-designer') . '</a> or ';
            }
            if (!$need_check || !$enable_printcart_api) {
                echo '<div class="update-nag notice notice-warning inline" style="background: #ffe1c8;display: block;">' . $message . '<b><a href="' . esc_url(NBD_Printcart_API::$dashboard_url) . '" target="_blank">Contact us for help</a></b></div>';
            }
        }

        public static function check_connection_with_printcart($call_api = false)
        {
            $connected = false;
            $sid = nbdesigner_get_option('nbdesigner_printcart_api_sid');
            $secret = nbdesigner_get_option('nbdesigner_printcart_api_secret');
            $unauth_token = self::get_unauth_token();

            if (!$sid || !$secret || !$unauth_token) {
                return false;
            } else {
                $connected = true;
            }

            if ($call_api) {
                $url = self::$api_url . '/stores/store-details';
                $headers = self::get_header_basic_auth();

                $response = wp_remote_request($url, array(
                    'headers' => $headers,
                    'method' => "GET",
                ));

                $store_detail = self::response($response);

                if (isset($store_detail['data']) && isset($store_detail['data']['unauth_token']) && $store_detail['data']['unauth_token'] == $unauth_token) {
                    $connected = true;
                } else {
                    $connected = false;
                    delete_option('nbdesigner_printcart_api_unauth_token');
                }
            }

            return $connected;
        }

        public static function check_connection_with_printcart_by_key($sid = '', $secret = '', $unauth_token = '')
        {

            $store = array();
            if (!$sid || !$secret || !$unauth_token) {
                return false;
            }
            $url = self::$api_url . '/stores/store-details';
            $response = wp_remote_request($url, array(
                'headers' => array(
                    "Authorization" => 'Basic ' . base64_encode($sid . ':' . $secret)
                ),
                'method' => "GET",
            ));

            $store_detail = self::response($response);


            if (isset($store_detail['data']) && isset($store_detail['data']['unauth_token']) && $store_detail['data']['unauth_token'] == $unauth_token) {
                $store = $store_detail['data'];
            } else {
                return false;
            }
            return $store;
        }

        public static function get_unauth_token()
        {
            return nbdesigner_get_option('nbdesigner_printcart_api_unauth_token');
        }

        public static function get_basic_auth()
        {
            $sid = nbdesigner_get_option('nbdesigner_printcart_api_sid');

            $secret = nbdesigner_get_option('nbdesigner_printcart_api_secret');

            return 'Basic ' . base64_encode($sid . ':' . $secret);
        }

        public static function get_header_unauth_token()
        {
            $unauth_token = nbdesigner_get_option('nbdesigner_printcart_api_unauth_token');

            return array(
                'X-PrintCart-Unauth-Token' => $unauth_token
            );
        }

        public static function get_header_basic_auth()
        {
            return array(
                "Authorization" => self::get_basic_auth(),
            );
        }
        public static function is_active()
        {
            if (self::is_https() && self::check_connection_with_printcart()) {
                return 1;
            }
            return 0;
        }
        public static function is_https()
        {
            if (substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1') {
                return 1;
            }
            $url = home_url();
            if (!$url)
                return 0;
            $scheme_url = parse_url($url, PHP_URL_SCHEME);
            if ($scheme_url == 'https') {
                return 1;
            }
            return 0;
        }

        public static function enable_printcart_api()
        {
            if (self::check_connection_with_printcart()) {
                return true;
            }
            return false;
        }

        public static function response($response, $format = true)
        {
            $body = wp_remote_retrieve_body($response);

            return json_decode($body, $format);
        }

        public static function printcart_remote_post($url, $headers, $post_fields = array(), $file = array())
        {
            $local_file = isset($file['tmp_name']) ? $file['tmp_name'] : '';
            $file_name = isset($file['name']) ? $file['name'] : '';
            $type = isset($file['type']) ? $file['type'] : '';
            $file_blob = isset($file['file_blob']) ? $file['file_blob'] : '';

            $boundary = wp_generate_password(24);

            $headers['content-type'] = 'multipart/form-data; boundary=' . $boundary;


            $payload = '';

            if ($post_fields && is_array($post_fields) && count($post_fields) > 0) {
                foreach ($post_fields as $name => $value) {
                    $payload .= '--' . $boundary;
                    $payload .= "\r\n";
                    $payload .= 'Content-Disposition: form-data; name="' . $name .
                        '"' . "\r\n\r\n";
                    $payload .= $value;
                    $payload .= "\r\n";
                }
            }

            if ($local_file || $file_blob) {
                $payload .= '--' . $boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="file"; filename="' . $file_name . '"' . "\r\n";
                $payload .= 'Content-Type: ' . $type . "\r\n";
                $payload .= "\r\n";
                $payload .= $file_blob ? $file_blob : file_get_contents($local_file);
                $payload .= "\r\n";
            }

            $payload .= '--' . $boundary . '--';

            return wp_remote_post($url, array(
                'headers' => $headers,
                'body' => $payload,
                'timeout' => 600,
            ));
        }

        public static function fetchData($url)
        {
            $headers = self::get_header_unauth_token();

            return wp_remote_get($url, array(
                'headers' => $headers,
            ));
        }

        public static function upload_file($url, $headers, $file, $param = array())
        {
            $response = self::printcart_remote_post(
                $url,
                $headers,
                $param,
                $file
            );

            return self::response($response);
        }

        public static function upload_image($file, $param = array())
        {
            $headers = self::get_header_unauth_token();

            $url = self::$api_url . '/images';

            return self::upload_file($url, $headers, $file, $param);
        }

        public static function upload_clipart($file)
        {
            $headers = self::get_header_basic_auth();

            $url = self::$api_url . '/cliparts';

            return self::upload_file($url, $headers, $file);
        }

        public static function update_clipart($clipart_id, $cats)
        {
            if (!$clipart_id)
                return;
            $url = self::$api_url . '/cliparts/' . $clipart_id;

            $headers = self::get_header_basic_auth();

            $response = wp_remote_request($url, array(
                'headers' => $headers,
                'method' => "PUT",
                'body' => array('storages' => $cats)
            ));

            return self::response($response);
        }

        public static function fetchClipartByStorageId($cat_id = '', $cursor = '', $limit = 99)
        {
            $url = self::$api_url . '/cliparts?limit=' . $limit;

            if ($cat_id) {
                $url = self::$api_url . '/clipart-storages/' . $cat_id . '/cliparts?limit=' . $limit;
            }

            if ($cursor) {
                $url = self::$api_url . '/cliparts?cursor=' . $cursor . '&limit=' . $limit;
            }

            $response = self::fetchData(
                $url
            );

            return self::response($response);
        }

        public static function fetchClipartCount()
        {
            $url = self::$api_url . '/cliparts/count';
            $response = self::fetchData(
                $url
            );

            return self::response($response);
        }

        public static function fetchClipartStorage($limit = 99)
        {
            $url = self::$api_url . '/clipart-storages?limit=' . $limit;
            $response = self::fetchData(
                $url
            );

            return self::response($response);
        }

        public static function fetchFonts($cursor = '', $limit = 99)
        {
            $url = self::$api_url . '/fonts?limit=' . $limit;

            if ($cursor) {
                $url = self::$api_url . '/fonts?cursor=' . $cursor . '&limit=99';
            }

            $response = self::fetchData(
                $url
            );

            return self::response($response);
        }

        public static function fetchFontById($id)
        {
            if (!$id)
                return;

            $url = self::$api_url . '/fonts/' . $id;

            $response = self::fetchData(
                $url
            );

            return self::response($response);
        }

        public static function create_font($file, $font)
        {
            $headers = self::get_header_basic_auth();

            $url = self::$api_url . '/fonts';

            return self::upload_file($url, $headers, $file, $font);
        }

        public static function update_font($font_id, $font)
        {
            if (!$font_id)
                return;
            $url = self::$api_url . '/fonts/' . $font_id;

            $headers = self::get_header_basic_auth();

            $response = wp_remote_request($url, array(
                'headers' => $headers,
                'method' => "PUT",
                'body' => $font
            ));

            return self::response($response);
        }

        public static function get_access_token_saved($email)
        {
            $access_token = nbdesigner_get_option('nbdesigner_printcart_api_access_token');
            if (!$access_token)
                return '';

            $response = wp_remote_request(self::$api_url . '/account', array(
                'method' => "GET",
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                ),
            ));

            $store = self::response($response);

            if (isset($store['data']['email']) && $store['data']['email'] == $email) {
                return $access_token;
            }

            delete_option('nbdesigner_printcart_api_access_token');

            return '';
        }

        public static function random_password($length = 8)
        {
            $lowers = 'abcdefghijklmnopqrstuvwxyz';
            $uppers = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $number = '0123456789';
            $symbols = '!@#$%^&*';
            $password = '';
            $password .= substr(str_shuffle($lowers), 0, 3);
            $password .= substr(str_shuffle($uppers), 0, 3);
            $password .= substr(str_shuffle($number), 0, 1);
            $password .= substr(str_shuffle($symbols), 0, 1);
            $password = str_shuffle($password);
            return $password;
        }

        public static function create_user_with_store($data)
        {
            $response = array(
                'flag' => 0,
                'message' => '',
                'reg_user' => 0,
                'reg_store' => 0,
                'email_exist' => 0
            );

            $name = isset($data['name']) ? $data['name'] : '';
            $email = isset($data['email']) ? $data['email'] : '';
            $password = self::random_password();
            $store_name = isset($data['store_name']) ? $data['store_name'] : '';
            $shop_url = isset($data['shop_url']) ? $data['shop_url'] : '';
            $is_https = self::is_https($shop_url);
            if (!$is_https) {
                $response['message'] = self::$mess_ssl;
                return $response;
            }
            $consumer_key = isset($data['consumer_key']) ? $data['consumer_key'] : '';
            $consumer_secret = isset($data['consumer_secret']) ? $data['consumer_secret'] : '';
            $integration = 'woocommerce';
            $integration_details = array(
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
            );
            if (!$name || !$email || !$store_name || !$shop_url || !$consumer_key || !$consumer_secret) {
                $mess = !$name ? 'Name is required. ' : '';
                $mess .= !$email ? 'Email is required. ' : '';
                $mess .= !$store_name ? 'Store name is required. ' : '';
                $mess .= !$shop_url ? 'Shop URL is required. ' : '';
                $mess .= !$consumer_key ? 'Consumer key is required. ' : '';
                $mess .= !$consumer_secret ? 'Consumer secret is required. ' : '';
                $response['message'] = esc_html__('Invalid input! ' . $mess, 'web-to-print-online-designer');
                return $response;
            }

            // Create user

            $access_token = self::get_access_token_saved($email);
            $user = array();
            if (!$access_token) {
                $user = wp_remote_request(self::$api_url . '/account', array(
                    'body' => array(
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                    ),
                    'method' => "POST",
                ));

                $user = self::response($user);

                if (isset($user['error'])) {
                    if (!empty($user['error']['email'][0])) {
                        $response['message'] = $user['error']['email'][0];
                    } else {
                        $response['message'] = esc_html__('User creation failed', 'web-to-print-online-designer');
                    }
                    $response['email_exist'] = 1;
                    return $response;
                }

                $access_token = isset($user['data']) && isset($user['data']['access_token']) ? $user['data']['access_token'] : '';

                if ($access_token) {
                    $response['user'] = $user;
                    $response['reg_user'] = 1;
                }
            }

            if ($access_token) {
                update_option('nbdesigner_printcart_api_access_token', $access_token);
                $store = self::response(wp_remote_request(self::$api_url . '/stores', array(
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
                    $response['reg_store'] = 1;
                    $response['sid'] = $sid;
                    $response['secret'] = $secret;
                    $response['unauth_token'] = $unauth_token;
                    update_option('nbdesigner_printcart_api_email', $email);
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
                    return $response;
                }
            } else {
                $response['message'] = 'Connection failed.';
                if (isset($user['error']) && isset($user['error']['email'])) {
                    $response['message'] = wp_kses(__('The email has already been taken. Please try with another email. <a href="' . self::$dashboard_url . '" target="_blank">Go to Dashboard</a>', 'web-to-print-online-designer'), array(
                        'a' => array('href' => array(), 'target' => array())
                    ));
                    $response['email_exist'] = 1;
                } else {
                    $response['message'] = esc_html__('User creation failed', 'web-to-print-online-designer');
                }
                return $response;
            }
            return $response;
        }

        public static function fetchDataWithAuth($url, $auth)
        {

            $response = wp_remote_get($url, array(
                'headers' => $auth,
            ));

            return self::response($response);
        }

        public static function fetchStoreDetail()
        {
            $url = self::$api_url . '/stores/store-details';

            $response = self::fetchData(
                $url
            );

            return self::response($response);
        }

        public static function fetchStores()
        {
            $access_token = nbdesigner_get_option('nbdesigner_printcart_api_access_token');
            if (!$access_token)
                return '';

            $response = wp_remote_request(self::$api_url . '/stores', array(
                'method' => "GET",
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                ),
            ));
            return self::response($response);
        }

        public static function fetchStoreDetailWithAuth($auth)
        {
            $url = self::$api_url . '/stores/store-details';

            return self::fetchDataWithAuth($url, $auth);
        }

        public static function fetchAccountWithBasicAuth($auth)
        {
            $url = self::$api_url . '/account';

            return self::fetchDataWithAuth($url, $auth);
        }
        public static function fetchAccount()
        {
            $url = self::$api_url . '/account';

            $headers = self::get_header_basic_auth();

            $response = wp_remote_request($url, array(
                'headers' => $headers,
                'method' => "GET",
            ));

            return self::response($response);
        }
        public static function login($email, $password)
        {
            $response = wp_remote_request(self::$api_url . '/account/signIn', array(
                'body' => array(
                    'email' => $email,
                    'password' => $password,
                ),
                'method' => "POST",
            ));

            return self::response($response);
        }
    }
}
