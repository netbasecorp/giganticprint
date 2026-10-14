<?php
/**
 * Template Name: Shop Page
 * Template Post Type: page
 *
 * Assign in Pages → Edit → Page Attributes → Template: "Shop Page".
 * Also auto-applied when the page slug is "shop".
 */

get_header();

/* ── Customizer ─────────────────────────────────── */
$phone      = giga_option( 'giga_footer_phone', '' );
$phone_href = giga_option( 'giga_footer_phone_href', preg_replace( '/[^0-9+]/', '', $phone ) );
$b2b_url    = giga_option( 'giga_b2b_url', home_url( '/contact/' ) );

$shop_title = giga_option(
	'giga_shop_hero_title',
	'Shop All <span class="accent">Printing</span> Products'
);
$shop_sub = giga_option(
	'giga_shop_hero_sub',
	'Find the right print product for your business, event, vehicle, or storefront. Fast turnaround, real prices.'
);

/* ── WooCommerce categories (chips + sidebar) ───── */
$uncategorized_id = 0;
$_uc = get_term_by( 'slug', 'uncategorized', 'product_cat' );
if ( $_uc ) $uncategorized_id = $_uc->term_id;

$wc_cats = get_terms( [
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'exclude'    => $uncategorized_id ? [ $uncategorized_id ] : [],
] );
if ( is_wp_error( $wc_cats ) ) $wc_cats = [];

/* ── Featured / popular products strip (6) ──────── */
$_featured = wc_get_products( [
	'limit'      => 6,
	'status'     => 'publish',
	'visibility' => 'visible',
	'featured'   => true,
] );
if ( empty( $_featured ) ) {
	$_featured = wc_get_products( [
		'limit'      => 6,
		'status'     => 'publish',
		'visibility' => 'visible',
		'orderby'    => 'popularity',
		'order'      => 'DESC',
	] );
}

/* ── All products (main grid) ───────────────────── */
$_all = wc_get_products( [
	'limit'      => -1,
	'status'     => 'publish',
	'visibility' => 'visible',
	'orderby'    => 'menu_order',
	'order'      => 'ASC',
] );

/* ── Helpers ─────────────────────────────────────── */
if ( ! function_exists( 'giga_shop_cat_slugs' ) ) {
	function giga_shop_cat_slugs( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) return '';
		return implode( ' ', wp_list_pluck( $terms, 'slug' ) );
	}
}
if ( ! function_exists( 'giga_shop_has_designer' ) ) {
	function giga_shop_has_designer( $product_id ) {
		$v = get_post_meta( $product_id, '_nbdesigner_enable', true );
		return ! empty( $v ) && $v !== 'no';
	}
}
if ( ! function_exists( 'giga_shop_specs' ) ) {
	function giga_shop_specs( $product_id, $max = 3 ) {
		$tags = get_the_terms( $product_id, 'product_tag' );
		if ( ! $tags || is_wp_error( $tags ) ) return [];
		return array_slice( wp_list_pluck( $tags, 'name' ), 0, $max );
	}
}
if ( ! function_exists( 'giga_shop_price_from' ) ) {
	function giga_shop_price_from( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			$min = $product->get_variation_price( 'min', true );
			return $min ? wc_price( $min ) : $product->get_price_html();
		}
		return $product->get_price_html();
	}
}
?>

<main id="main-content" class="shop-page">

  <!-- BREADCRUMB -->
  <div class="shop-bc-wrap">
    <div class="container"><?php giga_breadcrumb(); ?></div>
  </div>

  <!-- ═══════════════════════════════════════
       SHOP HEADER
  ═══════════════════════════════════════════ -->
  <section class="shop-header">
    <div class="container">
      <div class="shop-header-inner">

        <div class="shop-header-top">
          <div>
            <div class="shop-header-label">
              <?php echo esc_html( giga_option( 'giga_city', 'Fort Pierce, FL' ) ); ?>
            </div>
            <h1 class="shop-header-title">
              <?php echo wp_kses_post( $shop_title ); ?>
            </h1>
            <p class="shop-header-sub">
              <?php echo esc_html( $shop_sub ); ?>
            </p>
          </div>
          <?php if ( $phone_href ) : ?>
          <div class="shop-header-ctas">
            <a href="tel:<?php echo esc_attr( $phone_href ); ?>" class="sh-btn-outline">
              <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015 12.84 19.79 19.79 0 011.95 4.18 2 2 0 013.93 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
              <?php esc_html_e( 'Request a Quote', 'storecmsmart' ); ?>
            </a>
          </div>
          <?php endif; ?>
        </div>

        <!-- Search -->
        <div class="shop-search-wrap">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input class="shop-search-input" type="search" id="shopSearch"
                 placeholder="<?php esc_attr_e( 'Search products…', 'storecmsmart' ); ?>"
                 autocomplete="off">
        </div>

        <!-- Category chips -->
        <div class="cat-chips">
          <button class="cat-chip is-active" data-cat="all">
            <?php esc_html_e( 'All Products', 'storecmsmart' ); ?>
          </button>
          <?php foreach ( $wc_cats as $cat ) : ?>
          <button class="cat-chip" data-cat="<?php echo esc_attr( $cat->slug ); ?>">
            <?php echo esc_html( $cat->name ); ?>
          </button>
          <?php endforeach; ?>
        </div>

      </div><!-- .shop-header-inner -->
    </div>
  </section>

  <!-- ═══════════════════════════════════════
       POPULAR STRIP
  ═══════════════════════════════════════════ -->
  <?php if ( ! empty( $_featured ) ) : ?>
  <section class="popular-strip">
    <div class="container">
      <div class="popular-eyebrow"><?php esc_html_e( 'Most Popular', 'storecmsmart' ); ?></div>
      <div class="popular-heading"><?php esc_html_e( 'Top Best-Selling Products', 'storecmsmart' ); ?></div>
      <div class="popular-grid">
        <?php foreach ( $_featured as $fp ) :
          $fp_id   = $fp->get_id();
          $fp_img  = get_the_post_thumbnail_url( $fp_id, 'thumbnail' );
          $fp_cats = get_the_terms( $fp_id, 'product_cat' );
          $fp_cat  = ( $fp_cats && ! is_wp_error( $fp_cats ) ) ? $fp_cats[0]->slug : '';
        ?>
        <a class="pop-card" href="<?php echo esc_url( get_permalink( $fp_id ) ); ?>"
           data-cat="<?php echo esc_attr( $fp_cat ); ?>">
          <div class="pop-card-icon">
            <?php if ( $fp_img ) : ?>
              <img src="<?php echo esc_url( $fp_img ); ?>"
                   alt="<?php echo esc_attr( $fp->get_name() ); ?>"
                   width="52" height="52">
            <?php else : ?>
              <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
            <?php endif; ?>
          </div>
          <div class="pop-card-name"><?php echo esc_html( $fp->get_name() ); ?></div>
          <div class="pop-card-from"><?php echo wp_kses_post( giga_shop_price_from( $fp ) ); ?></div>
          <?php if ( $fp->is_featured() ) : ?>
            <span class="pop-card-tag hot"><?php esc_html_e( 'Best Seller', 'storecmsmart' ); ?></span>
          <?php elseif ( $fp->is_on_sale() ) : ?>
            <span class="pop-card-tag fast"><?php esc_html_e( 'On Sale', 'storecmsmart' ); ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════
       MAIN CATALOG
  ═══════════════════════════════════════════ -->
  <section id="catalog" class="shop-catalog">
    <div class="container">
      <div class="shop-layout">

        <!-- ── FILTER SIDEBAR ─────────────────── -->
        <aside class="filter-sidebar">

          <!-- Category filter -->
          <?php if ( ! empty( $wc_cats ) ) : ?>
          <div class="filter-box is-open">
            <div class="filter-box-head" onclick="gigaToggleFilter(this)">
              <span class="filter-box-head-title"><?php esc_html_e( 'Category', 'storecmsmart' ); ?></span>
              <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
            </div>
            <div class="filter-body">
              <ul class="filter-list">
                <?php foreach ( $wc_cats as $cat ) : ?>
                <li class="filter-item">
                  <input type="checkbox" id="fcat-<?php echo esc_attr( $cat->slug ); ?>"
                         name="cat" value="<?php echo esc_attr( $cat->slug ); ?>">
                  <label for="fcat-<?php echo esc_attr( $cat->slug ); ?>">
                    <span><?php echo esc_html( $cat->name ); ?></span>
                    <span class="filter-count"><?php echo (int) $cat->count; ?></span>
                  </label>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <?php endif; ?>

          <!-- Tags / feature filter -->
          <div class="filter-box is-open">
            <div class="filter-box-head" onclick="gigaToggleFilter(this)">
              <span class="filter-box-head-title"><?php esc_html_e( 'Features', 'storecmsmart' ); ?></span>
              <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
            </div>
            <div class="filter-body">
              <ul class="filter-list">
                <li class="filter-item">
                  <input type="checkbox" id="ftag-feat" name="tag" value="featured">
                  <label for="ftag-feat"><span><?php esc_html_e( 'Best Seller', 'storecmsmart' ); ?></span></label>
                </li>
                <li class="filter-item">
                  <input type="checkbox" id="ftag-sale" name="tag" value="onsale">
                  <label for="ftag-sale"><span><?php esc_html_e( 'On Sale', 'storecmsmart' ); ?></span></label>
                </li>
                <li class="filter-item">
                  <input type="checkbox" id="ftag-designer" name="tag" value="designer">
                  <label for="ftag-designer"><span><?php esc_html_e( 'Online Designer', 'storecmsmart' ); ?></span></label>
                </li>
              </ul>
            </div>
          </div>

          <!-- Clear all -->
          <button class="filter-clear-all" id="clearAll"><?php esc_html_e( 'Clear All Filters', 'storecmsmart' ); ?></button>

        </aside>

        <!-- ── PRODUCT GRID ───────────────────── -->
        <div>
          <div class="grid-header">
            <div class="grid-count">
              <strong id="productCount"><?php echo count( $_all ); ?></strong>
              <?php esc_html_e( ' products found', 'storecmsmart' ); ?>
            </div>
            <div class="grid-header-right">
              <div class="grid-sort">
                <label for="sortSelect"><?php esc_html_e( 'Sort:', 'storecmsmart' ); ?></label>
                <select id="sortSelect">
                  <option value="default"><?php esc_html_e( 'Default', 'storecmsmart' ); ?></option>
                  <option value="name-asc"><?php esc_html_e( 'Name A–Z', 'storecmsmart' ); ?></option>
                  <option value="name-desc"><?php esc_html_e( 'Name Z–A', 'storecmsmart' ); ?></option>
                  <option value="price-asc"><?php esc_html_e( 'Price: Low → High', 'storecmsmart' ); ?></option>
                  <option value="price-desc"><?php esc_html_e( 'Price: High → Low', 'storecmsmart' ); ?></option>
                </select>
              </div>
              <div class="grid-view-btns">
                <button class="grid-view-btn is-active" id="view3" title="3 columns" onclick="gigaSetView(3)">
                  <svg viewBox="0 0 20 20"><rect x="1" y="1" width="5" height="5" rx="1"/><rect x="8" y="1" width="5" height="5" rx="1"/><rect x="15" y="1" width="5" height="5" rx="1"/><rect x="1" y="8" width="5" height="5" rx="1"/><rect x="8" y="8" width="5" height="5" rx="1"/><rect x="15" y="8" width="5" height="5" rx="1"/></svg>
                </button>
                <button class="grid-view-btn" id="view4" title="4 columns" onclick="gigaSetView(4)">
                  <svg viewBox="0 0 20 20"><rect x="1" y="1" width="3" height="3" rx=".5"/><rect x="6" y="1" width="3" height="3" rx=".5"/><rect x="11" y="1" width="3" height="3" rx=".5"/><rect x="16" y="1" width="3" height="3" rx=".5"/><rect x="1" y="6" width="3" height="3" rx=".5"/><rect x="6" y="6" width="3" height="3" rx=".5"/><rect x="11" y="6" width="3" height="3" rx=".5"/><rect x="16" y="6" width="3" height="3" rx=".5"/></svg>
                </button>
              </div>
            </div>
          </div><!-- .grid-header -->

          <div class="product-grid" id="productGrid">
            <?php foreach ( $_all as $product ) :
              $pid        = $product->get_id();
              $purl       = get_permalink( $pid );
              $pname      = $product->get_name();
              $pshort     = wp_strip_all_tags( $product->get_short_description() );
              $pcat_slugs = giga_shop_cat_slugs( $pid );
              $pspecs     = giga_shop_specs( $pid );
              $pthumb     = get_the_post_thumbnail_url( $pid, 'woocommerce_thumbnail' );
              $has_design = giga_shop_has_designer( $pid );
              $is_featured = $product->is_featured();
              $is_on_sale  = $product->is_on_sale();
              $ptype       = $product->get_type();
              $pprice_raw  = (float) ( $ptype === 'variable'
                ? $product->get_variation_price( 'min' )
                : $product->get_price() );

              /* Virtual tags for JS filter */
              $vtags = [];
              if ( $is_featured )  $vtags[] = 'featured';
              if ( $is_on_sale )   $vtags[] = 'onsale';
              if ( $has_design )   $vtags[] = 'designer';
              $ptags_data = implode( ',', $vtags );

              /* Add to cart URL for simple products */
              $atc_url = ( $ptype === 'simple' && ! $has_design )
                ? esc_url( add_query_arg( 'add-to-cart', $pid, wc_get_cart_url() ) )
                : esc_url( $purl );

              /* Start Design URL */
              $design_url = esc_url( home_url( '/create-your-own/' ) . '?product_id=' . $pid );
            ?>
            <div class="pcard"
                 data-cat="<?php echo esc_attr( $pcat_slugs ); ?>"
                 data-tags="<?php echo esc_attr( $ptags_data ); ?>"
                 data-price="<?php echo esc_attr( number_format( $pprice_raw, 2, '.', '' ) ); ?>"
                 data-name="<?php echo esc_attr( strtolower( $pname ) ); ?>">

              <!-- Thumbnail -->
              <div class="pcard-thumb">
                <?php if ( $pthumb ) : ?>
                  <img src="<?php echo esc_url( $pthumb ); ?>"
                       alt="<?php echo esc_attr( $pname ); ?>" loading="lazy">
                <?php else : ?>
                  <div class="pcard-thumb-placeholder">
                    <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Badges -->
              <div class="pcard-badges">
                <?php if ( $is_featured ) : ?>
                  <span class="pcard-badge best"><?php esc_html_e( 'Best Seller', 'storecmsmart' ); ?></span>
                <?php endif; ?>
                <?php if ( $is_on_sale ) : ?>
                  <span class="pcard-badge fast"><?php esc_html_e( 'Sale', 'storecmsmart' ); ?></span>
                <?php endif; ?>
                <?php if ( $has_design ) : ?>
                  <span class="pcard-badge custom"><?php esc_html_e( 'Designer', 'storecmsmart' ); ?></span>
                <?php endif; ?>
              </div>

              <!-- Body -->
              <div class="pcard-body">
                <div class="pcard-name"><?php echo esc_html( $pname ); ?></div>
                <?php if ( $pshort ) : ?>
                  <div class="pcard-desc"><?php echo esc_html( wp_trim_words( $pshort, 14 ) ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $pspecs ) ) : ?>
                  <div class="pcard-specs">
                    <?php foreach ( $pspecs as $spec ) : ?>
                      <span class="pcard-spec"><?php echo esc_html( $spec ); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <div class="pcard-price">
                  <?php if ( $ptype === 'variable' ) : ?>
                    <span class="pcard-from"><?php esc_html_e( 'From', 'storecmsmart' ); ?></span>
                  <?php endif; ?>
                  <span class="pcard-val"><?php echo wp_kses_post( giga_shop_price_from( $product ) ); ?></span>
                </div>
              </div>

              <!-- Actions -->
              <div class="pcard-actions">
                <?php if ( $has_design ) : ?>
                  <a href="<?php echo $design_url; ?>" class="pcard-btn pcard-btn--primary">
                    <?php esc_html_e( 'Start Design', 'storecmsmart' ); ?>
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                  </a>
                <?php elseif ( $ptype === 'variable' ) : ?>
                  <a href="<?php echo esc_url( $purl ); ?>" class="pcard-btn pcard-btn--primary">
                    <?php esc_html_e( 'Select Options', 'storecmsmart' ); ?>
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                  </a>
                <?php else : ?>
                  <a href="<?php echo $atc_url; ?>" class="pcard-btn pcard-btn--primary"
                     data-product-id="<?php echo (int) $pid; ?>">
                    <?php esc_html_e( 'Add to Cart', 'storecmsmart' ); ?>
                    <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg>
                  </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( $purl ); ?>" class="pcard-btn pcard-btn--view">
                  <?php esc_html_e( 'View Details', 'storecmsmart' ); ?>
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </a>
              </div>

            </div><!-- .pcard -->
            <?php endforeach; ?>
          </div><!-- #productGrid -->

          <div id="noResults" style="display:none;text-align:center;padding:64px 0;color:var(--nbd-mb-text-soft)">
            <svg viewBox="0 0 24 24" style="width:48px;height:48px;stroke:var(--nbd-mb-border-strong);stroke-width:1.5;fill:none;margin:0 auto 16px;display:block"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <strong style="display:block;font-size:18px;font-weight:800;color:var(--nbd-mb-ink);margin-bottom:8px"><?php esc_html_e( 'No products found', 'storecmsmart' ); ?></strong>
            <span><?php esc_html_e( 'Try adjusting your search or filters.', 'storecmsmart' ); ?></span>
          </div>

        </div><!-- grid column -->
      </div><!-- .shop-layout -->
    </div>
  </section>

  <!-- ═══════════════════════════════════════
       B2B BAND
  ═══════════════════════════════════════════ -->
  <div class="shop-b2b-section">
    <div class="container">
      <div class="shop-b2b-band">
        <div class="shop-b2b-text">
          <div class="shop-b2b-label"><?php esc_html_e( 'B2B / Wholesale', 'storecmsmart' ); ?></div>
          <h3><?php echo wp_kses_post( giga_option( 'giga_shop_b2b_title', 'Print resellers &amp; agencies — you belong here.' ) ); ?></h3>
          <p><?php echo esc_html( giga_option( 'giga_shop_b2b_desc', 'Volume pricing, NET-30 terms, dedicated rep, and blind shipping.' ) ); ?></p>
        </div>
        <div class="shop-b2b-actions">
          <a href="<?php echo esc_url( $b2b_url ); ?>" class="btn-primary">
            <?php esc_html_e( 'Apply for B2B', 'storecmsmart' ); ?>
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <?php if ( $phone_href ) : ?>
          <a href="tel:<?php echo esc_attr( $phone_href ); ?>" class="btn-secondary">
            <?php esc_html_e( 'Call Us', 'storecmsmart' ); ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</main><!-- #main-content -->

<script>
(function () {
  var grid     = document.getElementById('productGrid');
  var cards    = Array.from(document.querySelectorAll('.pcard'));
  var noRes    = document.getElementById('noResults');
  var countEl  = document.getElementById('productCount');

  /* ── Core filter ──────────────────────────────── */
  function doFilter() {
    var chip    = document.querySelector('.cat-chip.is-active');
    var cat     = chip ? chip.dataset.cat : 'all';
    var q       = (document.getElementById('shopSearch').value || '').toLowerCase().trim();
    var catCbs  = Array.from(document.querySelectorAll('input[name="cat"]:checked'));
    var tagCbs  = Array.from(document.querySelectorAll('input[name="tag"]:checked'));

    var visible = 0;
    cards.forEach(function (card) {
      var cCat  = (card.dataset.cat   || '').toLowerCase();
      var cName = (card.dataset.name  || '').toLowerCase();
      var cTags = (card.dataset.tags  || '').toLowerCase();
      var cCatArr = cCat.split(' ');

      var chipOk   = cat === 'all' || cCatArr.indexOf(cat) > -1;
      var searchOk = !q || cName.indexOf(q) > -1;
      var catCbOk  = !catCbs.length  || catCbs.some(function (cb) { return cCatArr.indexOf(cb.value) > -1; });
      var tagCbOk  = !tagCbs.length  || tagCbs.some(function (cb) { return cTags.indexOf(cb.value) > -1; });

      var show = chipOk && searchOk && catCbOk && tagCbOk;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (countEl) countEl.textContent = visible;
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
  }

  /* ── Category chips ───────────────────────────── */
  document.querySelectorAll('.cat-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.cat-chip').forEach(function (c) { c.classList.remove('is-active'); });
      chip.classList.add('is-active');
      doFilter();
    });
  });

  /* Popular strip clicks → scroll to catalog with chip active */
  document.querySelectorAll('.pop-card').forEach(function (card) {
    card.addEventListener('click', function (e) {
      var cat = card.dataset.cat;
      if (!cat) return;
      e.preventDefault();
      var chip = document.querySelector('.cat-chip[data-cat="' + cat + '"]');
      if (chip) {
        document.querySelectorAll('.cat-chip').forEach(function (c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        doFilter();
      }
      var catalog = document.getElementById('catalog');
      if (catalog) catalog.scrollIntoView({ behavior: 'smooth' });
    });
  });

  /* ── Search ──────────────────────────────────── */
  document.getElementById('shopSearch').addEventListener('input', doFilter);

  /* ── Checkbox filters ────────────────────────── */
  document.querySelectorAll('input[name="cat"],input[name="tag"]').forEach(function (cb) {
    cb.addEventListener('change', doFilter);
  });

  /* ── Clear all ───────────────────────────────── */
  var clearBtn = document.getElementById('clearAll');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      document.querySelectorAll('input[name="cat"],input[name="tag"]').forEach(function (cb) { cb.checked = false; });
      document.getElementById('shopSearch').value = '';
      document.querySelectorAll('.cat-chip').forEach(function (c) { c.classList.remove('is-active'); });
      var allChip = document.querySelector('.cat-chip[data-cat="all"]');
      if (allChip) allChip.classList.add('is-active');
      doFilter();
    });
  }

  /* ── Filter accordion ────────────────────────── */
  window.gigaToggleFilter = function (head) {
    head.closest('.filter-box').classList.toggle('is-open');
  };

  /* ── View toggle ─────────────────────────────── */
  window.gigaSetView = function (n) {
    grid.classList.toggle('view-4', n === 4);
    document.getElementById('view3').classList.toggle('is-active', n !== 4);
    document.getElementById('view4').classList.toggle('is-active', n === 4);
  };

  /* ── Sort ────────────────────────────────────── */
  document.getElementById('sortSelect').addEventListener('change', function () {
    var val = this.value;
    var sorted = cards.slice().sort(function (a, b) {
      if (val === 'name-asc')   return a.dataset.name.localeCompare(b.dataset.name);
      if (val === 'name-desc')  return b.dataset.name.localeCompare(a.dataset.name);
      if (val === 'price-asc')  return parseFloat(a.dataset.price || 0) - parseFloat(b.dataset.price || 0);
      if (val === 'price-desc') return parseFloat(b.dataset.price || 0) - parseFloat(a.dataset.price || 0);
      return 0;
    });
    sorted.forEach(function (card) { grid.appendChild(card); });
  });
})();
</script>

<?php get_footer(); ?>
