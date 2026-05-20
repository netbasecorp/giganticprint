<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly  ?>
<html lang="<?php echo( $lang_code ); ?>">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="Content-type" content="text/html; charset=utf-8">
        <title><?php esc_html_e('Online Designer', 'web-to-print-online-designer'); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1, minimum-scale=0.5, maximum-scale=1.0"/>
        <meta content="Online Designer - HTML5 Designer - Online Print Solution" name="description" />
        <meta content="Online Designer" name="keywords" />
        <meta content="Netbaseteam" name="author"> 
        <?php do_action( 'pc_head', 'template-mobile' ); ?>
    </head>
    <body>
        <p><img src="<?php echo NBDESIGNER_PLUGIN_URL . 'assets/images/mobile.png'; ?>" /></p>
        <p class="announce"><?php esc_html_e('Sorry, our design tool is not currently supported on mobile devices.', 'web-to-print-online-designer'); ?></p>
        <?php if( $ui_mode == 1 ): ?>
        <p class="recommend"><a href="javascript:void(0)" onclick="window.parent.hideDesignFrame();"><?php esc_html_e('Back to product', 'web-to-print-online-designer'); ?></a></p>
        <?php else: ?>
        <p class="recommend"><a class="button" href="<?php echo get_permalink( wc_get_page_id( 'shop' ) );?>"><?php esc_html_e('Return to shop', 'web-to-print-online-designer'); ?></a></p>
        <?php endif; ?>
    </body>
</html>