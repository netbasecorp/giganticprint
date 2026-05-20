<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly  ?>
<!DOCTYPE html>
<?php 
    global $is_IE, $nbd_printing_options, $is_iphone;
    if( $is_IE ){
        include NBDESIGNER_PLUGIN_DIR . 'views/editor_components/ie_notify.php';
        die();
    }
    include 'signature.php';
?>
<?php
    $hide_on_mobile = nbdesigner_get_option( 'nbdesigner_disable_on_smartphones' );
    $lang_code      = str_replace( '-', '_', get_locale() );
    $locale         = substr( $lang_code, 0, 2 );
    $product_id     = ( isset( $_GET['product_id'] ) &&  $_GET['product_id'] != '' ) ? absint( sanitize_text_field($_GET['product_id']) ) : 0;
    if( !is_nbd_product( $product_id ) && ! ( isset( $_GET['design_type'] ) && $_GET['design_type'] == 'edit_order' ) ){
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 ); exit();
    }
    $variation_id  = ( isset( $_GET['variation_id'] ) &&  $_GET['variation_id'] != '' ) ? absint( sanitize_text_field($_GET['variation_id']) ) : 0;
    $default_font  = nbd_get_default_font();
    $_default_font = str_replace( " ", "+", json_decode( $default_font)->alias );
    $_product      = wc_get_product( $product_id );
    $nbd_printing_options->get_product_option( $product_id );
    if( !is_object( $_product ) ){
        wp_redirect( untrailingslashit( get_option( 'home' ) ) );
        exit;
    }
    $product_type   = $_product->get_type();
    $task           = (isset($_GET['task']) &&  $_GET['task'] != '') ? wc_clean($_GET['task']) : 'new';
    $task2          = (isset($_GET['task2']) &&  $_GET['task2'] != '') ? wc_clean($_GET['task2']) : '';
    $ui_mode        = is_nbd_design_page() ? 2 : 1;/*1: Iframe popup, 2: Editor page, 3: Div in detail product*/
    if(wp_is_mobile() && $hide_on_mobile == 'yes'):
    nbdesigner_get_template( 'mobile.php', array( 'lang_code' => $lang_code, 'ui_mode' => $ui_mode ) );
    else: 
    if( !nbd_check_permission() ):
    nbdesigner_get_template( 'permission.php' );
    else:
    $option_id = false;
    if( $ui_mode == 2 ){
        $enable = get_post_meta( $product_id, '_nbo_enable', true );
        if( $enable ){
            $option_id = get_transient( 'nbo_product_'.$product_id );
        }
    }
    $show_nbo_option    =  (($option_id || $product_type == 'variable') && $ui_mode == 2 && ($task == 'new' || $task2 == 'update') ) ? true : false;
    $valid_license      = nbd_check_license();
?>
<html lang="<?php echo( $lang_code ); ?>">
    <head>
        <?php
            /* Meta data */
            include NBDESIGNER_PLUGIN_DIR . 'views/editor_components/meta_data.php'; 
            if( nbdesigner_get_option('nbdesigner_share_design') == 'yes' && isset( $_GET['nbd_share_id'] ) && $_GET['nbd_share_id'] != '' ){
                include NBDESIGNER_PLUGIN_DIR . 'views/editor_components/meta_data_share.php'; 
            }
            // No inline scripts or styles unless dynamic.
            echo '<link href="https://fonts.googleapis.com/css?family='. $_default_font . ':400,400i,700,700i" rel="stylesheet" type="text/css">';
            $depend_css = array('pc-poppins-font-r', 'pc-roboto-font-r', 'pc-jquery-ui', 'pc-bootstrap', 'pc-tooltipster', 'pc-perfect-scrollbar', 'pc-modern', 'pc-spectrum', 'modern-additional');
            if(is_rtl()) {
                $depend_css[] = 'modern-rtl';
            }
            do_action( 'nbd_extra_css', $ui_mode );
            if( file_exists( NBDESIGNER_DATA_DIR . '/custom.css' ) ) {
                $depend_css[] = 'pc-custom';
            }
            if( $show_nbo_option ) {
                $depend_css[] = 'modern-print-option';
            }
            do_action( 'pc_head', 'pc-frontend-modern', $depend_css );
        ?>
        <?php 
            $enableColor            = nbdesigner_get_option( 'nbdesigner_show_all_color', 'yes' );
            $enable_upload_multiple = nbdesigner_get_option( 'nbdesigner_upload_multiple_images', 'no' );
            $design_type            = ( isset($_GET['design_type']) &&  $_GET['design_type'] != '' ) ? wc_clean( $_GET['design_type'] ) : '';
            $nbd_item_key           = ( isset($_GET['nbd_item_key']) &&  $_GET['nbd_item_key'] != '' ) ? wc_clean( $_GET['nbd_item_key'] ) : '';
            $nbu_item_key           = ( isset($_GET['nbu_item_key']) &&  $_GET['nbu_item_key'] != '' ) ? wc_clean( $_GET['nbu_item_key'] ) : '';
            $cart_item_key          = ( isset($_GET['cik']) &&  $_GET['cik'] != '' ) ? wc_clean( $_GET['cik'] ) : '';
            $reference              = ( isset($_GET['reference']) &&  $_GET['reference'] != '' ) ? wc_clean( $_GET['reference'] ) : ''; 

            $redirect_url                   = ( isset( $_GET['rd'] ) &&  $_GET['rd'] != '' ) ? nbd_get_redirect_url() : ( ( $task == 'new' && $ui_mode == 2 ) ? wc_get_cart_url() : '');
            $_enable_upload                 = get_post_meta( $product_id, '_nbdesigner_enable_upload', true );
            $_enable_upload_without_design  = get_post_meta( $product_id, '_nbdesigner_enable_upload_without_design', true );
            $enable_upload                  = $_enable_upload ? 2 : 1;
            $enable_upload_without_design   = $_enable_upload_without_design ? 2 : 1;
            $home_url                       = $icl_home_url = untrailingslashit( get_option( 'home' ) );
            $is_wpml                        = 0;
            $font_url                       = NBDESIGNER_FONT_URL;
            if ( function_exists( 'icl_get_home_url' ) ) {
                $icl_home_url = untrailingslashit( icl_get_home_url() );
                if ( class_exists( 'SitePress' ) ) {
                    global $sitepress;
                    if($sitepress){
                        $wpml_language_negotiation_type = $sitepress->get_setting( 'language_negotiation_type' );
                        if( $wpml_language_negotiation_type == 2 ){
                            $is_wpml    = 1;
                            $font_url   = str_replace( untrailingslashit( get_option( 'home' ) ), untrailingslashit( icl_get_home_url() ), $font_url );
                        }
                    }
                }
            };
            $fbID               = nbdesigner_get_option( 'nbdesigner_facebook_app_id' );
            
            $template_data  = nbd_get_resource_templates( $product_id, $variation_id, false, 0, true );
            $templates      = $template_data['templates'];
            $total_template     = nbd_count_total_template( $product_id, $variation_id );
            $product_data       = nbd_get_product_info( $product_id, $variation_id, $nbd_item_key, $task, $task2, $reference, false, $cart_item_key );
            
            $link_get_options   = add_query_arg(
                urlencode_deep( array(
                    'wc-api'  => 'NBO_Quick_View',
                    'product' => $product_id
                ) ),
                home_url( '/' )
            );
            if( count($_REQUEST) ){
                foreach ( $_REQUEST as $key => $value ){
                    if ( strpos( $key, 'attribute_' ) === 0 ) {
                        $link_get_options .= '&' . urlencode( $key ) . '='. urlencode( $value );
                    }
                }
            }
            if( isset( $_GET['nbo_values'] ) && $_GET['nbo_values'] != '' ){
                $link_get_options .= '&nbo_values='. sanitize_text_field($_GET['nbo_values']);
            }
            $link_edit_option = '';
            if( isset( $_GET['cik'] ) && $_GET['cik'] != '' ){
                $link_get_options .= '&nbo_cart_item_key=' . sanitize_text_field($_GET['cik']);
                if( $task2 != '' ){
                    $link_edit_option = add_query_arg(
                        array(
                            'nbo_cart_item_key'  => sanitize_text_field($_GET['cik'])
                        ),
                        wc_get_product( $variation_id > 0 ? $variation_id : $product_id )->get_permalink()
                    );
                    $link_edit_option = wp_nonce_url( $link_edit_option, 'nbo-edit' );
                }
            }

            $layout             = 'modern';
            include NBDESIGNER_PLUGIN_DIR . 'views/editor_components/js_config.php';

            $enable_3d_preview          = NBD_3D_PREVIEW::enable_3d_preview($product_id) ? 1 : 0;
            $enable_sticker_preview     = 0;
            if( $nbes_settings ){
                $_nbes_settings         = maybe_unserialize( $nbes_settings );
                $enable_sticker_preview = isset( $_nbes_settings['sticker_preview'] ) && $_nbes_settings['sticker_preview'] == 1 ? 1 : 0;
            }
        ?>
    </head>
    <body ng-app="nbd-app" class="nbd-mode-modern nbd-mode-<?php echo( $ui_mode ); ?> <?php echo (is_rtl()) ? 'nbd-modern-rtl' : '';?> <?php if( $is_iphone ) echo 'iphone'; ?>">
        <div style="width: 100%; height: 100%;" ng-controller="designCtrl" ng-click="wraperClickHandle($event)" keypress ng-cloak>
            <div id="design-container">
                <div class="container-fluid" id="designer-controller">
                    <div class="nbd-navigations">
                        <?php include 'modern/main-bar.php';?>
                    </div>
                    <div class="nbd-workspace">
                        <?php include 'modern/sidebar.php';?>
                        <div class="main <?php echo (wp_is_mobile()) ? 'active' : ''; ?>">
                            <?php 
                                include 'modern/toolbar.php';
                                include 'modern/stages.php';
                                include 'modern/toolbar-zoom.php';
                                include 'modern/warning.php';
                                include 'modern/context-menu.php';
                                include 'modern/loading-workflow.php';
                                if( $show_nbo_option && nbdesigner_get_option('nbdesigner_display_product_option') == '2' && !wp_is_mobile() ) include 'modern/await-for-print-options.php';
                                if( $settings['nbdesigner_enable_eyedropper'] == 'yes' ) include 'modern/eyedropper.php';
                            ?>
                        </div>
                    </div>
                    <?php if( $enable_3d_preview ) include 'modern/3d-preview.php'; ?>
                </div>
            </div>
            <?php
                include 'modern/popup.php';
                include 'modern/toasts.php';
                include 'modern/tip.php';
                include 'modern/color-palette.php';
                include 'modern/tour-guide.php';
            ?>
        </div>
        <?php include 'modern/loading-page.php';?>
        <?php do_action( 'nbd_editor_extra_section', $ui_mode ); ?>
        <?php

        $depend_js = array('pc-printcart-ext');
        if(!NBDESIGNER_MODE_DEV) {
            $depend_js[] = 'pc-jquery-ui';
            $depend_js[] = 'pc-angular';
        } else {
            $depend_js[] = 'pc-jquery-ui-dev';
            $depend_js[] = 'pc-angular-dev';
        }
        $depend_js[] = 'bundle-modern';
        
        if( nbdesigner_get_option('nbdesigner_enable_text_check_lang', 'no') == 'yes' || nbdesigner_get_option('nbdesigner_enable_font_to_outlines', 'no') == 'yes' ) {
            $depend_js[] = 'pc-opentype';
        }
        ?>
        <!-- NBO  -->
        <?php if( $show_nbo_option ): ?>
            <?php wc_get_template( 'single-product/add-to-cart/variation.php' ); ?>
            <!-- No inline scripts or styles unless dynamic. -->
            <script type="text/javascript">
                <?php
                    $wc_add_to_cart_params = array(
                        'wc_ajax_url'                      => WC_AJAX::get_endpoint( '%%endpoint%%' ),
                        'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'web-to-print-online-designer' ),
                        'i18n_make_a_selection_text'       => esc_attr__( 'Please select some product options before adding this product to your cart.', 'web-to-print-online-designer' ),
                        'i18n_unavailable_text'            => esc_attr__( 'Sorry, this product is unavailable. Please choose a different combination.', 'web-to-print-online-designer' )
                    );
                    $nbds_frontend = array(
                        'wc_currency_format_num_decimals'               =>  wc_get_price_decimals(),
                        'currency_format_num_decimals'                  =>  nbdesigner_get_option( 'nbdesigner_number_of_decimals', 4 ),
                        'currency_format_symbol'                        =>  html_entity_decode( (string) get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8'),
                        'currency_format_decimal_sep'                   =>  stripslashes( wc_get_price_decimal_separator() ),
                        'currency_format_thousand_sep'                  =>  stripslashes( wc_get_price_thousand_separator() ),
                        'currency_format'                               =>  esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format()) ),
                        'nbdesigner_hide_add_cart_until_form_filled'    =>  nbdesigner_get_option('nbdesigner_hide_add_cart_until_form_filled')
                    );
                ?>
                var wc_add_to_cart_variation_params = <?php echo json_encode($wc_add_to_cart_params); ?>;
                var nbds_frontend                   = <?php echo json_encode($nbds_frontend); ?>;
            </script>
            <?php 
                $depend_js[] = 'wc-accounting';
                if($product_type == 'variable') {
                    $depend_js[] = 'wc-jquery-blockui'; 
                }
                $depend_js[] = 'wc-add-to-cart';
                if($product_type == 'variable') {
                    $depend_js[] = 'wc-add-to-cart-variation'; 
                }
            ?>
        <?php endif; 
        do_action( 'pc_footer', 'pc-frontend-modern-1', $depend_js );
        ?>
        <!-- End. NBO  -->
        <!-- No inline scripts or styles unless dynamic. -->
        <script type="text/javascript">
            NBDESIGNCONFIG.enable_sticker_preview = <?php echo esc_js($enable_sticker_preview ? 1 : 0); ?>;
        </script>
        <?php
        $depend_js_c = array();
        if( $enable_sticker_preview ) {
            $depend_js_c[] = 'sticker-contour';
        }
        do_action( 'nbd_extra_js', $ui_mode ); 
        $depend_js_c[] = 'pc-designer-modern';
        $depend_js_c[] = 'pc-app-modern';
        if(file_exists( NBDESIGNER_DATA_DIR . '/custom.js' )) {
            $depend_js_c[] = 'pc-custom';
        }
        do_action( 'pc_footer', 'pc-frontend-modern-2', $depend_js_c );
        ?>
    </body>
</html>
<?php endif; endif;