<?php
/**
 * Template Name: Blog Page
 * Template Post Type: page
 */

get_header();

/* ── Blog categories (exclude uncategorized) ─────────── */
$_uc_cat    = get_term_by( 'slug', 'uncategorized', 'category' );
$exclude_id = $_uc_cat ? [ $_uc_cat->term_id ] : [];

$blog_cats = get_categories( [
	'hide_empty' => true,
	'exclude'    => $exclude_id,
	'orderby'    => 'count',
	'order'      => 'DESC',
] );
if ( is_wp_error( $blog_cats ) ) $blog_cats = [];

/* ── All published posts ─────────────────────────────── */
$blog_posts = get_posts( [
	'numberposts' => -1,
	'post_status' => 'publish',
	'orderby'     => 'date',
	'order'       => 'DESC',
] );

/* ── Featured post: sticky or most recent ────────────── */
$featured_post = null;
$sticky_ids    = get_option( 'sticky_posts' );
if ( ! empty( $sticky_ids ) ) {
	$featured_post = get_post( $sticky_ids[0] );
}
if ( ! $featured_post && ! empty( $blog_posts ) ) {
	$featured_post = $blog_posts[0];
}

/* ── Build JS modal data (excerpt only — keeps page light) ── */
$modal_data = [];
foreach ( $blog_posts as $_bp ) {
	$_id      = $_bp->ID;
	$_cats    = get_the_category( $_id );
	$_wc      = str_word_count( wp_strip_all_tags( $_bp->post_content ) );
	/* Use manual excerpt if set, otherwise trim content to 120 words */
	$_excerpt = $_bp->post_excerpt
		? wp_trim_words( $_bp->post_excerpt, 120 )
		: wp_trim_words( wp_strip_all_tags( $_bp->post_content ), 120 );
	$modal_data[ $_id ] = [
		'title'    => $_bp->post_title,
		'excerpt'  => $_excerpt,
		'thumb'    => (string) get_the_post_thumbnail_url( $_id, 'large' ),
		'cat'      => $_cats ? $_cats[0]->name : 'Article',
		'date'     => get_the_date( 'M d, Y', $_id ),
		'url'      => get_permalink( $_id ),
		'readTime' => max( 1, round( $_wc / 200 ) ),
		'author'   => get_the_author_meta( 'display_name', $_bp->post_author ),
	];
}

/* ── Customizer options ──────────────────────────────── */
$blog_eyebrow = giga_option( 'giga_blog_eyebrow', 'Print tips & industry insights' );
$blog_title   = giga_option( 'giga_blog_hero_title', 'The <span class="accent">Gigantic</span> Blog' );
$blog_lead    = giga_option( 'giga_blog_hero_lead', 'Practical guides, design tips, and print industry know-how from the team — South Florida\'s most trusted large-format shop.' );
$blog_cta_ttl = giga_option( 'giga_blog_cta_title', 'Ready to print something <span class="accent-yellow">gigantic?</span>' );
$phone        = giga_option( 'giga_footer_phone', '' );
$phone_href   = giga_option( 'giga_footer_phone_href', $phone ? preg_replace( '/[^0-9+]/', '', $phone ) : '' );
$shop_url     = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
?>

<main id="main-content" class="blog-page">

  <!-- BREADCRUMB -->
  <div class="blog-bc-wrap">
    <div class="container"><?php giga_breadcrumb(); ?></div>
  </div>

  <!-- ═══════════════════════════════════════
       BLOG HERO
  ═══════════════════════════════════════════ -->
  <section class="blog-hero">
    <div class="container">
      <div class="blog-hero-eyebrow">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
        </svg>
        <?php echo esc_html( $blog_eyebrow ); ?>
      </div>
      <h1><?php echo wp_kses_post( $blog_title ); ?></h1>
      <p class="blog-hero-lead"><?php echo esc_html( $blog_lead ); ?></p>

      <!-- Category pills -->
      <div class="blog-cat-pills">
        <button class="cat-pill is-active" data-cat="all">
          <?php esc_html_e( 'All Posts', 'storecmsmart' ); ?>
        </button>
        <?php foreach ( $blog_cats as $cat ) : ?>
        <button class="cat-pill" data-cat="<?php echo esc_attr( $cat->slug ); ?>">
          <?php echo esc_html( $cat->name ); ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════
       FEATURED POST
  ═══════════════════════════════════════════ -->
  <?php if ( $featured_post ) :
    $fp_id      = $featured_post->ID;
    $fp_thumb   = get_the_post_thumbnail_url( $fp_id, 'large' );
    $fp_cats    = get_the_category( $fp_id );
    $fp_cat     = $fp_cats ? $fp_cats[0]->name : '';
    $fp_excerpt = $featured_post->post_excerpt
      ? wp_trim_words( $featured_post->post_excerpt, 30 )
      : wp_trim_words( wp_strip_all_tags( $featured_post->post_content ), 30 );
    $fp_date    = get_the_date( 'M d, Y', $fp_id );
    $fp_author  = get_the_author_meta( 'display_name', $featured_post->post_author );
  ?>
  <section class="blog-featured-section">
    <div class="container">
      <div class="blog-section-head-row" style="margin-bottom:var(--nbd-space-4)">
        <div>
          <div class="blog-eyebrow"><?php esc_html_e( 'Featured', 'storecmsmart' ); ?></div>
          <h2 class="blog-section-title"><?php esc_html_e( "Editor's Pick", 'storecmsmart' ); ?></h2>
        </div>
      </div>
      <div class="featured-post" role="article" onclick="openBlogModal(<?php echo (int) $fp_id; ?>)" tabindex="0"
           aria-label="<?php echo esc_attr( $featured_post->post_title ); ?>">
        <div class="featured-post-img">
          <?php if ( $fp_thumb ) : ?>
            <img src="<?php echo esc_url( $fp_thumb ); ?>"
                 alt="<?php echo esc_attr( $featured_post->post_title ); ?>" loading="lazy">
          <?php else : ?>
            <div class="fp-thumb-placeholder">
              <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
            </div>
          <?php endif; ?>
          <span class="fp-badge"><?php esc_html_e( 'Featured', 'storecmsmart' ); ?></span>
        </div>
        <div class="featured-post-body">
          <?php if ( $fp_cat ) : ?>
            <div class="fp-cat"><?php echo esc_html( $fp_cat ); ?></div>
          <?php endif; ?>
          <h3 class="fp-title"><?php echo esc_html( $featured_post->post_title ); ?></h3>
          <p class="fp-excerpt"><?php echo esc_html( $fp_excerpt ); ?></p>
          <div class="fp-meta">
            <span class="fp-author"><?php printf( esc_html__( 'By %s', 'storecmsmart' ), esc_html( $fp_author ) ); ?></span>
            <span class="fp-date"><?php echo esc_html( $fp_date ); ?></span>
          </div>
          <span class="fp-read-btn">
            <?php esc_html_e( 'Read article', 'storecmsmart' ); ?>
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none" width="14" height="14">
              <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </span>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════
       BLOG GRID
  ═══════════════════════════════════════════ -->
  <section class="blog-posts-section" id="blog-posts">
    <div class="container">
      <div class="blog-section-head-row">
        <div>
          <div class="blog-eyebrow"><?php esc_html_e( 'Latest Articles', 'storecmsmart' ); ?></div>
          <h2 class="blog-section-title"><?php esc_html_e( 'Print Tips & Guides', 'storecmsmart' ); ?></h2>
          <div class="blog-section-sub">
            <?php printf(
              esc_html( _n( '%d article', '%d articles', count( $blog_posts ), 'storecmsmart' ) ),
              count( $blog_posts )
            ); ?>
          </div>
        </div>
      </div>

      <?php if ( ! empty( $blog_posts ) ) : ?>
      <div class="blog-grid" id="blog-grid">
        <?php foreach ( $blog_posts as $bp ) :
          $bp_id       = $bp->ID;
          $bp_thumb    = get_the_post_thumbnail_url( $bp_id, 'medium_large' );
          $bp_cats     = get_the_category( $bp_id );
          $bp_cat      = $bp_cats ? $bp_cats[0] : null;
          $bp_cat_slug = $bp_cat ? $bp_cat->slug : 'uncategorized';
          $bp_cat_name = $bp_cat ? $bp_cat->name : '';
          $bp_excerpt  = $bp->post_excerpt
            ? wp_trim_words( $bp->post_excerpt, 18 )
            : wp_trim_words( wp_strip_all_tags( $bp->post_content ), 18 );
          $bp_date     = get_the_date( 'M d, Y', $bp_id );
          $word_count  = str_word_count( wp_strip_all_tags( $bp->post_content ) );
          $read_time   = max( 1, round( $word_count / 200 ) );
        ?>
        <article class="blog-card"
                 data-cat="<?php echo esc_attr( $bp_cat_slug ); ?>"
                 onclick="openBlogModal(<?php echo (int) $bp_id; ?>)"
                 tabindex="0"
                 role="button"
                 aria-label="<?php echo esc_attr( sprintf( __( 'Read: %s', 'storecmsmart' ), $bp->post_title ) ); ?>">
          <div class="blog-card-img">
            <?php if ( $bp_thumb ) : ?>
              <img src="<?php echo esc_url( $bp_thumb ); ?>"
                   alt="<?php echo esc_attr( $bp->post_title ); ?>" loading="lazy">
            <?php else : ?>
              <div class="blog-card-img-placeholder">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
              </div>
            <?php endif; ?>
            <?php if ( $bp_cat_name ) : ?>
              <span class="blog-card-cat"><?php echo esc_html( $bp_cat_name ); ?></span>
            <?php endif; ?>
          </div>
          <div class="blog-card-body">
            <h3 class="blog-card-title"><?php echo esc_html( $bp->post_title ); ?></h3>
            <?php if ( $bp_excerpt ) : ?>
              <p class="blog-card-excerpt"><?php echo esc_html( $bp_excerpt ); ?></p>
            <?php endif; ?>
            <div class="blog-card-footer">
              <span class="blog-card-meta">
                <?php echo esc_html( $bp_date ); ?> &middot;
                <?php printf( esc_html__( '%d min read', 'storecmsmart' ), $read_time ); ?>
              </span>
              <span class="blog-card-read">
                <?php esc_html_e( 'Read', 'storecmsmart' ); ?>
                <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </span>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div><!-- #blog-grid -->
      <?php else : ?>
      <div class="blog-empty">
        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
        <p><?php esc_html_e( 'No blog posts published yet.', 'storecmsmart' ); ?></p>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ═══════════════════════════════════════
       HOW IT WORKS
  ═══════════════════════════════════════════ -->
  <section class="how-section">
    <div class="container">
      <div class="blog-section-head-row" style="margin-bottom:0">
        <div>
          <div class="how-eyebrow"><?php esc_html_e( 'How it works', 'storecmsmart' ); ?></div>
          <h2 class="how-section-title"><?php esc_html_e( 'Order in 4 simple steps', 'storecmsmart' ); ?></h2>
          <div class="how-section-sub"><?php esc_html_e( 'From file upload to front door — fast and painless.', 'storecmsmart' ); ?></div>
        </div>
      </div>
      <div class="how-steps">
        <div class="how-step">
          <div class="how-step-num">1</div>
          <h4><?php esc_html_e( 'Choose your product', 'storecmsmart' ); ?></h4>
          <p><?php esc_html_e( 'Browse categories or start by size. Banners, rigid signs, decals, apparel — all in one place.', 'storecmsmart' ); ?></p>
        </div>
        <div class="how-step">
          <div class="how-step-num">2</div>
          <h4><?php esc_html_e( 'Upload your file', 'storecmsmart' ); ?></h4>
          <p><?php esc_html_e( 'Drop a PDF, AI, or high-res PNG. Our team does a free pre-flight check before printing.', 'storecmsmart' ); ?></p>
        </div>
        <div class="how-step">
          <div class="how-step-num">3</div>
          <h4><?php esc_html_e( 'Pick size & quantity', 'storecmsmart' ); ?></h4>
          <p><?php esc_html_e( 'Real-time pricing updates as you configure. Quantity breaks reward larger orders automatically.', 'storecmsmart' ); ?></p>
        </div>
        <div class="how-step">
          <div class="how-step-num">4</div>
          <h4><?php esc_html_e( 'We print & ship', 'storecmsmart' ); ?></h4>
          <p><?php esc_html_e( 'Standard orders enter production the next business day from our Fort Pierce facility.', 'storecmsmart' ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════
       CTA BAND
  ═══════════════════════════════════════════ -->
  <div class="blog-cta-band">
    <div class="container">
      <div class="blog-cta-inner">
        <h2><?php echo wp_kses_post( $blog_cta_ttl ); ?></h2>
        <div class="blog-cta-actions">
          <a href="<?php echo esc_url( $shop_url ); ?>" class="btn-primary">
            <?php esc_html_e( 'Start an Order', 'storecmsmart' ); ?>
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none">
              <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </a>
          <?php if ( $phone_href ) : ?>
          <a href="tel:<?php echo esc_attr( $phone_href ); ?>" class="btn-call-yellow">
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none" width="15" height="15">
              <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015 12.84 19.79 19.79 0 011.95 4.18 2 2 0 013.93 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6z"/>
            </svg>
            <?php esc_html_e( 'Call us', 'storecmsmart' ); ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</main><!-- #main-content -->

<!-- ═══════════════════════════════════════════════════════════
     ARTICLE MODAL
═══════════════════════════════════════════════════════════════ -->
<div class="bmodal-backdrop" id="blog-modal" role="dialog" aria-modal="true" aria-labelledby="bmodal-title-el">
  <div class="bmodal" id="bmodal-box">
    <!-- Close -->
    <button class="bmodal-close" id="bmodal-close-btn" aria-label="<?php esc_attr_e( 'Close', 'storecmsmart' ); ?>">
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>

    <!-- Image -->
    <div class="bmodal-img-wrap" id="bmodal-img-wrap">
      <img id="bmodal-img" src="" alt="">
    </div>

    <!-- Body -->
    <div class="bmodal-body">
      <div class="bmodal-meta-row">
        <span class="bmodal-badge" id="bmodal-badge">Article</span>
        <span class="bmodal-date" id="bmodal-date"></span>
        <span class="bmodal-read-time" id="bmodal-read-time"></span>
      </div>
      <h2 class="bmodal-title" id="bmodal-title-el"></h2>
      <div class="bmodal-content" id="bmodal-content"></div>

      <!-- CTA -->
      <div class="bmodal-cta">
        <a href="#" class="bmodal-full-btn" id="bmodal-full-link">
          <?php esc_html_e( 'Read full article', 'storecmsmart' ); ?>
          <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </a>
        <div class="bmodal-cta-divider"><?php esc_html_e( 'or', 'storecmsmart' ); ?></div>
        <p class="bmodal-cta-sub">
          <strong><?php esc_html_e( 'Ready to put this into practice?', 'storecmsmart' ); ?></strong>
          <?php esc_html_e( ' Get instant pricing for your next print project.', 'storecmsmart' ); ?>
        </p>
        <a href="<?php echo esc_url( $shop_url ); ?>" class="btn-primary bmodal-order-btn">
          <?php esc_html_e( 'Shop Now', 'storecmsmart' ); ?>
          <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    </div>
  </div>
</div><!-- .bmodal-backdrop -->

<script>
/* ── Blog post data (embedded from PHP) ─────────────────── */
var BLOG_DATA = <?php echo wp_json_encode( $modal_data ); ?>;

/* ── Category filter pills ──────────────────────────────── */
(function () {
  var pills = document.querySelectorAll('.cat-pill');
  var cards = document.querySelectorAll('.blog-card');

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      pills.forEach(function (p) { p.classList.remove('is-active'); });
      pill.classList.add('is-active');
      var cat = pill.dataset.cat;
      cards.forEach(function (card) {
        card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
      });
    });
  });
})();

/* ── Modal ──────────────────────────────────────────────── */
var _modal    = document.getElementById('blog-modal');
var _modalBox = document.getElementById('bmodal-box');

function openBlogModal(id) {
  var p = BLOG_DATA[id];
  if (!p) return;

  /* image */
  var imgWrap = document.getElementById('bmodal-img-wrap');
  var img     = document.getElementById('bmodal-img');
  if (p.thumb) {
    img.src = p.thumb;
    img.alt = p.title;
    imgWrap.style.display = '';
  } else {
    imgWrap.style.display = 'none';
  }

  /* meta */
  document.getElementById('bmodal-badge').textContent     = p.cat;
  document.getElementById('bmodal-date').textContent      = p.date;
  document.getElementById('bmodal-read-time').textContent = p.readTime + ' min read';
  document.getElementById('bmodal-title-el').textContent  = p.title;
  document.getElementById('bmodal-full-link').href        = p.url;

  /* excerpt */
  var contentEl = document.getElementById('bmodal-content');
  if (p.excerpt && p.excerpt.trim()) {
    contentEl.innerHTML = '<p>' + p.excerpt + '</p>';
  } else {
    contentEl.innerHTML =
      '<p>This article is being finalized by the team at GiganticPrints.com.' +
      ' Check back soon for the full version.</p>';
  }

  /* open */
  _modal.classList.add('is-open');
  document.body.style.overflow = 'hidden';
  _modalBox.scrollTop = 0;
}

function closeBlogModal() {
  _modal.classList.remove('is-open');
  document.body.style.overflow = '';
}

/* close button */
document.getElementById('bmodal-close-btn').addEventListener('click', closeBlogModal);

/* click backdrop (outside box) */
_modal.addEventListener('click', function (e) {
  if (e.target === _modal) closeBlogModal();
});

/* Escape key */
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && _modal.classList.contains('is-open')) closeBlogModal();
});

/* keyboard for featured post */
document.querySelectorAll('.featured-post[tabindex]').forEach(function (el) {
  el.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
  });
});

/* keyboard for cards */
document.querySelectorAll('.blog-card').forEach(function (card) {
  card.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
  });
});
</script>

<?php get_footer(); ?>
