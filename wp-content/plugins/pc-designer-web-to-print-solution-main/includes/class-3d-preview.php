<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('NBD_3D_PREVIEW')) {

    class NBD_3D_PREVIEW
    {
    	public static $enable_feature_3d_preview = false;
        public static function enable_3d_preview($product_id = null) {
        	if(!self::$enable_feature_3d_preview) return false;
        	$product_id = isset( $_GET['product_id'] ) ? absint( sanitize_text_field($_GET['product_id']) ) : $product_id ;	
        	$nbes_settings      = get_post_meta( $product_id, '_nbes_settings', true );
        	if( $nbes_settings ){
        		$_nbes_settings = maybe_unserialize( $nbes_settings );
	        	if( isset( $_nbes_settings['td_preview'] ) && $_nbes_settings['td_preview'] == 1 && $_nbes_settings['td_folder_name'] != '' && $_nbes_settings['td_custom_mesh_name'] != '' ){
		            return true;
		        }
		    }
	        return false;
        }
    }
}
