<?php
defined( 'ABSPATH' ) || exit;
class PC_Script_Hook {
    protected static $instance;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct() {
        //todo
    }
    public function init(){
    
        add_action( 'pc_head', array( $this, 'pc_enqueue_script_head' ), 1, 2 );

        add_action( 'pc_footer', array( $this, 'pc_enqueue_script_footer' ), 1, 2 );

        add_action( 'pc_custom_hook', array( $this, 'pc_custom_hook' ), 1, 2 );

        add_filter( 'script_loader_tag', array( $this, 'pc_add_param_script' ), 10, 2 );

    }

    public function pc_print_styles( $handles = false ) {
        global $wp_styles;

        _wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

        if ( ! ( $wp_styles instanceof WP_Styles ) ) {
            if ( ! $handles ) {
                return array(); // No need to instantiate if nothing is there.
            }
        }

        return wp_styles()->do_items( $handles );
    }

    public function pc_print_scripts( $handles = false ) {
        global $wp_scripts;

        _wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

        if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
            if ( ! $handles ) {
                return array(); // No need to instantiate if nothing is there.
            }
        }

        return wp_scripts()->do_items( $handles );
    }

    public function pc_enqueue_style( $handles = false ) {

        if(is_array($handles) && count($handles) > 0) {
            wp_enqueue_style( $handles );
            $this->pc_print_styles( $handles );
        }
    }

    public function pc_enqueue_script( $handles = false ) {

        if(is_array($handles) && count($handles) > 0) {
            wp_enqueue_script( $handles );
            $this->pc_print_scripts( $handles );
        }
    }

    // PC custom add param script
    public function pc_add_param_script($tag, $handle){
        if ( 'dropboxjs' == $handle ) {
            $dbID = nbdesigner_get_option('nbdesigner_dropbox_app_id', '');
            if($dbID) {
                return str_replace( ' src', ' data-app-key="' . $dbID . '" src', $tag );
            }
        }
        if ( 'googleapijs' == $handle ) {
            $enbleGG    = nbdesigner_get_option('nbdesigner_enable_drive_file_upload', 'yes');
            if($enbleGG) {
                $enbleGG_str = $enbleGG == 'yes' ? 'true' : '';
                return str_replace( ' src', ' gapi_processed="' . $enbleGG_str . '" src', $tag );
            }
        }

        if ( 'googleapijs-on' == $handle ) {
            return str_replace( ' src', ' gapi_processed="true" src', $tag );
        }
        return $tag;
    }

    public function pc_custom_hook( $page ) {
        $recaptcha_key = nbdesigner_get_option( 'nbdesigner_v3_recaptcha_key', '' );
        wp_register_script( 'dropboxjs', 'https://www.dropbox.com/static/api/2/dropins.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'googleapijs', 'https://apis.google.com/js/api.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'recaptchajs', 'https://www.google.com/recaptcha/api.js?render=' . $recaptcha_key, array(), NBDESIGNER_VERSION, true );

        if( $page == 'dropboxjs' ) {
            $this->pc_enqueue_script( array( 'dropboxjs' ) );
        }

        if( $page == 'googleapijs' ) {
            $this->pc_enqueue_script( array( 'googleapijs' ) );
        }

        if( $page == 'googleapijs-on' ) {
            $this->pc_enqueue_script( array( 'googleapijs' ) );
        }
        if( $page == 'recaptchajs' ) {
            $this->pc_enqueue_script( array( 'recaptchajs' ) );
        }
    }
    
    public function pc_enqueue_script_head($page, $depend = false) {

        wp_register_style( 'pc-roboto-font', 'https://fonts.googleapis.com/css?family=Roboto:400,100,300italic,300', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-poppins-font-r', 'https://fonts.googleapis.com/css?family=Poppins:400,400i,700,700i', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-roboto-font-r', 'https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-poppins-font', 'https://fonts.googleapis.com/css?family=Poppins:400,100,300italic,300', array(), NBDESIGNER_VERSION );

        wp_register_style( 'pc-template-mobile', NBDESIGNER_CSS_URL .'templates/mobile.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-advanced-upload', NBDESIGNER_CSS_URL .'templates/advanced-upload.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-option-builder', NBDESIGNER_CSS_URL .'templates/option-builder.css', array(), NBDESIGNER_VERSION );

        wp_register_style( 'pc-license-notice', NBDESIGNER_CSS_URL .'views/license-notice.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-user-profile', NBDESIGNER_CSS_URL .'views/user-profile.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-product-builder', NBDESIGNER_CSS_URL .'views/product-builder.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-advanced-upload-page', NBDESIGNER_CSS_URL .'views/advanced-upload-page.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-simple-upload-page', NBDESIGNER_CSS_URL .'views/simple-upload-page.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-vista-page', NBDESIGNER_CSS_URL .'views/vista.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-manager-fonts', NBDESIGNER_CSS_URL .'views/nbdesigner-manager-fonts.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-3d-preview', NBDESIGNER_CSS_URL .'views/3d-preview.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-nbdesigner-tools', NBDESIGNER_CSS_URL .'views/nbdesigner-tools.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-support', NBDESIGNER_CSS_URL .'views/support.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-system-info-all', NBDESIGNER_CSS_URL .'views/system-info-all.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-system-info', NBDESIGNER_CSS_URL .'views/system-info.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-loading', NBDESIGNER_CSS_URL .'views/loading.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-quote-button', NBDESIGNER_CSS_URL .'views/quote-button.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-tags-extra-fields', NBDESIGNER_CSS_URL .'views/tags-extra-fields.css', array(), NBDESIGNER_VERSION );

        wp_register_style( 'pc-jquery-ui', NBDESIGNER_CSS_URL .'jquery-ui.min.css', array(), '1.10.4' );
        wp_register_style( 'pc-bootstrap', NBDESIGNER_CSS_URL .'bootstrap.min.css', array(), '3.0.0' );
        wp_register_style( 'pc-tooltipster', NBDESIGNER_CSS_URL .'tooltipster.bundle.min.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-perfect-scrollbar', NBDESIGNER_CSS_URL .'perfect-scrollbar.min.css', array(), '0.8.1' );
        wp_register_style( 'pc-modern', NBDESIGNER_CSS_URL .'modern.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-spectrum', NBDESIGNER_CSS_URL .'spectrum.css', array(), '1.8.0' );
        wp_register_style( 'modern-additional', NBDESIGNER_CSS_URL .'modern-additional.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'modern-rtl', NBDESIGNER_CSS_URL .'modern-rtl.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-custom', NBDESIGNER_DATA_URL . '/custom.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'modern-print-option', NBDESIGNER_CSS_URL .'modern-print-option.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-font-awesome', NBDESIGNER_CSS_URL .'font-awesome.min.css', array(), '4.7.0' );
        wp_register_style( 'pc-bundle', NBDESIGNER_CSS_URL .'bundle.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-style', NBDESIGNER_CSS_URL .'style.min.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-custom-style', NBDESIGNER_CSS_URL .'custom.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'nbdesigner-rtl', NBDESIGNER_CSS_URL .'nbdesigner-rtl.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-app-product-builder', NBDESIGNER_CSS_URL .'app-product-builder.css', array(), NBDESIGNER_VERSION );
        wp_register_style( 'pc-live-chat', NBDESIGNER_CSS_URL .'live-chat.css', array(), NBDESIGNER_VERSION );

        wp_register_script( 'pc-template-mobile', NBDESIGNER_JS_URL .'templates/mobile.js', array(), NBDESIGNER_VERSION );

        wp_register_script( 'pc-angular', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.6.9/angular.min.js', array(), '1.6.9', true );
        wp_register_script( 'pc-printcart-jq', NBDESIGNER_PLUGIN_URL .'assets/libs/printcart-jq.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-printcart-ext', NBDESIGNER_PLUGIN_URL .'assets/libs/printcart-ext.js', array(), NBDESIGNER_VERSION, true );

        wp_register_script( 'wc-accounting',  WC()->plugin_url().'/assets/js/accounting/accounting.min.js', array(), '0.4.2', true );

        if($page == 'template-mobile') {
            $this->pc_enqueue_style( array( 'pc-roboto-font', 'pc-template-mobile' ) );
            $this->pc_enqueue_script( array( 'pc-template-mobile' ) );
        }

        if($page == 'advanced-upload') {
            $this->pc_enqueue_style( array( 'pc-advanced-upload' ) );
        }

        if($page == 'license-notice') {
            $this->pc_enqueue_style( array( 'pc-license-notice' ) );
        }

        if($page == 'user-profile') {
            $this->pc_enqueue_style( array( 'pc-user-profile' ) );
        }

        if($page == 'vista-page') {
            $this->pc_enqueue_style( array( 'pc-vista-page' ) );
        }

        if($page == 'option-builder') {
            $this->pc_enqueue_style( array( 'pc-option-builder' ) );
        }

        if($page == 'manager-fonts') {
            $this->pc_enqueue_style( array( 'pc-manager-fonts' ) );
        }

        if($page == '3d-preview') {
            $this->pc_enqueue_style( array( 'pc-3d-preview' ) );
        }

        if($page == 'nbdesigner-tools') {
            $this->pc_enqueue_style( array( 'pc-nbdesigner-tools' ) );
        }

        if($page == 'support') {
            $this->pc_enqueue_style( array( 'pc-support' ) );
        }

        if($page == 'system-info-all') {
            $this->pc_enqueue_style( array( 'pc-system-info-all' ) );
        }

        if($page == 'system-info') {
            $this->pc_enqueue_style( array( 'pc-system-info' ) );
        }

        if($page == 'loading') {
            $this->pc_enqueue_style( array( 'pc-loading' ) );
        }

        if($page == 'quote-button') {
            $this->pc_enqueue_style( array( 'pc-quote-button' ) );
        }

        if($page == 'tags-extra-fields') {
            $this->pc_enqueue_style( array( 'pc-tags-extra-fields' ) );
        }

        if($page == 'live-chat') {
            $this->pc_enqueue_style( array( 'pc-live-chat' ) );
        }

        if($page == 'advanced-upload-page') {
            $this->pc_enqueue_style( array( 'pc-poppins-font-r', 'pc-advanced-upload-page' ) );
            $this->pc_enqueue_script( array( 'pc-printcart-ext', 'pc-angular' ) );
        }

        if($page == 'simple-upload-page') {
            $this->pc_enqueue_style( array( 'pc-poppins-font-r', 'pc-simple-upload-page' ) );
            $this->pc_enqueue_script( array( 'pc-printcart-jq', 'pc-angular' ) );
        }

        if($page == 'product-builder') {
            $this->pc_enqueue_style( array( 'pc-poppins-font-r', 'pc-spectrum', 'pc-app-product-builder', 'pc-product-builder' ) );
            $this->pc_enqueue_script( array( 'pc-printcart-ext', 'pc-angular', 'wc-accounting' ) );
        }

        if($page == 'pc-frontend-modern') {
            if(is_array($depend) && count($depend) > 0) {
                $depend[] = 'pc-custom-style';
                $this->pc_enqueue_style( $depend );
            }
        }

        if($page == 'pc-frontend-template') {
            if(is_array($depend) && count($depend) > 0) {
                $depend[] = 'pc-custom-style';
                $this->pc_enqueue_style( $depend );
            }
        }
    }

    public function _pc_enqueue_script() {
        do_action( '_pc_enqueue_script' );
    }

    public function pc_enqueue_script_footer($page, $depend = false) {
        wp_register_script( 'firebase-app', 'https://www.gstatic.com/firebasejs/7.14.5/firebase-app.js', array(), '7.14.5', true );
        wp_register_script( 'firebase-auth', 'https://www.gstatic.com/firebasejs/7.14.5/firebase-auth.js', array(), '7.14.5', true );
        wp_register_script( 'firebase-database', 'https://www.gstatic.com/firebasejs/7.14.5/firebase-database.js', array(), '2.1.1', true );
        wp_register_script( 'pc-live-chat', NBDESIGNER_JS_URL . 'live-chat.js', array(), NBDESIGNER_VERSION );
        wp_register_script( 'pc-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js', array('jquery'), '1.10.4', true );
        wp_register_script( 'pc-angular', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.6.9/angular.min.js', array(), '1.6.9', true );
        wp_register_script( 'pc-opentype', 'https://cdn.jsdelivr.net/npm/opentype.js@latest/dist/opentype.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/js/bootstrap.min.js', array(), '3.2.0', true );
        wp_register_script( 'pc-angular-rc', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.3.0-rc.2/angular.min.js', array(), '1.3.0', true );
        wp_register_script( 'pc-lodash', 'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/2.4.1/lodash.js', array(), '2.4.1', true );
        wp_register_script( 'pc-lodash-min', 'https://cdn.jsdelivr.net/npm/lodash@4.17.11/lodash.min.js', array(), '4.17.11', true );
        wp_register_script( 'pc-lodash-n', 'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.10/lodash.js', array(), '4.17.10', true );
        wp_register_script( 'pc-jspdf-umd', 'https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js', array(), NBDESIGNER_VERSION, true );

        wp_register_script( 'pc-jquery-ui-dev', NBDESIGNER_ASSETS_URL .'libs/jquery-ui.min.js', array('jquery'), '1.10.4', true );
        wp_register_script( 'pc-angular-dev', NBDESIGNER_ASSETS_URL .'libs/angular-1.6.9.min.js',array('jquery'), '1.6.9', true );
        wp_register_script( 'pc-perfect-scrollbar', NBDESIGNER_ASSETS_URL .'libs/perfect-scrollbar.min.js',array('jquery'), '0.8.1', true );
        wp_register_script( 'pc-lib-moment', NBDESIGNER_PLUGIN_URL .'assets/libs/moment.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-lib-Chart', NBDESIGNER_PLUGIN_URL .'assets/libs/Chart.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'bundle-modern', NBDESIGNER_JS_URL .'bundle-modern.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'sticker-contour', NBDESIGNER_JS_URL .'sticker/contour.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-designer-modern', NBDESIGNER_JS_URL .'designer-modern.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-app-modern', NBDESIGNER_JS_URL .'app-modern.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-custom', NBDESIGNER_DATA_URL . '/custom.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-touch', NBDESIGNER_JS_URL .'touch.js', array(), '0.2.3', true );
        wp_register_script( 'pc-bootstrap-dev', NBDESIGNER_PLUGIN_URL .'assets/libs/bootstrap.min.js', array(), '3.4.1', true );
        wp_register_script( 'pc-angular-rc-dev', NBDESIGNER_PLUGIN_URL .'assets/libs/angular-RC-2.min.js', array('jquery'), '1.3.0', true );
        wp_register_script( 'pc-lodash-dev', NBDESIGNER_PLUGIN_URL .'assets/libs/lodash.js', array(), '2.4.1', true );
        wp_register_script( 'pc-fontfaceobserver', NBDESIGNER_PLUGIN_URL .'assets/libs/fontfaceobserver.js', array(), '2.0.13', true );
        wp_register_script( 'pc-fabric', NBDESIGNER_PLUGIN_URL .'assets/libs/fabric.2.6.0.min.js', array(), '2.6.0', true );
        wp_register_script( 'pc-printcart-jq', NBDESIGNER_PLUGIN_URL .'assets/libs/printcart-jq.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-printcart-ext', NBDESIGNER_PLUGIN_URL .'assets/libs/printcart-ext.js', array(), NBDESIGNER_VERSION, true );

        wp_register_script( 'pc-bundle', NBDESIGNER_JS_URL .'bundle.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'fabric-curvedText', NBDESIGNER_JS_URL .'fabric.curvedText.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'fabric-removeColor', NBDESIGNER_JS_URL .'fabric.removeColor.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-layout', NBDESIGNER_JS_URL .'_layout.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-spectrum', NBDESIGNER_JS_URL .'spectrum.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-qrcode', NBDESIGNER_JS_URL .'qrcode.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-add-to-cart-variation', NBDESIGNER_JS_URL .'add-to-cart-variation.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-designer', NBDESIGNER_JS_URL .'designer.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-app-product-builder', NBDESIGNER_JS_URL .'app-product-builder.js', array(), NBDESIGNER_VERSION, true );

        wp_register_script( 'wc-accounting',  WC()->plugin_url().'/assets/js/accounting/accounting.min.js', array(), '0.4.2', true );
        wp_register_script( 'wc-jquery-blockui',  WC()->plugin_url().'/assets/js/jquery-blockui/jquery.blockUI.min.js', array(), '2.70.0', true );
        wp_register_script( 'wc-add-to-cart',  WC()->plugin_url().'/assets/js/frontend/add-to-cart.min.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'wc-add-to-cart-variation',  WC()->plugin_url().'/assets/js/frontend/add-to-cart-variation.min.js', array(), NBDESIGNER_VERSION, true );

        wp_register_script( 'pc-gallary-sidebar', NBDESIGNER_JS_URL .'templates/sidebar.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-mydesign-edit-info',  NBDESIGNER_JS_URL .'templates/edit-info.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-variation-bulk', NBDESIGNER_JS_URL .'templates/variation-bulk.js', array(), NBDESIGNER_VERSION, true );

        wp_register_script( 'pc-license-form', NBDESIGNER_JS_URL .'views/license-form.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-license-notice', NBDESIGNER_JS_URL .'views/license-notice.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-manager-product', NBDESIGNER_JS_URL .'views/manager-product.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-tools', NBDESIGNER_JS_URL .'views/tools.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-nbdesigner-tools', NBDESIGNER_JS_URL .'views/nbdesigner-tools.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-nbdesigner-tools-action', NBDESIGNER_JS_URL .'views/nbdesigner-tools-action.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-nbdesigner-translate', NBDESIGNER_JS_URL .'views/nbdesigner-translate.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-modal-upload', NBDESIGNER_JS_URL .'views/modal-upload.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-facebook-chat', NBDESIGNER_JS_URL .'views/facebook-chat.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-advanced-upload-page', NBDESIGNER_JS_URL .'views/advanced-upload-page.js', array(), NBDESIGNER_VERSION, true );
        wp_register_script( 'pc-simple-upload-page', NBDESIGNER_JS_URL .'views/simple-upload-page.js', array(), NBDESIGNER_VERSION, true );

        if( $page == 'live-chat') {
            $depend_arr = array( 'firebase-app', 'firebase-auth', 'firebase-database', 'pc-perfect-scrollbar', 'pc-live-chat' );
            $this->pc_enqueue_script( $depend_arr );
        }

        if($page == 'gallary-sidebar') {
            $this->pc_enqueue_script( array( 'pc-gallary-sidebar' ) );
        }

        if($page == 'launcher-dashboard') {
            $this->pc_enqueue_script( array( 'pc-lib-moment', 'pc-lib-Chart' ) );
        }

        if($page == 'mydesign-edit-info') {
            $this->pc_enqueue_script( array( 'pc-mydesign-edit-info' ) );
        }

        if($page == 'variation-bulk') {
            $this->pc_enqueue_script( array( 'pc-variation-bulk' ) );
        }

        if($page == 'license-form') {
            $this->pc_enqueue_script( array( 'pc-license-form' ) );
        }

        if($page == 'license-notice') {
            $this->pc_enqueue_script( array( 'pc-license-notice' ) );
        }

        if($page == 'manager-product') {
            $this->pc_enqueue_script( array( 'pc-manager-product' ) );
        }

        if($page == 'nbdesigner-tools') {
            $this->pc_enqueue_script( array( 'pc-tools', 'pc-nbdesigner-tools', 'pc-nbdesigner-tools-action' ) );
        }

        if($page == 'nbdesigner-translate') {
            $this->pc_enqueue_script( array( 'pc-nbdesigner-translate' ) );
        }

        if($page == 'modal-upload') {
            $this->pc_enqueue_script( array( 'pc-modal-upload' ) );
        }

        if($page == 'facebook-chat') {
            $this->pc_enqueue_script( array( 'pc-facebook-chat' ) );
        }

        if($page == 'vista-page') {
            $this->pc_enqueue_script( array( 'pc-opentype' ) );
        }

        if($page == 'advanced-upload-page') {
            if(is_array($depend) && count($depend) > 0) {
                $this->pc_enqueue_script( $depend );
            }
        }

        if($page == 'simple-upload-page') {
            if(is_array($depend) && count($depend) > 0) {
                $this->pc_enqueue_script( $depend );
            }
        }

        if($page == 'product-builder') {
            $this->pc_enqueue_script( array( 'pc-lodash-min', 'pc-fontfaceobserver', 'pc-spectrum', 'pc-fabric', 'pc-app-product-builder'  ) );
        }

        if($page == 'pc-frontend-modern-1') {
            if(is_array($depend) && count($depend) > 0) {
                $this->pc_enqueue_script( $depend );
            }
        }

        if($page == 'pc-frontend-modern-2') {
            if(is_array($depend) && count($depend) > 0) {
                $this->pc_enqueue_script( $depend );
            }
        }

        if($page == 'pc-frontend-template') {
            if(is_array($depend) && count($depend) > 0) {
                $this->pc_enqueue_script( $depend );
            }
        }
    }

}
$pc_script_hook = PC_Script_Hook::instance();
$pc_script_hook->init();
