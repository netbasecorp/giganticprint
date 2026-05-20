<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly  ?>

<?php do_action( 'pc_footer', 'license-notice' ); ?>

<span class="nbd-notice-license-footer" onclick="showNBDLicensePopup(event)" title="<?php esc_attr_e('Get NBDesigner Premium', 'web-to-print-online-designer'); ?>">
    <svg class="nbd-pro" fill="#F3B600" xmlns="http://www.w3.org/2000/svg" viewBox="-505 380 12 10"><path d="M-503 388h8v1h-8zM-494 382.2c-.4 0-.8.3-.8.8 0 .1 0 .2.1.3l-2.3.7-1.5-2.2c.3-.2.5-.5.5-.8 0-.6-.4-1-1-1s-1 .4-1 1c0 .3.2.6.5.8l-1.5 2.2-2.3-.8c0-.1.1-.2.1-.3 0-.4-.3-.8-.8-.8s-.8.4-.8.8.3.8.8.8h.2l.8 3.3h8l.8-3.3h.2c.4 0 .8-.3.8-.8 0-.4-.4-.7-.8-.7z"></path></svg>
</span>
<div class="nbd-license-popup" id="nbd-license-popup" onclick="hideNBDLicensePopup(event)">
    <div class="nbd-license-popup-inner">
        <a href="https://solution.printcart.com/app/wordpress-web-2-print-product-designer-plugin" target="_blank">
            <img src="<?php echo NBDESIGNER_ASSETS_URL . 'images/lite_version_popup.png'; ?>" alt="Lite version"/>
        </a>
        <span onclick="hideNBDLicensePopup(event, true)" class="nbd-license-popup-close">&times;</span>
    </div>
</div>

<?php do_action( 'pc_head', 'license-notice' ); ?>