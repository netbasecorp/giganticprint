<?php get_header(); ?>

<main id="main-content" class="site-main">
  <?php do_action( 'giga_before_content' ); ?>

  <?php while ( have_posts() ) : the_post();
    // Detect WooCommerce pages (cart, checkout, my account) by shortcode or WC function
    $woo_shortcodes = [ '[woocommerce_cart]', '[woocommerce_checkout]', '[woocommerce_my_account]', '[woocommerce_order_tracking]' ];
    $post_content   = get_the_content();
    $is_woo_page    = false;
    foreach ( $woo_shortcodes as $sc ) {
      if ( str_contains( $post_content, $sc ) ) { $is_woo_page = true; break; }
    }
    if ( function_exists('is_cart') && ( is_cart() || is_checkout() || is_account_page() ) ) {
      $is_woo_page = true;
    }
  ?>
    <div class="site-bc-wrap">
      <div class="container"><?php giga_breadcrumb(); ?></div>
    </div>

    <?php if ( ! $is_woo_page ) : ?>
    <div class="page-hero">
      <div class="container">
        <h1 class="page-title"><?php the_title(); ?></h1>
      </div>
    </div>
    <?php endif; ?>

    <?php if ( $is_woo_page ) : ?>
    <div class="woo-shortcode-wrap">
      <?php the_content(); ?>
    </div>
    <?php else : ?>
    <div class="container page-content-wrap">
      <div class="entry-content">
        <?php the_content(); ?>
      </div>
    </div>
    <?php endif; ?>

  <?php endwhile; ?>
</main>

<?php get_footer(); ?>
