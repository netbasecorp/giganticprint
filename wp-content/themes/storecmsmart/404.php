<?php get_header(); ?>

<main class="site-main">
  <div class="page-hero">
    <div class="container">
      <h1 class="page-title"><?php esc_html_e( 'Page not found', 'storecmsmart' ); ?></h1>
    </div>
  </div>

  <div class="container page-content-wrap" style="text-align:center; padding: 64px 24px;">
    <p style="font-size:80px; margin:0; line-height:1;">404</p>
    <p style="font-size:18px; color:var(--nbd-mb-text-soft); margin: 16px 0 32px;">
      <?php esc_html_e( "The page you're looking for doesn't exist or has been moved.", 'storecmsmart' ); ?>
    </p>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary"><?php esc_html_e( 'Go to homepage', 'storecmsmart' ); ?></a>
    <?php if ( function_exists( 'wc_get_page_id' ) ) : ?>
      <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="btn-secondary" style="margin-left:12px;"><?php esc_html_e( 'Browse products', 'storecmsmart' ); ?></a>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
