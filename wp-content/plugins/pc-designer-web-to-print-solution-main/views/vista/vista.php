<?php
global $product;
$default_font                   = nbd_get_default_font();
$lang_code                      = str_replace( '-', '_', get_locale() );
$locale                         = substr( $lang_code, 0, 2 );
$product_id                     = $product->get_id();
$product_type                   = $product->get_type();
$enableColor                    = nbdesigner_get_option( 'nbdesigner_show_all_color' );
$enable_upload_multiple         = nbdesigner_get_option( 'nbdesigner_upload_multiple_images' );
$task                           = ( isset( $_GET['task'] ) &&  $_GET['task'] != '') ? sanitize_text_field($_GET['task']) : 'new';
$task2                          = ( isset($_GET['task2'] ) &&  $_GET['task2'] != '') ? sanitize_text_field($_GET['task2']) : '';
$design_type                    = ( isset($_GET['design_type'] ) &&  $_GET['design_type'] != '') ? sanitize_text_field($_GET['design_type']) : '';
$nbd_item_key                   = ( isset($_GET['nbd_item_key'] ) &&  $_GET['nbd_item_key'] != '') ? sanitize_text_field($_GET['nbd_item_key']) : '';
$nbu_item_key                   = ( isset($_GET['nbu_item_key'] ) &&  $_GET['nbu_item_key'] != '') ? sanitize_text_field($_GET['nbu_item_key']) : '';
$cart_item_key                  = ( isset($_GET['cik'] ) &&  $_GET['cik'] != '' ) ? sanitize_text_field($_GET['cik']) : '';
$reference                      = ( isset($_GET['reference'] ) &&  $_GET['reference'] != '' ) ? sanitize_text_field($_GET['reference']) : '';
$ui_mode                        = 3;/*1: Iframe popup, 2: Editor page, 3: Div in detail product*/
$redirect_url                   = ( isset( $_GET['rd'] ) &&  $_GET['rd'] != '' ) ? nbd_get_redirect_url() : ( ( $task == 'new' && $ui_mode == 2 ) ? wc_get_cart_url() : '' );
$_enable_upload                 = get_post_meta($product_id, '_nbdesigner_enable_upload', true);
$_enable_upload_without_design  = get_post_meta($product_id, '_nbdesigner_enable_upload_without_design', true);
$enable_upload                  = $_enable_upload ? 2 : 1;
$enable_upload_without_design   = $_enable_upload_without_design ? 2 : 1;
$variation_id                   = 0;
$show_nbo_option                = false;
$home_url = $icl_home_url       = untrailingslashit( get_option( 'home' ) );
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
$fbID               = nbdesigner_get_option('nbdesigner_facebook_app_id');
$dbID               = nbdesigner_get_option('nbdesigner_dropbox_app_id');
$ingId              = nbdesigner_get_option('nbdesigner_instagram_app_id');
$layout             = 'visual';
$product_data       = nbd_get_product_info( $product_id, $variation_id, $nbd_item_key, $task, $task2, $reference, false, $cart_item_key );
$link_get_options   = '';
$template_data      = nbd_get_resource_templates( $product_id, $variation_id, false, 0, true );
$templates          = $template_data['templates'];
$valid_license      = nbd_check_license();

include NBDESIGNER_PLUGIN_DIR . 'views/editor_components/js_config.php';

do_action( 'pc_head', 'vista-page' ); 

?>
<?php $mode3Task = (isset($_GET['nbdv-task']) && $_GET['nbdv-task'] != '') ? sanitize_text_field($_GET['nbdv-task']) : ''; ?>
<div id="nbd-vista-app" class="nbd-mode-vista <?php echo wp_is_mobile() ? 'nbd-mobile' : 'nbd-desktop'; ?> <?php echo (is_rtl()) ? 'nbd-rtl' : ''?> <?php if(isset($_GET['nbdv-task']) && $_GET['nbdv-task'] != '') echo 'nbd-on-task'; ?>">
    <div class="nbd-vista-ctrl" ng-controller="designCtrl" keypress ng-cloak>
        <div id="design-container">
            <div class="container-fluid" id="designer-controller">
                <div class="nbd-vista">
                    <div class="main-vista">
                        <?php include "toolbar.php";?>
                        <div class="v-workspace">
                            <?php include "sidebar.php";?>
                            <?php include "layout.php";?>
                            <?php include "warning.php"; ?>
                            <?php include "toasts.php";?>
                        </div>
                    </div>
                    <?php include 'popup.php';?>
                    <?php include 'context-menu.php';?>
                    <?php include 'loading-app.php';?>
                </div>
                <?php include 'save-template.php';?>
            </div>
        </div>
    </div>
</div>
<?php if( nbdesigner_get_option('nbdesigner_enable_text_check_lang', 'no') == 'yes' || nbdesigner_get_option('nbdesigner_enable_font_to_outlines', 'no') == 'yes' ): ?>
    <?php do_action( 'pc_footer', 'vista-page' ); ?>
<?php endif;