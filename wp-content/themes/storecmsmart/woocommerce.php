<?php
/**
 * woocommerce.php — wrapper for all WooCommerce pages (shop, category, single product, cart, checkout, account).
 * WooCommerce checks for this file first; if found it uses it instead of index.php.
 */
get_header(); ?>

<main id="main-content" class="site-main woo-page">
  <?php do_action( 'giga_before_content' ); ?>

  <?php
  // Page title block (WooCommerce suppresses its own via filter in functions.php)
  $page_title = '';
  if ( is_shop() ) {
    $page_title = get_the_title( wc_get_page_id( 'shop' ) );
  } elseif ( is_product_category() ) {
    $page_title = single_cat_title( '', false );
  } elseif ( is_product_tag() ) {
    $page_title = single_tag_title( '', false );
  } elseif ( is_product() ) {
    $page_title = get_the_title();
  } elseif ( is_cart() ) {
    $page_title = __( 'Cart', 'storecmsmart' );
  } elseif ( is_checkout() ) {
    $page_title = __( 'Checkout', 'storecmsmart' );
  } elseif ( is_account_page() ) {
    $page_title = __( 'My Account', 'storecmsmart' );
  }
  // Fetch category thumbnail once for both layout class and img output
  $cat_thumb_url = '';
  if ( is_product_category() ) {
    $term     = get_queried_object();
    $thumb_id = $term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
    if ( $thumb_id ) {
      $cat_thumb_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' );
    }
  }

  if ( $page_title ) : ?>
    <div class="page-hero<?php echo $cat_thumb_url ? ' page-hero--with-thumb' : ''; ?>">
      <div class="container">
        <?php woocommerce_breadcrumb(); ?>
        <div class="page-hero-inner">
          <div class="page-hero-text">
            <h1 class="page-title"><?php echo esc_html( $page_title ); ?></h1>
            <?php if ( is_product_category() ) :
              $cat_desc = category_description();
              if ( $cat_desc ) : ?>
                <p class="page-subtitle"><?php echo wp_kses_post( $cat_desc ); ?></p>
              <?php endif;
            endif; ?>
          </div>
          <?php if ( $cat_thumb_url ) : ?>
            <div class="page-hero-thumb">
              <img src="<?php echo esc_url( $cat_thumb_url ); ?>" alt="<?php echo esc_attr( $page_title ); ?>">
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="container woo-page-container">
    <?php woocommerce_content(); ?>
  </div>
</main>

<?php get_footer(); ?>
