<?php
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
?>

<?php do_action( 'pc_head', 'system-info-all' ); ?>

<div class="system-info">
    <h1><?php esc_html_e('PHP Info', 'web-to-print-online-designer'); ?></h1>
<?php
    $full_info_link = admin_url( 'admin.php?page=nbdesigner_system_info' );

    ob_start();
    phpinfo();
    $pinfo = ob_get_contents();
    ob_end_clean();

    $pinfo = preg_replace( '%^.*<div class="center">(.*)</div>.*$%ms', '$1', $pinfo );
    $pinfo = preg_replace( '%(^.*)<a name=\".*\">(.*)</a>(.*$)%m', '$1$2$3', $pinfo );
    $pinfo = str_replace( '<table>', '<table class="widefat striped phpinfo">', $pinfo );
    $pinfo = str_replace( '<td class="e">', '<th class="e">', $pinfo );
    echo( $pinfo );
?>
</div>