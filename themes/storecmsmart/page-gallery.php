<?php
/**
 * Template Name: Gallery Page
 */

get_header();

/* ── Gather all gallery items ── */
$_gallery_args = [
    'post_type'      => 'gallery_item',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
];
$_gallery_posts = get_posts( $_gallery_args );

/* Build JS-safe data array */
$_gallery_data = [];
foreach ( $_gallery_posts as $_gp ) {
    $_span  = get_post_meta( $_gp->ID, '_gallery_span', true ) ?: 'normal';
    $_terms = wp_get_post_terms( $_gp->ID, 'gallery_type', [ 'fields' => 'all' ] );
    $_cats  = array_map( fn( $t ) => $t->slug, $_terms );
    $_cats_labels = array_map( fn( $t ) => $t->name, $_terms );
    $_gallery_data[] = [
        'id'        => $_gp->ID,
        'title'     => $_gp->post_title,
        'thumb'     => (string) get_the_post_thumbnail_url( $_gp->ID, 'large' ),
        'thumb_sm'  => (string) get_the_post_thumbnail_url( $_gp->ID, 'medium_large' ),
        'span'      => $_span,
        'cats'      => $_cats,
        'catsLabel' => implode( ', ', $_cats_labels ),
    ];
}

/* All taxonomy terms for filter pills */
$_gallery_types = get_terms( [
    'taxonomy'   => 'gallery_type',
    'hide_empty' => true,
    'orderby'    => 'name',
] );
?>

<main id="main-content" class="site-main">

<!-- ─── HERO ─── -->
<section class="gallery-hero">
  <div class="gallery-hero-inner container">
    <div class="gallery-hero-text">
      <div class="gallery-bc-wrap"><?php giga_breadcrumb(); ?></div>
      <span class="gallery-hero-eyebrow">Our Work</span>
      <h1>See What We <span class="accent">Create</span></h1>
      <p class="gallery-hero-lead">From vehicle wraps to wall murals — every project is built to make you stand out. Browse our portfolio below.</p>
    </div>
    <div class="gallery-hero-stats">
      <div class="hero-stat">
        <span class="hero-stat-num">23+</span>
        <span class="hero-stat-label">Years of Experience</span>
      </div>
      <div class="hero-stat">
        <span class="hero-stat-num">5,100</span>
        <span class="hero-stat-label">Sq Ft Facility</span>
      </div>
      <div class="hero-stat">
        <span class="hero-stat-num">24hr</span>
        <span class="hero-stat-label">Rush Turnaround</span>
      </div>
    </div>
  </div>
</section>

<!-- ─── FILTER TOOLBAR ─── -->
<div class="gallery-toolbar" id="gallery-toolbar">
  <div class="gallery-toolbar-inner container">
    <div class="gallery-search-wrap">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="gallery-search" placeholder="Search projects…" autocomplete="off" />
    </div>
    <div class="gallery-filter-pills">
      <button class="gpill is-active" data-cat="all">All Work</button>
      <?php foreach ( $_gallery_types as $_gt ) : ?>
        <button class="gpill" data-cat="<?php echo esc_attr( $_gt->slug ); ?>"><?php echo esc_html( $_gt->name ); ?></button>
      <?php endforeach; ?>
    </div>
    <span class="gallery-count-label"><span id="gallery-visible-count"><?php echo count( $_gallery_data ); ?></span> projects</span>
  </div>
</div>

<!-- ─── GALLERY GRID ─── -->
<section class="gallery-section">
  <div class="container">
    <?php if ( empty( $_gallery_data ) ) : ?>
      <p class="gallery-empty">No gallery items yet. <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gallery_item' ) ); ?>">Add some in the admin</a>.</p>
    <?php else : ?>
      <div class="gallery-grid" id="gallery-grid">
        <?php foreach ( $_gallery_data as $_gi_idx => $_gi ) :
            $_span_class = $_gi['span'] !== 'normal' ? ' span-' . $_gi['span'] : '';
            $_hidden     = $_gi_idx >= 24 ? ' gallery-hidden' : '';
            $_cats_attr  = implode( ' ', $_gi['cats'] );
        ?>
          <div class="gallery-item<?php echo $_span_class . $_hidden; ?>"
               data-cat="<?php echo esc_attr( $_cats_attr ); ?>"
               data-title="<?php echo esc_attr( strtolower( $_gi['title'] ) ); ?>"
               data-index="<?php echo $_gi_idx; ?>">
            <?php if ( $_gi['thumb'] ) : ?>
              <img src="<?php echo esc_url( $_gi['thumb'] ); ?>"
                   alt="<?php echo esc_attr( $_gi['title'] ); ?>"
                   loading="lazy" />
            <?php else : ?>
              <div class="gallery-item-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5" opacity=".4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            <?php endif; ?>
            <div class="gallery-item-overlay">
              <span class="gallery-item-cat"><?php echo esc_html( $_gi['catsLabel'] ); ?></span>
              <span class="gallery-item-title"><?php echo esc_html( $_gi['title'] ); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ( count( $_gallery_data ) > 24 ) : ?>
        <div class="gallery-showmore-wrap">
          <button class="btn-show-more" id="gallery-show-more">
            Show More Work
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ─── LIGHTBOX ─── -->
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lb-close" id="lb-close" aria-label="Close">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
  <button class="lb-prev" id="lb-prev" aria-label="Previous">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <button class="lb-next" id="lb-next" aria-label="Next">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  <div class="lb-content">
    <img id="lb-img" src="" alt="" />
    <div class="lb-caption">
      <span id="lb-cat" class="lb-badge"></span>
      <span id="lb-title"></span>
      <span id="lb-counter" class="lb-counter-text"></span>
    </div>
  </div>
</div>
<div class="lb-backdrop" id="lb-backdrop"></div>

<!-- ─── YOUTUBE SECTION ─── -->
<section class="gallery-youtube-section">
  <div class="container yt-inner">

    <!-- Header -->
    <div class="yt-header">
      <span class="yt-eyebrow">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="#ff0000"><path d="M23.5 6.2s-.3-1.9-1.1-2.7c-1.1-1.1-2.3-1.1-2.8-1.2C16.8 2 12 2 12 2s-4.8 0-7.6.3c-.6.1-1.8.1-2.8 1.2C.7 4.3.4 6.2.4 6.2S.1 8.4.1 10.6v2.1c0 2.2.3 4.4.3 4.4s.3 1.9 1.1 2.7c1.1 1.1 2.5 1.1 3.1 1.1C6.7 21 12 21 12 21s4.8 0 7.6-.3c.6-.1 1.8-.1 2.8-1.2.8-.8 1.1-2.7 1.1-2.7s.3-2.2.3-4.4v-2.1c0-2.2-.3-4.4-.3-4.4zM9.7 14.8V8.5l6.5 3.2-6.5 3.1z"/></svg>
        Watch Us Work
      </span>
      <h2 class="yt-heading">See it done.<br><span class="yt-heading-yellow">Behind the wrap.</span></h2>
      <p class="yt-subhead">Real installs. Real clients. Filmed at our 5,100 sq ft facility in Fort Pierce, FL — or out in the field.</p>
    </div>

    <!-- Video + Project card -->
    <div class="yt-main-grid">
      <div class="yt-video-col">
        <div class="yt-iframe-wrap">
          <iframe
            src="https://www.youtube.com/embed/erAH6Y018_I"
            title="SignEdge Graphics — Sea Turtle Education Trailer"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>
        <div class="yt-watch-link">
          <a href="https://www.youtube.com/watch?v=erAH6Y018_I" target="_blank" rel="noopener">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Watch on YouTube
          </a>
        </div>
      </div>

      <div class="yt-project-card">
        <span class="yt-featured-badge">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          Featured Project
        </span>
        <h3 class="yt-project-title">St. Lucie County — Sea Turtle Education Trailer</h3>
        <p class="yt-project-desc">St. Lucie County's Environmental Resource Department came to us to turn a plain trailer into a mobile Sea Turtle awareness display. Watch the full wrap reveal — concept to completion.</p>
        <ul class="yt-project-meta">
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
            Fort Pierce, FL · St. Lucie County
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
            Trailer wrap · Environmental signage
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
            Produced in-house · SignEdge Graphics
          </li>
        </ul>
        <a href="https://www.youtube.com/watch?v=erAH6Y018_I" target="_blank" rel="noopener" class="yt-watch-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Watch on YouTube
        </a>
      </div>
    </div>



    <!-- Subscribe strip — inside dark section -->
    <div class="yt-subscribe-bar">
      <div class="yt-subscribe-bar-left">
        <div class="yt-sub-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M23.5 6.2s-.3-1.9-1.1-2.7c-1.1-1.1-2.3-1.1-2.8-1.2C16.8 2 12 2 12 2s-4.8 0-7.6.3c-.6.1-1.8.1-2.8 1.2C.7 4.3.4 6.2.4 6.2S.1 8.4.1 10.6v2.1c0 2.2.3 4.4.3 4.4s.3 1.9 1.1 2.7c1.1 1.1 2.5 1.1 3.1 1.1C6.7 21 12 21 12 21s4.8 0 7.6-.3c.6-.1 1.8-.1 2.8-1.2.8-.8 1.1-2.7 1.1-2.7s.3-2.2.3-4.4v-2.1c0-2.2-.3-4.4-.3-4.4zM9.7 14.8V8.5l6.5 3.2-6.5 3.1z"/></svg>
        </div>
        <div>
          <strong>Follow us on YouTube</strong>
          <span>New wraps, installs and behind-the-scenes from our Fort Pierce shop — @SignEdge_Graphics</span>
        </div>
      </div>
      <a href="https://www.youtube.com/@SignEdge_Graphics" target="_blank" rel="noopener" class="yt-sub-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2s-.3-1.9-1.1-2.7c-1.1-1.1-2.3-1.1-2.8-1.2C16.8 2 12 2 12 2s-4.8 0-7.6.3c-.6.1-1.8.1-2.8 1.2C.7 4.3.4 6.2.4 6.2S.1 8.4.1 10.6v2.1c0 2.2.3 4.4.3 4.4s.3 1.9 1.1 2.7c1.1 1.1 2.5 1.1 3.1 1.1C6.7 21 12 21 12 21s4.8 0 7.6-.3c.6-.1 1.8-.1 2.8-1.2.8-.8 1.1-2.7 1.1-2.7s.3-2.2.3-4.4v-2.1c0-2.2-.3-4.4-.3-4.4zM9.7 14.8V8.5l6.5 3.2-6.5 3.1z"/></svg>
        Subscribe
      </a>
    </div>

  </div><!-- /.container.yt-inner -->

</section>

<!-- ─── REVIEWS ─── -->
<section class="gallery-reviews-section">
  <div class="container">
    <div class="reviews-header">
      <div>
        <span class="reviews-eyebrow">What Clients Say</span>
        <h2>Trusted by <span class="accent">Businesses</span> Across the Region</h2>
      </div>
      <div class="reviews-nav">
        <button class="rev-arrow rev-prev" id="rev-prev" aria-label="Previous review">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="rev-arrow rev-next" id="rev-next" aria-label="Next review">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    <div class="reviews-track-wrap">
      <div class="reviews-track" id="reviews-track">

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"SignEdge did an incredible job on our fleet wraps. The quality is outstanding and they delivered ahead of schedule. Our trucks have never looked better — we get compliments everywhere we go!"</p>
          <div class="review-author">
            <div class="review-avatar">J</div>
            <div>
              <strong>James M.</strong>
              <span>Fleet Manager, Miami Logistics</span>
            </div>
          </div>
        </div>

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"We needed custom banners fast for our grand opening and SignEdge came through! Same-day turnaround, vibrant colors, and super easy to work with. Will definitely use them again."</p>
          <div class="review-author">
            <div class="review-avatar">S</div>
            <div>
              <strong>Sarah T.</strong>
              <span>Owner, Sunshine Boutique</span>
            </div>
          </div>
        </div>

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Best print shop in Fort Pierce. Exceptional attention to detail on our wall mural — the colors are perfect and the install team was professional and fast. Highly recommend!"</p>
          <div class="review-author">
            <div class="review-avatar">R</div>
            <div>
              <strong>Roberto A.</strong>
              <span>Director, Palm City Real Estate</span>
            </div>
          </div>
        </div>

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"We've used SignEdge for three trade show booths now. Every time they nail it — sharp graphics, on time, and the staff is always helpful and friendly. Wouldn't go anywhere else!"</p>
          <div class="review-author">
            <div class="review-avatar">K</div>
            <div>
              <strong>Karen W.</strong>
              <span>Marketing Manager, TechFlex Solutions</span>
            </div>
          </div>
        </div>

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Had my entire van wrapped and couldn't be happier. The design process was easy, the team was professional, and the final result exceeded my expectations. Getting calls asking who did the wrap!"</p>
          <div class="review-author">
            <div class="review-avatar">D</div>
            <div>
              <strong>David L.</strong>
              <span>Owner, DL Plumbing Services</span>
            </div>
          </div>
        </div>

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"SignEdge installed full window graphics on our storefront and it looks amazing. Foot traffic increased noticeably within the first week. Fast install, zero mess, and the price was fair."</p>
          <div class="review-author">
            <div class="review-avatar">M</div>
            <div>
              <strong>Michelle P.</strong>
              <span>Owner, Treasure Coast Bakery</span>
            </div>
          </div>
        </div>

        <div class="review-card">
          <div class="review-source">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook Review
          </div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Ordered custom t-shirts and yard signs for our charity 5K. Everything arrived on time and looked better than we imagined. The team was patient with our design revisions. Will be back next year!"</p>
          <div class="review-author">
            <div class="review-avatar">T</div>
            <div>
              <strong>Tanya B.</strong>
              <span>Event Coordinator, Helping Hands Foundation</span>
            </div>
          </div>
        </div>

      </div>
    </div>
    <div class="reviews-dots" id="reviews-dots">
      <button class="rev-dot is-active" data-idx="0"></button>
      <button class="rev-dot" data-idx="1"></button>
      <button class="rev-dot" data-idx="2"></button>
      <button class="rev-dot" data-idx="3"></button>
      <button class="rev-dot" data-idx="4"></button>
      <button class="rev-dot" data-idx="5"></button>
      <button class="rev-dot" data-idx="6"></button>
      <button class="rev-dot" data-idx="7"></button>
    </div>
  </div>
</section>

<!-- ─── TRUST MARQUEE ─── -->
<div class="gallery-marquee-wrap">
  <div class="gallery-marquee">
    <div class="marquee-track">
      <?php
      $_mtags = ['Vehicle Wraps','Banners','Signage','Wall Murals','Trade Show Displays','Floor Graphics','Window Graphics','Apparel','Same-Day Rush','Fort Pierce, FL'];
      $mt_html = '';
      foreach ( $_mtags as $_mt ) {
          $mt_html .= '<span class="marquee-item"><span class="marquee-dot">●</span>' . esc_html( $_mt ) . '</span>';
      }
      echo $mt_html . $mt_html; // duplicate for seamless loop
      ?>
    </div>
  </div>
</div>

<!-- ─── CTA BAND ─── -->
<section class="gallery-cta-band">
  <div class="gallery-cta-inner container">
    <div class="gallery-cta-text">
      <h2>Ready to wrap, sign or brand your vehicle?</h2>
      <p>Same team, same production floor — now available to order 24/7 online through GiganticPrints.com.</p>
    </div>
    <div class="gallery-cta-btns">
      <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn-cta-dark">Start an order →</a>
      <a href="tel:+17724298818" class="btn-cta-outline">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.78 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
        Call us
      </a>
    </div>
  </div>
</section>

</main>

<!-- ─── EMBED DATA ─── -->
<script>
var GALLERY_DATA = <?php echo wp_json_encode( array_values( $_gallery_data ) ); ?>;
</script>

<script>
(function () {
'use strict';

/* ── Gallery filter + search ── */
var grid       = document.getElementById('gallery-grid');
var allItems   = grid ? Array.from(grid.querySelectorAll('.gallery-item')) : [];
var pills      = Array.from(document.querySelectorAll('.gpill'));
var searchInp  = document.getElementById('gallery-search');
var countEl    = document.getElementById('gallery-visible-count');
var showMoreBtn= document.getElementById('gallery-show-more');
var PAGE_SIZE  = 24;
var showAll    = false;
var activeCat  = 'all';
var searchVal  = '';

function matchItem(item) {
  var cats  = item.getAttribute('data-cat') || '';
  var title = item.getAttribute('data-title') || '';
  var catOk = activeCat === 'all' || cats.split(' ').indexOf(activeCat) !== -1;
  var srchOk = !searchVal || title.indexOf(searchVal) !== -1;
  return catOk && srchOk;
}

function applyFilter() {
  var matched = 0;
  allItems.forEach(function(item, idx) {
    var ok = matchItem(item);
    var overLimit = !showAll && matched >= PAGE_SIZE;
    if (ok && !overLimit) {
      item.classList.remove('gallery-hidden');
      item.style.display = '';
      matched++;
    } else if (ok && overLimit) {
      item.classList.add('gallery-hidden');
      item.style.display = '';
      matched++;
    } else {
      item.classList.add('gallery-hidden');
      item.style.display = 'none';
    }
  });
  var totalMatched = allItems.filter(matchItem).length;
  if (countEl) countEl.textContent = showAll ? totalMatched : Math.min(PAGE_SIZE, totalMatched);
  if (showMoreBtn) {
    showMoreBtn.style.display = (totalMatched > PAGE_SIZE && !showAll) ? '' : 'none';
  }
  buildLightboxList();
}

pills.forEach(function(pill) {
  pill.addEventListener('click', function() {
    pills.forEach(function(p) { p.classList.remove('is-active'); });
    pill.classList.add('is-active');
    activeCat = pill.getAttribute('data-cat');
    showAll = false;
    applyFilter();
  });
});

if (searchInp) {
  searchInp.addEventListener('input', function() {
    searchVal = searchInp.value.trim().toLowerCase();
    showAll = false;
    applyFilter();
  });
}

if (showMoreBtn) {
  showMoreBtn.addEventListener('click', function() {
    showAll = true;
    applyFilter();
  });
}

/* ── Lightbox ── */
var lb         = document.getElementById('lightbox');
var lbBackdrop = document.getElementById('lb-backdrop');
var lbImg      = document.getElementById('lb-img');
var lbCat      = document.getElementById('lb-cat');
var lbTitle    = document.getElementById('lb-title');
var lbCounter  = document.getElementById('lb-counter');
var lbList     = [];
var lbCurrent  = 0;

function buildLightboxList() {
  lbList = allItems.filter(function(item) {
    return matchItem(item);
  }).map(function(item) {
    var idx = parseInt(item.getAttribute('data-index'), 10);
    var d = GALLERY_DATA[idx] || {};
    return {
      src  : d.thumb || '',
      title: d.title || '',
      cat  : d.catsLabel || ''
    };
  }).filter(function(d) { return d.src; });
}

function openLightbox(idx) {
  if (!lb || !lbList.length) return;
  lbCurrent = idx;
  showLightboxItem();
  lb.classList.add('is-open');
  lbBackdrop.style.display = 'block';
  document.body.style.overflow = 'hidden';
  lb.focus();
}

function showLightboxItem() {
  var d = lbList[lbCurrent];
  if (!d) return;
  lbImg.src = d.src;
  lbImg.alt = d.title;
  if (lbCat) lbCat.textContent = d.cat;
  if (lbTitle) lbTitle.textContent = d.title;
  if (lbCounter) lbCounter.textContent = (lbCurrent + 1) + ' / ' + lbList.length;
}

function closeLightbox() {
  if (!lb) return;
  lb.classList.remove('is-open');
  lbBackdrop.style.display = 'none';
  lbImg.src = '';
  document.body.style.overflow = '';
}

function lbPrev() { lbCurrent = (lbCurrent - 1 + lbList.length) % lbList.length; showLightboxItem(); }
function lbNext() { lbCurrent = (lbCurrent + 1) % lbList.length; showLightboxItem(); }

document.getElementById('lb-close') && document.getElementById('lb-close').addEventListener('click', closeLightbox);
document.getElementById('lb-prev')  && document.getElementById('lb-prev').addEventListener('click', lbPrev);
document.getElementById('lb-next')  && document.getElementById('lb-next').addEventListener('click', lbNext);
lbBackdrop && lbBackdrop.addEventListener('click', closeLightbox);

document.addEventListener('keydown', function(e) {
  if (lb && lb.classList.contains('is-open')) {
    if (e.key === 'Escape')     closeLightbox();
    if (e.key === 'ArrowLeft')  lbPrev();
    if (e.key === 'ArrowRight') lbNext();
  }
});

/* Attach click to gallery items */
if (grid) {
  grid.addEventListener('click', function(e) {
    var item = e.target.closest('.gallery-item');
    if (!item) return;
    var visibleList = allItems.filter(function(it) {
      return matchItem(it) && it.style.display !== 'none';
    });
    var pos = visibleList.indexOf(item);
    if (pos !== -1) openLightbox(pos);
  });
}

/* ── Reviews carousel ── */
var track     = document.getElementById('reviews-track');
var dotsWrap  = document.getElementById('reviews-dots');
var dots      = Array.from(document.querySelectorAll('.rev-dot'));
var revIdx    = 0;
var revTimer  = null;

function getRevCards() { return track ? Array.from(track.querySelectorAll('.review-card')) : []; }

function scrollToReview(idx) {
  if (!track) return;
  var cards = getRevCards();
  if (!cards.length) return;
  revIdx = ((idx % cards.length) + cards.length) % cards.length;
  var target = cards[revIdx];
  track.scrollTo({ left: target.offsetLeft - track.offsetLeft, behavior: 'smooth' });
  dots.forEach(function(d, i) { d.classList.toggle('is-active', i === revIdx); });
}

function revNext() { scrollToReview(revIdx + 1); }
function revPrev() { scrollToReview(revIdx - 1); }

function startAutoPlay() {
  clearInterval(revTimer);
  revTimer = setInterval(revNext, 5000);
}

document.getElementById('rev-prev') && document.getElementById('rev-prev').addEventListener('click', function() {
  revPrev(); startAutoPlay();
});
document.getElementById('rev-next') && document.getElementById('rev-next').addEventListener('click', function() {
  revNext(); startAutoPlay();
});
dots.forEach(function(dot) {
  dot.addEventListener('click', function() {
    scrollToReview(parseInt(dot.getAttribute('data-idx'), 10));
    startAutoPlay();
  });
});

/* pause on hover */
if (track) {
  track.addEventListener('mouseenter', function() { clearInterval(revTimer); });
  track.addEventListener('mouseleave', startAutoPlay);
}

startAutoPlay();

/* ── Sticky toolbar ── */
var toolbar = document.getElementById('gallery-toolbar');
if (toolbar) {
  var toolbarTop = toolbar.getBoundingClientRect().top + window.scrollY;
  window.addEventListener('scroll', function() {
    toolbar.classList.toggle('is-sticky', window.scrollY > toolbarTop - 60);
  }, { passive: true });
}

/* Init */
applyFilter();

})();
</script>

<?php get_footer(); ?>
