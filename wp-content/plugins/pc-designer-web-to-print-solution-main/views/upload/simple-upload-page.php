<?php if (!defined('ABSPATH')) exit;
    $product_id         = ( isset( $_GET['product_id'] ) && $_GET['product_id'] != '' ) ? absint( sanitize_text_field($_GET['product_id']) ) : 0;
    $variation_id       = ( isset( $_GET['variation_id'] ) && $_GET['variation_id'] != '' ) ? absint( sanitize_text_field($_GET['variation_id']) ) : 0;
    $nbu_ui_mode        = 2;
    $error_redirec      = false;
    $option_id          = false;
    if( $product_id == 0 ){
        $error_redirec  = true;
    }else{
        global $product;
        $product        = wc_get_product( $product_id );
        if( is_object( $product ) ){
            $product_type   = $product->get_type();  
            $enable = get_post_meta($product_id, '_nbo_enable', true);
            if( $enable ){
                $option_id = get_transient( 'nbo_product_'.$product_id );
            }
            $show_nbo_option = ( $option_id || $product_type == 'variable' ) ? true : false;
            if( $show_nbo_option ){
                $wc_add_to_cart_params = array(
                    'wc_ajax_url'                      => WC_AJAX::get_endpoint( '%%endpoint%%' ),
                    'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'web-to-print-online-designer' ),
                    'i18n_make_a_selection_text'       => esc_attr__( 'Please select some product options before adding this product to your cart.', 'web-to-print-online-designer' ),
                    'i18n_unavailable_text'            => esc_attr__( 'Sorry, this product is unavailable. Please choose a different combination.', 'web-to-print-online-designer' )
                );
                $nbds_frontend = array(
                    'currency_format_num_decimals'                  =>  wc_get_price_decimals(),
                    'currency_format_symbol'                        =>  html_entity_decode( (string) get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8'),
                    'currency_format_decimal_sep'                   =>  stripslashes( wc_get_price_decimal_separator() ),
                    'currency_format_thousand_sep'                  =>  stripslashes( wc_get_price_thousand_separator() ),
                    'currency_format'                               =>  esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format()) ),
                    'nbdesigner_hide_add_cart_until_form_filled'    =>  nbdesigner_get_option('nbdesigner_hide_add_cart_until_form_filled')
                );
            }
            $option     = maybe_unserialize( get_post_meta( $product_id, '_nbdesigner_upload', true ) );
        }else{
            $error_redirec  = true;
        }
    }
    if( !$error_redirec ){
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo get_bloginfo( 'name' ); ?> - <?php esc_html_e('Upload photos', 'web-to-print-online-designer'); ?></title>
        <?php do_action( 'pc_head', 'simple-upload-page' ); ?>
    </head>
    <body>
        <div class="nbd-m-upload-design-wrap">
            <?php include NBDESIGNER_PLUGIN_DIR.'templates/single-product/simple-upload.php'; ?>
        </div>
        <div class="nbo-options-overlay"></div>
        <div id="nbu-upload-nbo-options">
            <div class="nbu-options-header">
                <span><b><?php esc_html_e('Choose options', 'web-to-print-online-designer'); ?></b></span>
            </div>
            <div class="nbu-options-nbo-wrapper">
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </div>
        <?php 
            if( $show_nbo_option ) {
                wc_get_template( 'single-product/add-to-cart/variation.php' );
                $depend = array('pc-lodash-n', 'wc-accounting', 'wc-add-to-cart');
                if( $product_type == 'variable' ) {
                    $depend[] = 'pc-simple-upload-page';
                    $depend[] = 'wc-jquery-blockui';
                    $depend[] = 'wc-add-to-cart-variation';
                }
                do_action( 'pc_footer', 'simple-upload-page', $depend );
            }
        ?>
        <!-- No inline scripts or styles unless dynamic. -->
        <script type="text/javascript">
            <?php if( $show_nbo_option ): ?>
                var wc_add_to_cart_variation_params = <?php echo json_encode( $wc_add_to_cart_params ); ?>;
                var nbds_frontend = <?php echo json_encode( $nbds_frontend ); ?>;
                product_type = "<?php echo( $product_type ); ?>";
            <?php endif; ?>
            var nbd_allow_type  = "<?php echo( $option['allow_type'] ); ?>",
            product_id          = "<?php echo( $product_id ); ?>",
            nbd_disallow_type   = "<?php echo( $option['disallow_type'] ); ?>",
            nbd_number          = parseInt(<?php echo( $option['number'] ); ?>),
            nbd_minsize         = parseInt(<?php echo( $option['minsize'] ); ?>),
            nbd_maxsize         = parseInt(<?php echo( $option['maxsize'] ); ?>),
            nonce               = "<?php echo wp_create_nonce('save-design'); ?>",
            ajax_url            = "<?php echo admin_url('admin-ajax.php'); ?>";
            
            jQuery(document).ready(function(){
                /* Drag & Drop uplod file */
                var nbdDropArea = jQuery('label[for="nbd-file-upload"]'),
                nbdInput = jQuery('#nbd-file-upload');
                var listFileUpload = [];
                ['dragenter', 'dragover'].forEach(function(eventName){
                    nbdDropArea.on(eventName, highlight)
                });
                ['dragleave', 'drop'].forEach(function(eventName){
                    nbdDropArea.on(eventName, unhighlight)
                });
                function highlight(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    nbdDropArea.addClass('highlight');
                };
                function unhighlight(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    nbdDropArea.removeClass('highlight');
                };
                nbdDropArea.on('drop', handleDrop);
                function handleDrop(e) {
                    if( jQuery('#accept-term').length && !jQuery('#accept-term').is(':checked') ) {
                        alert(NBDESIGNCONFIG.nbdlangs.alert_upload_term);
                        return;
                    }else{
                        if(e.originalEvent.dataTransfer){
                            if(e.originalEvent.dataTransfer.files.length) {
                                e.preventDefault();
                                e.stopPropagation();
                                handleFiles(e.originalEvent.dataTransfer.files);
                            }
                        }
                    }
                };
                nbdInput.on('click', function(e){
                    e.stopPropagation();
                });
                nbdInput.on('change', function(){
                    handleFiles(this.files);
                });
                function resetUploadInput(){
                    nbdInput.val('');
                }
                function handleFiles(files) {
                    if(files.length > 0) uploadFile(files);
                }
                function uploadFile(files){
                    var file = files[0],
                    type = file.type.toLowerCase();
                    if( listFileUpload.length > (nbd_number-1) ) {
                        alert('Exceed number of upload files!');
                        return;
                    }
                    if( type == '' ){
                        type = file.name.substring(file.name.lastIndexOf('.')+1).toLowerCase();
                    }
                    type = type == 'image/jpeg' ? 'image/jpg' : type;
                    if( nbd_disallow_type != '' ){
                        var nbd_disallow_type_arr = nbd_disallow_type.toLowerCase().split(',');
                        var check = false;
                        nbd_disallow_type_arr.forEach(function(value){
                            value = value == 'jpeg' ? 'jpg' : value;
                            if( type.indexOf(value) > -1 ){
                                check = true;
                            }
                        });
                        if( check ){
                            resetUploadInput();
                            alert('Disallow extensions: ' + nbd_disallow_type);
                            return;
                        }
                    }
                    if( nbd_allow_type != '' ){
                        var nbd_allow_type_arr = nbd_allow_type.toLowerCase().split(',');
                        var check = false;
                        nbd_allow_type_arr.forEach(function(value){
                            value = value == 'jpeg' ? 'jpg' : value;
                            if( type.indexOf(value) > -1 ){
                                check = true;
                            }
                        });
                        if( !check ){
                            resetUploadInput();
                            alert('Only support: ' + nbd_allow_type);
                            return;
                        }
                    }
                    if (file.size > nbd_maxsize * 1024 * 1024 ) {
                        alert('Max file size' + nbd_maxsize + " MB");
                        resetUploadInput();
                        return;
                    }else if(file.size < nbd_minsize * 1024 * 1024){
                        alert('Min file size' + nbd_minsize + " MB");
                        resetUploadInput();
                        return;
                    };
                    var formData = new FormData;
                    formData.append('file', file);
                    jQuery('.nbd-upload-loading').addClass('is-visible');
                    jQuery('.nbu-upload-zone label').addClass('is-loading');
                    jQuery('.nbd-m-upload-design-wrap').addClass('is-loading');
                    var first_time = listFileUpload.length > 0 ? 2 : 1;
                    var variation_id = 0;
                    formData.append('first_time', first_time);
                    formData.append('action', 'nbd_upload_design_file');
                    formData.append('task', 'new');
                    formData.append('product_id', product_id);
                    formData.append('variation_id', variation_id);
                    formData.append('nonce', nonce);
                    jQuery.ajax({
                        url: ajax_url,
                        method: "POST",
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: formData,
                        complete: function() {
                            jQuery('.nbd-upload-loading').removeClass('is-visible');
                            jQuery('.nbu-upload-zone label').removeClass('is-loading');
                            jQuery('.nbd-m-upload-design-wrap').removeClass('is-loading');
                        },
                        success: function(data) {
                            if( data.flag == 1 ){
                                listFileUpload.push( { src : data.src, name : data.name } );
                                buildPreviewUpload();
                            }else{
                                alert(data.mes);
                            }
                            resetUploadInput();
                        }
                    });
                }
                window.removeUploadFile = function(index){
                    listFileUpload.splice(index, 1);
                    resetUploadInput();
                    buildPreviewUpload();
                };
                function buildPreviewUpload(){
                    update_nbu_value(listFileUpload); 
                    var html = '';
                    listFileUpload.forEach(function(file, index){
                        html += '<div class="nbd-upload-items"><div class="nbd-upload-items-inner"><img src="'+file.src+'" class="shadow nbd-upload-item"/><p class="nbd-upload-item-title">'+file.name+'</p><span class="shadow" onclick="removeUploadFile('+index+')" >&times;</span></div></div>';
                    });
                    jQuery('.upload-design-preview').html(html);
                }
                function update_nbu_value( arr ){
                    var files = '';
                    jQuery.each(arr, function (key, val) {
                        files += key == 0 ? val.name : '|' + val.name;
                    });
                    if( jQuery('form.cart, form.variations_form').find('input[name="nbd-upload-files"]').length == 0 ){
                        jQuery('form.cart, form.variations_form').append('<input name="nbd-upload-files" type="hidden" value="" />');
                    }
                    jQuery('input[name="nbd-upload-files"]').val( files );
                }
                /* submit upload files */
                window.hideUploadFrame = function(){
                    jQuery('form.cart, form.variations_form').append('<input name="submit_form_mode2" type="hidden" value="1" />');
                    jQuery('form.cart').append('<input name="add-to-cart" type="hidden" value="' + product_id + '" />');
                    if( typeof product_type != 'undefined' ){
                        showOptions();
                    }else{
                        jQuery('.variations_form, form.cart').submit();
                    }
                };
                function showOptions(){
                    jQuery('.nbo-options-overlay').addClass('active');
                    jQuery('#nbu-upload-nbo-options').addClass('active');
                }
                jQuery('.nbo-options-overlay').on('click', function(){
                    jQuery('#nbu-upload-nbo-options').removeClass('active');
                    jQuery('.nbo-options-overlay').removeClass('active');
                });
            });
        </script>
    </body>
</html>
<?php  
} else {
    wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
}