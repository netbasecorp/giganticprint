<?php
/**
 * B2B Portal — App shell template (no header/footer, true full-width).
 * Works with ANY WordPress theme.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* Prevent caching of sensitive portal pages */
nocache_headers();

/* Remove WP admin bar — prevents the 32px margin-top on <html> */
add_filter( 'show_admin_bar', '__return_false' );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?> · B2B Portal</title>
<?php wp_head(); ?>
<style>
  /* App-shell reset — full viewport, no theme leaking in */
  *, *::before, *::after { box-sizing: border-box; }
  /* Override WP admin-bar margin injected on logged-in pages */
  html, body {
    margin: 0 !important; padding: 0 !important;
    height: 100%;
    background: #f3f4f6;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 14px;
    -webkit-font-smoothing: antialiased;
  }
  /* Kill WP admin bar and anything the theme injected */
  #wpadminbar { display: none !important; }
  body > *:not(#pcs-portal-app):not(.pcs-portal-topbar):not(script):not(style) { display: none !important; }
  #pcs-portal-app { display: block !important; }
  .pcs-portal-topbar { display: flex !important; }

  /* Back to site link — subtle top bar */
  .pcs-portal-topbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 36px;
    background: #111827;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    z-index: 9999;
    font-size: 12px;
  }
  .pcs-portal-topbar-left { display: flex; align-items: center; gap: 12px; }
  .pcs-portal-topbar a { color: rgba(255,255,255,.7); text-decoration: none; }
  .pcs-portal-topbar a:hover { color: #fff; }
  .pcs-portal-topbar-brand { color: #fff; font-weight: 700; font-size: 13px; }
  .pcs-portal-topbar-sep { color: rgba(255,255,255,.2); }
  .pcs-portal-topbar-right { display: flex; align-items: center; gap: 16px; }

  /* Push portal below topbar */
  #pcs-portal-app {
    padding-top: 36px;
    min-height: 100vh;
  }
  #pcs-portal-app .pcs-portal-wrap {
    max-width: 100vw;
    min-height: calc(100vh - 36px);
  }
</style>
</head>
<body <?php body_class( 'pcs-portal-body' ); ?>>

<!-- Thin topbar: back to site + site name -->
<div class="pcs-portal-topbar">
  <div class="pcs-portal-topbar-left">
    <span class="pcs-portal-topbar-brand"><?php bloginfo( 'name' ); ?></span>
    <span class="pcs-portal-topbar-sep">·</span>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">← Back to site</a>
    <span class="pcs-portal-topbar-sep">·</span>
    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Shop</a>
    <span class="pcs-portal-topbar-sep">·</span>
    <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">My Account</a>
  </div>
  <div class="pcs-portal-topbar-right">
    <?php
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $user = wp_get_current_user();
    ?>
    <?php if ( $cart_count > 0 ) : ?>
      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
        🛒 <span style="background:#ef4444;color:#fff;padding:1px 6px;border-radius:9999px;font-size:10px;font-weight:700;"><?php echo (int) $cart_count; ?></span>
      </a>
    <?php endif; ?>
    <span style="color:rgba(255,255,255,.7);"><?php echo esc_html( $user->display_name ); ?></span>
    <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#f87171;">Log out</a>
  </div>
</div>

<!-- Portal app shell -->
<div id="pcs-portal-app">
  <?php while ( have_posts() ) : the_post(); ?>
    <?php the_content(); ?>
  <?php endwhile; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
