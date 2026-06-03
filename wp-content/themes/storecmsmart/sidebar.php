<?php
// Sidebar placeholder — theme does not use sidebars on most pages.
// WooCommerce may call get_sidebar() internally; this prevents 404 warnings.
if ( is_active_sidebar( 'shop-sidebar' ) ) : ?>
  <aside class="site-sidebar">
    <?php dynamic_sidebar( 'shop-sidebar' ); ?>
  </aside>
<?php endif; ?>
