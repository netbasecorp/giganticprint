<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>
<?php
$changelogs = $nbd_news->sections->changelog;
$faqs = $nbd_news->sections->faq;
?>

<?php do_action( 'pc_head', 'support' ); ?>

<div class="nbd-support-wrap">
    <div class="nbd-logo">
        <img src="<?php echo NBDESIGNER_PLUGIN_URL; ?>/assets/images/logo.svg" alt="Storefront" />
    </div> 
    <div class="nbd-intro">
        <p><?php esc_html_e('Hello! You might be interested in the following NBDesigner NEWS and our printing solutions.', 'web-to-print-online-designer'); ?></p>
    </div>
    <div class="nbd-enhance">
        <div class="nbd-enhance__column nbd-change-log">
            <h3><?php esc_html_e( 'Change log', 'web-to-print-online-designer' ); ?></h3>
            <div class="nbd-change-log__wrap">
                <?php 
                    if( is_array( $changelogs ) ):
                    foreach ( $changelogs as $log ):
                    $date = new DateTime($log->created);    
                ?>
                <h4><?php echo( $log->version_number.' &#8211; '.$date->format('F j, Y') ); ?></h4>
                <div><?php echo( $log->descriptions ); ?></div>
                <?php 
                    endforeach;
                    endif; 
                ?>
            </div>
        </div>
        <div class="nbd-enhance__column nbd-faq">
            <h3><?php esc_html_e( 'FAQs', 'web-to-print-online-designer' ); ?></h3>
            <div class="nbd-faq__wrap">
                <?php 
                    if( is_array( $faqs ) ):
                    foreach ( $faqs as $faq ):   
                ?>
                <h4><?php echo( $faq->title ); ?></h4>
                <div><?php echo( $faq->description ); ?></div>
                <?php 
                    endforeach;
                    endif; 
                ?>
            </div>            
        </div>    
    </div>
    <div class="nbd-project">
        <p>
            <?php printf( esc_html__( 'A %s project', 'web-to-print-online-designer' ), '<a href="http://netbaseteam.com/" target="_blank"><img src="' . NBDESIGNER_PLUGIN_URL . '/assets/images/netbaseteam.png" alt="Netbase Team" /></a>' ); ?>
        </p>
    </div>
</div>
