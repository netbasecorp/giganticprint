<?php
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
    $full_info_link = add_query_arg(
        array( 'mode' => 'all' ), 
        admin_url( 'admin.php?page=nbdesigner_system_info' )
    );
    $system_info = nbd_get_system_info();
?>

<?php do_action( 'pc_head', 'system-info' ); ?>

<div class="system-info">
    <h1><?php esc_html_e('System Info', 'web-to-print-online-designer'); ?></h1>
    <div>
        <table class="widefat striped">
            <tbody>
                <?php foreach( $system_info as $info ): ?>
                <tr>
                    <th><?php echo( $info['label'] ); ?></th>
                    <td class="<?php echo( $info['class'] ); ?>">
                        <?php if( $info['class'] == 'good' ): ?>
                            <span class="dashicons dashicons-yes"></span>
                        <?php elseif( $info['class'] == 'bad'): ?>
                            <span class="dashicons dashicons-no-alt"></span>
                        <?php endif ?>
                        <?php echo( $info['value'] ); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <a href="<?php echo esc_url( $full_info_link ); ?>"><?php esc_html_e('View full PHP info', 'web-to-print-online-designer'); ?></a>
    </div>
</div>