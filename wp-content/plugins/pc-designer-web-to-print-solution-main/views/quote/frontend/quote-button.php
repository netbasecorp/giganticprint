<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="nbdq-add-a-quote">
    <button data-id="<?php echo( $product->get_id() ); ?>" class="nbdq-add-a-quote-button button alt" id="nbdq-quote-btn"><span><?php esc_html_e( 'Add a quote', 'web-to-print-online-designer' ); ?></span></button>
</div>
<?php if ( nbdesigner_get_option( 'nbdesigner_quote_hide_add_to_cart', 'no' ) == 'yes'): ?>

<?php do_action( 'pc_head', 'quote-button' ); ?>

<?php endif;