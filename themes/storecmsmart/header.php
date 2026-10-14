<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'storecmsmart' ); ?></a>

<?php $img_base = get_stylesheet_directory_uri() . '/assets/images'; ?>

<!-- ============ UTILITY BAR ============ -->
<div class="util-bar">
  <div class="left">
    <span><span class="dot"></span><?php echo wp_kses_post( giga_option( 'giga_util_bar_text', wp_kses( __( 'Ordering open <strong>24 / 7</strong> · Ships nationwide', 'storecmsmart' ), [ 'strong' => [] ] ) ) ); ?></span>
  </div>
  <div class="right">
    <?php if ( has_nav_menu( 'utility' ) ) : ?>
      <?php wp_nav_menu( [
        'theme_location' => 'utility',
        'container'      => false,
        'menu_class'     => 'util-nav',
        'depth'          => 1,
        'fallback_cb'    => false,
      ] ); ?>
    <?php else :
      $_ul1_url = giga_option( 'giga_util_link1_url', '' );
      $_ul1_lbl = giga_option( 'giga_util_link1_label', __( 'FAQs', 'storecmsmart' ) );
      $_ul2_url = giga_option( 'giga_util_link2_url', '' );
      $_ul2_lbl = giga_option( 'giga_util_link2_label', __( 'How-to videos', 'storecmsmart' ) );
      $_ul3_url = giga_option( 'giga_util_link3_url', '' );
      $_ul3_lbl = giga_option( 'giga_util_link3_label', __( 'Track order', 'storecmsmart' ) );
    ?>
      <?php if ( $_ul1_url ) : ?><a href="<?php echo esc_url( $_ul1_url ); ?>" class="hide-mobile"><?php echo esc_html( $_ul1_lbl ); ?></a><?php endif; ?>
      <?php if ( $_ul2_url ) : ?><a href="<?php echo esc_url( $_ul2_url ); ?>" class="hide-mobile"><?php echo esc_html( $_ul2_lbl ); ?></a><?php endif; ?>
      <?php if ( $_ul3_url ) : ?><a href="<?php echo esc_url( $_ul3_url ); ?>"><?php echo esc_html( $_ul3_lbl ); ?></a><?php endif; ?>
      <?php $hdr_phone = giga_option( 'giga_footer_phone', '' ); if ( $hdr_phone ) : ?>
      <a href="tel:<?php echo esc_attr( giga_option( 'giga_footer_phone_href', preg_replace( '/[^0-9]/', '', $hdr_phone ) ) ); ?>" class="hide-mobile">📞 <?php echo esc_html( $hdr_phone ); ?></a>
      <?php endif; ?>
    <?php endif; ?>
    <?php
    $_soc_ig = giga_option( 'giga_social_instagram', '' );
    $_soc_fb = giga_option( 'giga_social_facebook',  '' );
    $_soc_yt = giga_option( 'giga_social_youtube',   '' );
    if ( $_soc_ig || $_soc_fb || $_soc_yt ) : ?>
    <span class="util-social">
      <?php if ( $_soc_ig ) : ?>
      <a href="<?php echo esc_url( $_soc_ig ); ?>" class="util-social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Instagram', 'storecmsmart' ); ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor" stroke="none"/></svg>
      </a>
      <?php endif; ?>
      <?php if ( $_soc_fb ) : ?>
      <a href="<?php echo esc_url( $_soc_fb ); ?>" class="util-social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Facebook', 'storecmsmart' ); ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
      </a>
      <?php endif; ?>
      <?php if ( $_soc_yt ) : ?>
      <a href="<?php echo esc_url( $_soc_yt ); ?>" class="util-social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'YouTube', 'storecmsmart' ); ?>">
        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 001.46 6.42 29 29 0 001 12a29 29 0 00.46 5.58 2.78 2.78 0 001.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.47a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon style="fill:var(--nbd-mb-ink)" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
      </a>
      <?php endif; ?>
    </span>
    <?php endif; ?>
  </div>
</div>

<!-- ============ TOP BAR ============ -->
<header class="topbar" role="banner">

  <!-- Logo — replace via Appearance > Customize > Site Identity -->
  <?php if ( has_custom_logo() ) : ?>
    <!-- the_custom_logo() already outputs its own <a class="custom-logo-link">,
         so we use a <div> wrapper to avoid invalid nested anchors. -->
    <div class="brand">
      <?php the_custom_logo(); ?>
    </div>
  <?php else : ?>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="<?php bloginfo( 'name' ); ?> home">
      <img src="<?php echo esc_url( $img_base . '/logo.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="brand-logo">
      <span class="brand-name"><?php bloginfo( 'name' ); ?> — <?php bloginfo( 'description' ); ?></span>
    </a>
  <?php endif; ?>

  <!-- Primary navigation
       Megamenu plugins (Max Mega Menu, WP Mega Menu, UberMenu) hook
       automatically into wp_nav_menu() via the 'primary' location. -->
  <nav class="topbar-nav" aria-label="<?php esc_attr_e( 'Primary', 'storecmsmart' ); ?>">
    <?php if ( has_nav_menu( 'primary' ) ) : ?>
      <?php wp_nav_menu( [
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => 'topbar-nav-list',
        'depth'          => 3,
        'fallback_cb'    => false,
        'walker'         => class_exists( 'Mega_Menu_Walker' ) ? new Mega_Menu_Walker() : null,
        'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
      ] ); ?>
    <?php else : ?>
      <!-- Fallback nav until a menu is assigned in WP Admin -->
      <div class="nav-dropdown" data-dropdown aria-expanded="false">
        <button type="button" class="nav-link" aria-haspopup="true"><?php esc_html_e( 'Categories', 'storecmsmart' ); ?></button>
        <div class="nav-dropdown-menu" role="menu">
          <?php
          $cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'number' => 9 ] );
          $cat_icons = [
            'banners'     => 'banners.png',
            'rigid-signs' => 'rigid.png',
            'adhesive-vinyl' => 'adhesive.png',
            'apparel'     => 'apparel.png',
            'magnetic'    => 'magnetic.png',
            'photo-prints'=> 'photo.png',
            'promo-print' => 'promo.png',
            'image-zone'  => 'imagezone.png',
            'accessories' => 'misc.png',
          ];
          if ( ! is_wp_error( $cats ) && $cats ) :
            foreach ( $cats as $cat ) :
              $icon_file = $cat_icons[ $cat->slug ] ?? 'misc.png';
          ?>
          <a class="nav-dropdown-item" href="<?php echo esc_url( get_term_link( $cat ) ); ?>" role="menuitem">
            <img src="<?php echo esc_url( $img_base . '/icons/' . $icon_file ); ?>" alt="">
            <span class="label"><?php echo esc_html( $cat->name ); ?><small><?php echo esc_html( wp_strip_all_tags( $cat->description ) ); ?></small></span>
          </a>
          <?php endforeach; endif; ?>
          <div class="nav-dropdown-divider"></div>
          <a class="nav-dropdown-footer" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" role="menuitem">
            <?php esc_html_e( 'Browse all categories', 'storecmsmart' ); ?>
            <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>
      <a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', get_permalink( wc_get_page_id( 'shop' ) ) ) ); ?>" class="nav-link"><?php esc_html_e( 'Popular', 'storecmsmart' ); ?></a>
      <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'how-it-works' ) ) ?: '#how' ); ?>" class="nav-link"><?php esc_html_e( 'How it works', 'storecmsmart' ); ?></a>
      <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'b2b-wholesale' ) ) ?: '#b2b' ); ?>" class="nav-link"><?php esc_html_e( 'B2B / Wholesale', 'storecmsmart' ); ?></a>
    <?php endif; ?>
  </nav>

  <!-- Header actions -->
  <div class="topbar-actions">
    <!-- Cart icon with live count -->
    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="nbd-icon-btn" aria-label="<?php esc_attr_e( 'Shopping cart', 'storecmsmart' ); ?>">
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg>
      <?php $count = giga_cart_count(); if ( $count > 0 ) : ?>
        <span class="badge"><?php echo esc_html( $count ); ?></span>
      <?php endif; ?>
    </a>

    <!-- Login / Account -->
    <?php if ( is_user_logged_in() ) : ?>
      <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>" class="login-btn"><?php esc_html_e( 'My Account', 'storecmsmart' ); ?></a>
    <?php else : ?>
      <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>" class="login-btn"><?php esc_html_e( 'Log in', 'storecmsmart' ); ?></a>
    <?php endif; ?>

    <!-- Start an Order CTA -->
    <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="start-btn">
      <?php esc_html_e( 'Start an Order', 'storecmsmart' ); ?>
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none" width="14" height="14"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
    </a>

    <!-- Hamburger — mobile only -->
    <button class="menu-toggle" id="menu-toggle"
            aria-label="<?php esc_attr_e( 'Open navigation', 'storecmsmart' ); ?>"
            aria-expanded="false"
            aria-controls="mobile-nav-panel">
      <span class="menu-toggle-bar"></span>
      <span class="menu-toggle-bar"></span>
      <span class="menu-toggle-bar"></span>
    </button>
  </div>

</header>

<!-- ============ CATEGORY NAV BAR ============
     Shown on all pages except the designer (which has its own category nav).
     Each item links to the WooCommerce shop filtered by that category.
-->
<?php
$_hide_catnav = ( isset( $_GET['product_id'] ) || ( function_exists( 'is_page' ) && is_page( 'create-your-own' ) ) );
if ( ! $_hide_catnav && function_exists( 'get_terms' ) ) :
    $_cnav_exclude = [];
    $_cnav_default = (int) get_option( 'default_product_cat' );
    if ( $_cnav_default ) $_cnav_exclude[] = $_cnav_default;
    $_cnav_cats = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
        'number'     => 20,
        'exclude'    => $_cnav_exclude,
    ] );
    if ( ! is_wp_error( $_cnav_cats ) && ! empty( $_cnav_cats ) ) :
?>
<nav class="cat-nav-bar" aria-label="<?php esc_attr_e( 'Browse by category', 'storecmsmart' ); ?>">
  <div class="cat-nav-inner">
    <?php
    // Find newest category (highest term_id) to show "NEW" badge
    $_cnav_max_id = max( array_map( function( $c ) { return $c->term_id; }, $_cnav_cats ) );
    foreach ( $_cnav_cats as $_cnav_cat ) :
        $_cnav_tid      = $_cnav_cat->term_id;
        $_cnav_thumb_id = get_term_meta( $_cnav_tid, 'thumbnail_id', true );
        $_cnav_img      = '';
        if ( $_cnav_thumb_id ) {
            $_cnav_src = wp_get_attachment_image_src( $_cnav_thumb_id, [ 56, 56 ] );
            $_cnav_img = $_cnav_src ? $_cnav_src[0] : '';
        }
        $_cnav_active = is_tax( 'product_cat' ) && (
            get_queried_object_id() === $_cnav_tid ||
            in_array( $_cnav_tid, get_ancestors( get_queried_object_id(), 'product_cat' ), true )
        );
        $_cnav_is_new = ( $_cnav_tid === $_cnav_max_id );
    ?>
    <a href="<?php echo esc_url( get_term_link( $_cnav_cat ) ); ?>"
       class="cat-nav-item<?php echo $_cnav_active ? ' cat-nav-active' : ''; ?><?php echo $_cnav_is_new ? ' cat-nav-is-new' : ''; ?>">
      <?php if ( $_cnav_is_new ) : ?>
        <span class="cat-nav-new-badge">NEW</span>
      <?php endif; ?>
      <?php if ( $_cnav_img ) : ?>
        <img src="<?php echo esc_url( $_cnav_img ); ?>" alt="" class="cat-nav-icon" width="28" height="28" loading="lazy">
      <?php else : ?>
        <span class="cat-nav-icon cat-nav-icon-placeholder" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        </span>
      <?php endif; ?>
      <span class="cat-nav-label"><?php echo esc_html( $_cnav_cat->name ); ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; endif; ?>

<!-- ============ MOBILE NAV DRAWER ============ -->
<div class="mobile-overlay" id="mobile-overlay" aria-hidden="true"></div>
<nav id="mobile-nav-panel" class="mobile-nav-panel"
     aria-label="<?php esc_attr_e( 'Mobile navigation', 'storecmsmart' ); ?>"
     aria-hidden="true">

  <div class="mobile-nav-head">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mobile-nav-brand" tabindex="-1">
      <?php if ( has_custom_logo() ) : the_custom_logo(); else : bloginfo( 'name' ); endif; ?>
    </a>
    <button class="mobile-nav-close" id="mobile-nav-close"
            aria-label="<?php esc_attr_e( 'Close navigation', 'storecmsmart' ); ?>">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" fill="none">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <?php if ( has_nav_menu( 'primary' ) ) : ?>
    <?php wp_nav_menu( [
      'theme_location' => 'primary',
      'container'      => false,
      'menu_class'     => 'mobile-nav-list',
      'depth'          => 2,
      'fallback_cb'    => false,
    ] ); ?>
  <?php else :
    $_mob_shop = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
    $_mob_cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'number' => 8 ] );
  ?>
    <ul class="mobile-nav-list">
      <?php if ( ! is_wp_error( $_mob_cats ) ) foreach ( $_mob_cats as $_mc ) : ?>
        <li><a href="<?php echo esc_url( get_term_link( $_mc ) ); ?>"><?php echo esc_html( $_mc->name ); ?></a></li>
      <?php endforeach; ?>
      <li><a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', $_mob_shop ) ); ?>"><?php esc_html_e( 'Popular', 'storecmsmart' ); ?></a></li>
      <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'how-it-works' ) ) ?: '#' ); ?>"><?php esc_html_e( 'How it works', 'storecmsmart' ); ?></a></li>
      <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'b2b-wholesale' ) ) ?: '#' ); ?>"><?php esc_html_e( 'B2B / Wholesale', 'storecmsmart' ); ?></a></li>
    </ul>
  <?php endif; ?>

  <div class="mobile-nav-foot">
    <?php $mob_phone = giga_option( 'giga_footer_phone', '' ); if ( $mob_phone ) : ?>
      <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $mob_phone ) ); ?>" class="mobile-nav-util"><?php echo esc_html( $mob_phone ); ?></a>
    <?php endif; ?>
    <?php if ( function_exists( 'wc_get_page_id' ) ) : ?>
      <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>" class="mobile-nav-util">
        <?php is_user_logged_in() ? esc_html_e( 'My Account', 'storecmsmart' ) : esc_html_e( 'Log in', 'storecmsmart' ); ?>
      </a>
    <?php endif; ?>
    <a href="<?php echo esc_url( function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' ) ); ?>"
       class="start-btn" style="display:block;text-align:center;margin-top:4px;">
      <?php esc_html_e( 'Start an Order', 'storecmsmart' ); ?>
    </a>
  </div>

</nav>
