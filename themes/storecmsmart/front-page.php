<?php
defined( 'ABSPATH' ) || exit;
get_header();

$img_base  = get_stylesheet_directory_uri() . '/assets/images';
$shop_url  = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
/* B2B URL: auto-use portal if plugin active, otherwise fall back to theme option */
if ( class_exists( 'PCS_B2B_Portal' ) ) {
    $portal_id = (int) get_option( 'pcs_portal_page_id' );
    $b2b_url   = $portal_id ? get_permalink( $portal_id ) : giga_option( 'giga_b2b_url', '#b2b' );
} else {
    $b2b_url   = giga_option( 'giga_b2b_url', '#b2b' );
}
?>

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container">
    <div class="hero-inner">
      <div>
        <div class="hero-eyebrow">
          <span class="dot"></span>
          <?php echo wp_kses_post( giga_option( 'giga_hero_eyebrow', 'Now accepting 24-hour orders' ) ); ?>
        </div>
        <h1 class="hero-title">
          <?php echo wp_kses_post( giga_option( 'giga_hero_title', 'Large-format signs,<br><span class="accent">printed in 24 hours.</span>' ) ); ?>
        </h1>
        <p class="hero-desc">
          <?php echo wp_kses_post( giga_option( 'giga_hero_desc', 'Upload your artwork or design online. Real-time pricing, real-time preview. Ships nationwide.' ) ); ?>
        </p>
        <div class="hero-cta-row">
          <a href="<?php echo esc_url( giga_option( 'giga_hero_cta_url', $shop_url ) ); ?>" class="btn-primary">
            <?php echo esc_html( giga_option( 'giga_hero_cta_text', 'Browse products' ) ); ?>
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <?php if ( class_exists( 'PCS_B2B_Portal' ) ) : ?>
          <a href="<?php echo esc_url( $b2b_url ); ?>" class="btn-secondary">
            <?php esc_html_e( 'B2B Portal', 'storecmsmart' ); ?>
          </a>
          <?php else : ?>
          <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn-secondary">
            <?php esc_html_e( 'Wholesale Inquiry', 'storecmsmart' ); ?>
          </a>
          <?php endif; ?>
        </div>

        <div class="hero-meta">
          <div class="hero-meta-item">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span><?php echo wp_kses( __( '<strong>24-hour</strong> turnaround', 'storecmsmart' ), [ 'strong' => [] ] ); ?></span>
          </div>
          <div class="hero-meta-item">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-6 9 6v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M9 22V12h6v10"/></svg>
            <span><?php echo wp_kses( __( '<strong>No login</strong> to design', 'storecmsmart' ), [ 'strong' => [] ] ); ?></span>
          </div>
          <div class="hero-meta-item">
            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span><?php
              $_city = giga_option( 'giga_city', '' );
              if ( $_city ) {
                printf( wp_kses( __( 'Ships from <strong>%s</strong>', 'storecmsmart' ), [ 'strong' => [] ] ), esc_html( $_city ) );
              } else {
                echo wp_kses( __( 'Ships <strong>nationwide</strong>', 'storecmsmart' ), [ 'strong' => [] ] );
              }
            ?></span>
          </div>
        </div>
      </div>

      <?php $hero_img = giga_option( 'giga_hero_image', '' ); ?>
      <div class="hero-visual<?php echo $hero_img ? ' hero-visual--photo' : ''; ?>" aria-hidden="true">
        <?php if ( $hero_img ) : ?>
          <img src="<?php echo esc_url( $hero_img ); ?>" alt="" class="hero-visual-img">
        <?php else :
          // Split site name into 2 lines — admin can override in Customize > Hero
          $_parts   = explode( ' ', trim( get_bloginfo( 'name' ) ), 2 );
          $_vl1     = giga_option( 'giga_hero_visual_l1' ) ?: strtoupper( $_parts[0] ?? '' );
          $_vl2     = giga_option( 'giga_hero_visual_l2' ) ?: strtoupper( $_parts[1] ?? '' );
          $_vtag    = giga_option( 'giga_hero_visual_tagline', __( '24-hour production', 'storecmsmart' ) );
        ?>
          <span class="hero-visual-dim top">55"</span>
          <span class="hero-visual-dim side">68"</span>
          <div>
            <div class="hero-banner-text">
              <span class="l1"><?php echo esc_html( $_vl1 ); ?></span>
              <?php if ( $_vl2 ) : ?><span class="l2"><?php echo esc_html( $_vl2 ); ?></span><?php endif; ?>
            </div>
            <div class="hero-visual-tagline"><?php echo esc_html( $_vtag ); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============ CATEGORIES ============ -->
<section class="section" id="categories">
  <div class="container">
    <div class="section-head-row">
      <div class="section-title-block">
        <div class="eyebrow"><?php esc_html_e( 'Browse by category', 'storecmsmart' ); ?></div>
        <h2 class="section-title"><?php esc_html_e( 'What are you printing today?', 'storecmsmart' ); ?></h2>
        <div class="section-sub">
          <?php
          $_city = giga_option( 'giga_city', '' );
          if ( $_city ) {
            printf( esc_html__( 'Our top categories — all produced in our %s shop.', 'storecmsmart' ), esc_html( $_city ) );
          } else {
            esc_html_e( 'Our top categories — all produced locally.', 'storecmsmart' );
          }
          ?>
          <a href="<?php echo esc_url( $shop_url ); ?>" style="color:var(--nbd-mb-primary);font-weight:700;"><?php esc_html_e( 'See full catalog →', 'storecmsmart' ); ?></a>
        </div>
      </div>
    </div>

    <?php
    // Map WooCommerce category slugs to icons + display data
    $cat_map = [
      'banners'        => [ 'icon' => 'banners.png',   'title' => __( 'Banners',        'storecmsmart' ), 'sub' => __( 'Vinyl Scrim · HDPE · Mesh · Stands',                  'storecmsmart' ), 'data' => 'banner' ],
      'rigid-signs'    => [ 'icon' => 'rigid.png',     'title' => __( 'Rigid signs',    'storecmsmart' ), 'sub' => __( 'Coroplast · PVC · Aluminum · J-Bond · Acrylic · Foam', 'storecmsmart' ), 'data' => 'rigid' ],
      'adhesive-vinyl' => [ 'icon' => 'adhesive.png',  'title' => __( 'Adhesive vinyl', 'storecmsmart' ), 'sub' => __( 'Decals · window graphics · wraps',                     'storecmsmart' ), 'data' => 'adhesive' ],
      'apparel'        => [ 'icon' => 'apparel.png',   'title' => __( 'Apparel',        'storecmsmart' ), 'sub' => __( 'DTF · screen print · embroidery',                      'storecmsmart' ), 'data' => 'apparel', 'badge' => __( 'New', 'storecmsmart' ) ],
      'magnetic'       => [ 'icon' => 'magnetic.png',  'title' => __( 'Magnetic',       'storecmsmart' ), 'sub' => __( 'Vehicle magnets · mobile ads',                         'storecmsmart' ), 'data' => 'magnet' ],
      'photo-prints'   => [ 'icon' => 'photo.png',     'title' => __( 'Photo prints',   'storecmsmart' ), 'sub' => __( 'Canvas · acrylic · poster · gallery',                  'storecmsmart' ), 'data' => 'photo' ],
      'promo-print'    => [ 'icon' => 'promo.png',     'title' => __( 'Promo & print',  'storecmsmart' ), 'sub' => __( 'Business cards · flyers · brochures',                  'storecmsmart' ), 'data' => 'promo' ],
      'image-zone'     => [ 'icon' => 'imagezone.png', 'title' => __( 'Image Zone',     'storecmsmart' ), 'sub' => __( 'Wall murals · backdrops · large-format',               'storecmsmart' ), 'data' => 'imagezone' ],
    ];

    // Try to get actual WC categories, fall back to static map
    $wc_cats = function_exists( 'get_terms' ) ? get_terms( [
      'taxonomy'   => 'product_cat',
      'hide_empty' => false,
      'number'     => 8,
      'parent'     => 0,
      'exclude'    => [ get_option( 'default_product_cat' ) ],
    ] ) : [];

    $use_wc = ! is_wp_error( $wc_cats ) && ! empty( $wc_cats );
    ?>

    <div class="cat-grid">
      <?php if ( $use_wc ) :
        foreach ( $wc_cats as $cat ) :
          $slug     = $cat->slug;
          $meta     = $cat_map[ $slug ] ?? [ 'icon' => 'misc.png', 'title' => $cat->name, 'sub' => wp_strip_all_tags( $cat->description ), 'data' => $slug ];
          $cat_url  = get_term_link( $cat );
          $thumb_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
          // Use short description: prefer the fixed sub from $cat_map, trim WC description to 80 chars
          $sub_text = ! empty( $meta['sub'] )
            ? $meta['sub']
            : wp_trim_words( wp_strip_all_tags( $cat->description ), 12, '…' );
      ?>
      <a href="<?php echo esc_url( $cat_url ); ?>"
         class="cat-card<?php echo $thumb_id ? ' has-photo' : ''; ?>"
         data-cat="<?php echo esc_attr( $meta['data'] ); ?>">
        <?php if ( ! empty( $meta['badge'] ) ) : ?>
          <span class="cat-card-badge"><?php echo esc_html( $meta['badge'] ); ?></span>
        <?php endif; ?>
        <div class="cat-card-visual">
          <div class="cat-card-illo" aria-hidden="true">
            <?php if ( $thumb_id ) :
              echo wp_get_attachment_image( $thumb_id, 'medium', false, [ 'alt' => '' ] );
            else : ?>
              <img src="<?php echo esc_url( $img_base . '/icons/' . $meta['icon'] ); ?>" alt="">
            <?php endif; ?>
          </div>
        </div>
        <div class="cat-card-content">
          <h3 class="cat-card-title">
            <?php echo esc_html( $meta['title'] ?: $cat->name ); ?>
            <span class="cat-card-arrow" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </span>
          </h3>
          <p class="cat-card-sub"><?php echo esc_html( $sub_text ); ?></p>
        </div>
      </a>
      <?php endforeach; ?>

      <?php else : // Static fallback cards ?>
      <?php foreach ( $cat_map as $slug => $meta ) : ?>
      <a href="<?php echo esc_url( $shop_url ); ?>" class="cat-card" data-cat="<?php echo esc_attr( $meta['data'] ); ?>">
        <?php if ( ! empty( $meta['badge'] ) ) : ?>
          <span class="cat-card-badge"><?php echo esc_html( $meta['badge'] ); ?></span>
        <?php endif; ?>
        <div class="cat-card-visual">
          <div class="cat-card-illo" aria-hidden="true">
            <img src="<?php echo esc_url( $img_base . '/icons/' . $meta['icon'] ); ?>" alt="">
          </div>
        </div>
        <div class="cat-card-content">
          <h3 class="cat-card-title">
            <?php echo esc_html( $meta['title'] ); ?>
            <span class="cat-card-arrow" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </span>
          </h3>
          <p class="cat-card-sub"><?php echo esc_html( $meta['sub'] ); ?></p>
        </div>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURED PRODUCTS (WooCommerce) ============ -->
<section class="section section--soft" id="popular">
  <div class="container">
    <div class="section-head-row">
      <div class="section-title-block">
        <div class="eyebrow"><?php esc_html_e( 'Top sellers', 'storecmsmart' ); ?></div>
        <h2 class="section-title"><?php esc_html_e( 'Featured this week.', 'storecmsmart' ); ?></h2>
        <div class="section-sub"><?php esc_html_e( 'A few of the products our customers come back for, across every category. Hover any card to see the specs.', 'storecmsmart' ); ?></div>
      </div>
      <a href="<?php echo esc_url( $shop_url ); ?>" class="section-link">
        <?php esc_html_e( 'View all', 'storecmsmart' ); ?>
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
      </a>
    </div>

    <?php
    // Query featured / best-seller products — 6 items, 3-column grid
    $featured_query = new WP_Query( [
      'post_type'      => 'product',
      'posts_per_page' => 6,
      'post_status'    => 'publish',
      'tax_query'      => [
        [
          'taxonomy' => 'product_visibility',
          'field'    => 'name',
          'terms'    => 'featured',
        ],
      ],
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
    ] );

    // Fallback: latest products if no featured
    if ( ! $featured_query->have_posts() ) {
      $featured_query = new WP_Query( [
        'post_type'      => 'product',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
      ] );
    }
    ?>

    <div class="product-grid">
      <?php if ( $featured_query->have_posts() ) :
        while ( $featured_query->have_posts() ) :
          $featured_query->the_post();
          $product     = wc_get_product( get_the_ID() );
          $normal_url  = get_the_post_thumbnail_url( get_the_ID(), 'woocommerce_single' );
          $rollover_url = giga_get_rollover_image_url( get_the_ID() );
          $product_url = get_permalink();
          $product_name = get_the_title();
          if ( ! $normal_url ) continue;
      ?>
      <a href="<?php echo esc_url( $product_url ); ?>" class="product-card" aria-label="<?php echo esc_attr( $product_name ); ?>">
        <img class="pc-img normal"
             src="<?php echo esc_url( $normal_url ); ?>"
             alt=""
             loading="lazy">
        <?php if ( $rollover_url ) : ?>
          <img class="pc-img rollover"
               src="<?php echo esc_url( $rollover_url ); ?>"
               alt=""
               loading="lazy">
        <?php else : ?>
          <!-- No gallery image — duplicate normal for graceful fallback -->
          <img class="pc-img rollover"
               src="<?php echo esc_url( $normal_url ); ?>"
               alt=""
               loading="lazy">
        <?php endif; ?>
        <span class="pc-sr"><?php echo esc_html( $product_name ); ?></span>
      </a>
      <?php endwhile;
        wp_reset_postdata();
      else : ?>
        <!-- Static demo fallback when no WooCommerce products exist yet -->
        <?php
        $demo_products = [
          [ 'img' => 'coroplast.png',   'roll' => 'coroplast_rollover.png',   'name' => __( 'Coroplast — Vibrant Yard Signs',       'storecmsmart' ) ],
          [ 'img' => 'banner.png',      'roll' => 'banner_rollover.png',      'name' => __( 'Premium Vinyl Banner',                 'storecmsmart' ) ],
          [ 'img' => 'aluminum.png',    'roll' => 'aluminum_rollover.png',    'name' => __( 'Aluminum — Heavy Duty Signage',        'storecmsmart' ) ],
          [ 'img' => 'dtf.png',         'roll' => 'dtf_rollover.png',         'name' => __( 'DTF Apparel — Direct to Film',         'storecmsmart' ) ],
          [ 'img' => 'canvas.png',      'roll' => 'canvas_rollover.png',      'name' => __( 'Art Canvas — Stretch & Frame',         'storecmsmart' ) ],
          [ 'img' => 'magnet.png',      'roll' => 'magnet_rollover.png',      'name' => __( 'Magnetic — Premium Vehicle Magnet',    'storecmsmart' ) ],
        ];
        foreach ( $demo_products as $p ) : ?>
        <a href="<?php echo esc_url( $shop_url ); ?>" class="product-card" aria-label="<?php echo esc_attr( $p['name'] ); ?>">
          <img class="pc-img normal"   src="<?php echo esc_url( $img_base . '/products/' . $p['img'] ); ?>"  alt="" loading="lazy">
          <img class="pc-img rollover" src="<?php echo esc_url( $img_base . '/products/' . $p['roll'] ); ?>" alt="" loading="lazy">
          <span class="pc-sr"><?php echo esc_html( $p['name'] ); ?></span>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ ITEMS & ACCESSORIES ============ -->
<section class="section section--white" id="items">
  <div class="container">
    <div class="section-head-row">
      <div class="section-title-block">
        <div class="eyebrow"><?php esc_html_e( 'Order with your signs', 'storecmsmart' ); ?></div>
        <h2 class="section-title"><?php esc_html_e( 'Items & accessories.', 'storecmsmart' ); ?></h2>
        <div class="section-sub"><?php esc_html_e( 'Hardware, stakes, stand-offs and add-on items — everything you need to install and present your prints.', 'storecmsmart' ); ?></div>
      </div>
      <a href="<?php echo esc_url( $shop_url ); ?>" class="section-link">
        <?php esc_html_e( 'Browse all', 'storecmsmart' ); ?>
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
      </a>
    </div>

    <?php
    // Query products from "accessories" category (slug: accessories or misc)
    $acc_query = new WP_Query( [
      'post_type'      => 'product',
      'posts_per_page' => 3,
      'post_status'    => 'publish',
      'tax_query'      => [
        [
          'taxonomy' => 'product_cat',
          'field'    => 'slug',
          'terms'    => [ 'accessories', 'misc', 'hardware', 'items' ],
          'operator' => 'IN',
        ],
      ],
    ] );
    ?>

    <div class="items-grid">
      <?php if ( $acc_query->have_posts() ) :
        while ( $acc_query->have_posts() ) :
          $acc_query->the_post();
          $img_url     = get_the_post_thumbnail_url( get_the_ID(), 'woocommerce_single' );
          $product_url = get_permalink();
          $product_name = get_the_title();
      ?>
      <a href="<?php echo esc_url( $product_url ); ?>" class="item-card" aria-label="<?php echo esc_attr( $product_name ); ?>">
        <?php if ( $img_url ) : ?>
          <img src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy">
        <?php endif; ?>
        <span class="item-card-cta">
          <?php esc_html_e( 'Add to order', 'storecmsmart' ); ?>
          <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </span>
      </a>
      <?php endwhile;
        wp_reset_postdata();
      else : ?>
        <!-- Static demo fallback -->
        <?php
        $demo_items = [
          [ 'img' => 'stepstake.png', 'name' => __( 'Step Stakes — 10×30" 2-Wire Step Stake', 'storecmsmart' ) ],
          [ 'img' => 'standoffs.png', 'name' => __( 'Stand Offs — Complete install kit',       'storecmsmart' ) ],
          [ 'img' => 'hardcard.png',  'name' => __( 'Hard Cards — Aluminum business cards',    'storecmsmart' ) ],
        ];
        foreach ( $demo_items as $item ) : ?>
        <a href="<?php echo esc_url( $shop_url ); ?>" class="item-card" aria-label="<?php echo esc_attr( $item['name'] ); ?>">
          <img src="<?php echo esc_url( $img_base . '/products/' . $item['img'] ); ?>" alt="" loading="lazy">
          <span class="item-card-cta">
            <?php esc_html_e( 'Add to order', 'storecmsmart' ); ?>
            <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </span>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section" id="how">
  <div class="container">
    <div class="section-head-row">
      <div class="section-title-block">
        <div class="eyebrow"><?php esc_html_e( '4 steps', 'storecmsmart' ); ?></div>
        <h2 class="section-title"><?php esc_html_e( 'How it works', 'storecmsmart' ); ?></h2>
        <div class="section-sub"><?php esc_html_e( 'From "I need a sign" to "it\'s on the way" — no phone tag required.', 'storecmsmart' ); ?></div>
      </div>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-num">1</div>
        <div class="step-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>
        </div>
        <h3 class="step-title"><?php esc_html_e( 'Choose your product', 'storecmsmart' ); ?></h3>
        <p class="step-desc"><?php esc_html_e( 'Browse our nine categories — banners, rigid signs, vehicle wraps, apparel, magnets, photo prints and more.', 'storecmsmart' ); ?></p>
      </div>

      <div class="step-card">
        <div class="step-num">2</div>
        <div class="step-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M16 16l-4-4-4 4M12 12v9"/><path d="M20 16v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3"/></svg>
        </div>
        <h3 class="step-title"><?php esc_html_e( 'Upload or design', 'storecmsmart' ); ?></h3>
        <p class="step-desc"><?php esc_html_e( 'Drop your PDF, AI, PSD, PNG, or JPG file — or build a layout with our free online designer. No login required.', 'storecmsmart' ); ?></p>
      </div>

      <div class="step-card">
        <div class="step-num">3</div>
        <div class="step-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M21 12l-9 9-9-9V3h18z"/><circle cx="12" cy="9" r="2"/></svg>
        </div>
        <h3 class="step-title"><?php esc_html_e( 'Configure & preview', 'storecmsmart' ); ?></h3>
        <p class="step-desc"><?php esc_html_e( 'Pick size, material, grommets, welding, wind slits, stakes. Real-time price as you adjust.', 'storecmsmart' ); ?></p>
      </div>

      <div class="step-card">
        <div class="step-num">4</div>
        <div class="step-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>
        </div>
        <h3 class="step-title"><?php esc_html_e( 'Order any time, day or night', 'storecmsmart' ); ?></h3>
        <p class="step-desc"><?php
          $_city = giga_option( 'giga_city', '' );
          if ( $_city ) {
            printf( esc_html__( 'Standard orders enter production the next business day from our %s facility.', 'storecmsmart' ), esc_html( $_city ) );
          } else {
            esc_html_e( 'Standard orders enter production the next business day.', 'storecmsmart' );
          }
        ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ============ B2B STRIP ============ -->
<section class="section" id="b2b">
  <div class="container">
    <div class="b2b-strip">
      <div class="b2b-strip-content">
        <div class="b2b-eyebrow"><?php esc_html_e( 'B2B / Wholesale', 'storecmsmart' ); ?></div>
        <h3><?php esc_html_e( 'Print buyer? Get up to 20% off retail.', 'storecmsmart' ); ?></h3>
        <p><?php esc_html_e( 'Print shops, marketing agencies, event companies, and high-volume buyers — apply for a B2B account to unlock tiered pricing, private templates, Image Zone storage, and 1-click reorder.', 'storecmsmart' ); ?></p>
      </div>
      <div class="b2b-actions">
        <a href="<?php echo esc_url( $b2b_url ); ?>" class="btn-primary btn-on-dark">
          <?php esc_html_e( 'Apply for B2B', 'storecmsmart' ); ?>
          <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn-ghost-on-dark"><?php esc_html_e( 'Learn more', 'storecmsmart' ); ?></a>
      </div>
    </div>
  </div>
</section>

<?php get_footer(); ?>
