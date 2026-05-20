<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php do_action( 'pc_footer', 'facebook-chat' ); ?>

<div class="fb-customerchat"
    attribution=setup_tool
    page_id="<?php echo( $nbc_fb_page_id ); ?>"
    theme_color="<?php echo( $nbc_fb_page_theme_color ); ?>"
    logged_in_greeting="<?php echo( $nbc_fb_page_login_greeting ); ?>"
    logged_out_greeting="<?php echo( $nbc_fb_page_logout_greeting ); ?>">
</div>