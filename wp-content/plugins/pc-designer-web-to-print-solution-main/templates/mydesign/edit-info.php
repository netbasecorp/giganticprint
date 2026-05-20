<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$_user_id   = absint( get_query_var('artist-info') );
$user_id    = get_current_user_id();
if( $_user_id != $user_id ): esc_html_e( 'Opps! You do not have permission access this page!', 'web-to-print-online-designer' ); else:
$user_info      = nbd_get_artist_info( $user_id );
$user_meta      = get_userdata( $user_id );
$user_roles     = $user_meta->roles;
$is_designer    = 0;
foreach( $user_roles as $role ){
    $desiger_roles = array('administrator', 'shop_manager', 'designer');
    if( in_array( $role, $desiger_roles ) ){
        $is_designer = 1;
    }
}
wp_enqueue_media();
do_action( 'nbd_artist_info_before_form', $user_id, $user_info );
?>
<form method="post" id="nbd-artist-form"  action="" class="nbd-artist-form">
    <?php wp_nonce_field( 'nbd_artist_settings_nonce' ); ?>
    <input type="hidden" value="<?php echo( $user_id ); ?>" name="user_id"/>
    <div class="nbd-section">
        <label for="nbd_artist_twitter"><?php esc_html_e( 'I want to become designer', 'web-to-print-online-designer' ); ?></label>
        <input type="hidden" name="nbd_become_designer" value="0" />
        <input type="checkbox" id="nbd_become_designer" name="nbd_become_designer" value="1" <?php checked( $is_designer, 1 ); ?> />
    </div>
    <?php if( $is_designer ): ?>
    <div class="nbd-banner" style="padding-bottom: <?php echo ( $user_info['banner_height'] / $user_info['banner_width'] * 100 ) . '%'; ?>;">
        <?php $banner = $user_info['nbd_artist_banner']; ?>
        <div class="image-wrap<?php echo( $banner ? '' : ' nbd-hide' ); ?>">
            <?php $banner_url = $banner ? wp_get_attachment_url( $banner ) : ''; ?>
            <input type="hidden" class="nbd-file-field" value="<?php echo( $banner ); ?>" name="nbd_artist_banner">
            <img class="nbd-banner-img" src="<?php echo esc_url( $banner_url ); ?>">
            <a class="close nbd-remove-banner-image">&times;</a>
        </div>
        <div class="button-area<?php echo( $banner ? ' nbd-hide' : '' ); ?>">
            <p><a href="#" class="nbd-banner-drag button button-primary"><?php esc_html_e( 'Upload banner', 'web-to-print-online-designer' ); ?></a></p>
            <p class="description"><?php esc_html_e( '(Upload a banner for your store. )', 'web-to-print-online-designer' ); ?></p>
        </div>
    </div>
    <?php endif; ?>
    <div class="nbd-section nbd_artist_name-wrap">
        <label for="nbd_artist_name"><?php esc_html_e( 'Artist Name', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_name" name="nbd_artist_name"
            value="<?php echo esc_attr( $user_info['nbd_artist_name'] ); ?>"/>
    </div>  
    <div class="nbd-section nbd_artist_description">
        <label for="nbd_artist_description" ><?php esc_html_e( 'About the artist', 'web-to-print-online-designer' ); ?></label>
        <textarea rows="5" cols="30" id="nbd_artist_description" name="nbd_artist_description" ><?php echo esc_attr( $user_info['nbd_artist_description'] ); ?></textarea>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_address"><?php esc_html_e( 'Address', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_address" name="nbd_artist_address"
            value="<?php echo esc_attr( $user_info['nbd_artist_address'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_phone"><?php esc_html_e( 'Phone Number', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_phone" name="nbd_artist_phone"
            value="<?php echo esc_attr( $user_info['nbd_artist_phone'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_facebook"><?php esc_html_e( 'Facebook', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_facebook" name="nbd_artist_facebook"
            value="<?php echo esc_attr( $user_info['nbd_artist_facebook'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_twitter"><?php esc_html_e( 'Twitter', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_twitter" name="nbd_artist_twitter"
            value="<?php echo esc_attr( $user_info['nbd_artist_twitter'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_linkedin"><?php esc_html_e( 'LinkedIn', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_linkedin" name="nbd_artist_linkedin"
            value="<?php echo esc_attr( $user_info['nbd_artist_linkedin'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_youtube"><?php esc_html_e( 'Youtube', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_youtube" name="nbd_artist_youtube"
            value="<?php echo esc_attr( $user_info['nbd_artist_youtube'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_instagram"><?php esc_html_e( 'Instagram', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_instagram" name="nbd_artist_instagram"
            value="<?php echo esc_attr( $user_info['nbd_artist_instagram'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_artist_flickr"><?php esc_html_e( 'Flickr', 'web-to-print-online-designer' ); ?></label>
        <input class="regular-text" type="text" id="nbd_artist_flickr" name="nbd_artist_flickr"
            value="<?php echo esc_attr( $user_info['nbd_artist_flickr'] ); ?>"/>
    </div>
    <div class="nbd-section">
        <label for="nbd_payment"><?php esc_html_e( 'Payment infomation', 'web-to-print-online-designer' ); ?></label>
        <textarea name="nbd_payment" rows="5" cols="30" placeholder="<?php esc_attr_e( 'Paypal: email&#x0a;Bank account', 'web-to-print-online-designer' ); ?>"><?php echo esc_attr( $user_info['nbd_payment'] ); ?></textarea>
    </div>
    <div class="nbd-section">
        <input type="submit" value="<?php esc_html_e('Update informations', 'web-to-print-online-designer'); ?>" />
        <img class="nbd-loading loaded" src="<?php echo NBDESIGNER_PLUGIN_URL.'assets/images/loading.gif' ?>" />
    </div>
</form>
<?php  do_action( 'nbd_artist_info_after_form', $user_id, $user_info ); ?>

<?php do_action( 'pc_footer', 'mydesign-edit-info' ); ?>
<?php endif;