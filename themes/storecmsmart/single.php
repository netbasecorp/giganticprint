<?php get_header(); ?>

<?php if ( have_posts() ) : the_post(); ?>

<?php
/* ── Data prep ─────────────────────────────────── */
$cats        = get_the_category();
$primary_cat = $cats ? $cats[0] : null;

// Category pill colour class
$cat_map = [
  'product'  => 'cat-products', 'material' => 'cat-products',
  'design'   => 'cat-design',
  'industr'  => 'cat-industry',
  'saving'   => 'cat-savings', 'tip' => 'cat-savings',
];
$pill_class = 'cat-products';
if ( $primary_cat ) {
  foreach ( $cat_map as $kw => $cls ) {
    if ( stripos( $primary_cat->slug, $kw ) !== false || stripos( $primary_cat->name, $kw ) !== false ) {
      $pill_class = $cls; break;
    }
  }
}

// Read time (250 wpm)
$word_count = str_word_count( strip_tags( get_the_content() ) );
$read_time  = max( 1, round( $word_count / 250 ) );

// Author
$author_name = get_the_author();
$author_bio  = get_the_author_meta( 'description' );
if ( ! $author_bio ) {
  $author_bio = 'The SignEdge team writes from the production floor — sharing real expertise from 20+ years of large-format printing.';
}
$parts       = explode( ' ', trim( $author_name ) );
$author_init = strtoupper( substr( $parts[0], 0, 1 ) . ( isset( $parts[1] ) ? substr( $parts[1], 0, 1 ) : '' ) );

// City
$city = get_theme_mod( 'giga_city', 'Fort Pierce, FL' );

// Shop URL
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop' );

// Post tags
$post_tags = get_the_tags();
?>

<main id="main-content" class="site-main page-template-single-post">

  <!-- ── Breadcrumb ─────────────────────────────── -->
  <div class="sp-bc-wrap">
    <div class="container">
      <?php giga_breadcrumb(); ?>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════
       ARTICLE HERO  (dark)
  ═════════════════════════════════════════════════ -->
  <section class="article-hero">
    <div class="container">
      <div class="article-hero-inner">

        <!-- Left: category + title + meta -->
        <div class="article-hero-left">
          <?php if ( $primary_cat ) : ?>
            <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>"
               class="article-category-pill <?php echo esc_attr( $pill_class ); ?>">
              <?php echo esc_html( $primary_cat->name ); ?>
            </a>
          <?php endif; ?>

          <h1 class="article-hero-h1"><?php the_title(); ?></h1>

          <div class="article-hero-meta-row">
            <div class="article-hero-meta">
              <span class="ah-author"><?php echo esc_html( $author_name ); ?></span>
              <span class="ah-dot"></span>
              <span><?php echo get_the_date(); ?></span>
              <span class="ah-dot"></span>
              <span class="ah-read-time">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?php echo $read_time; ?> min read
              </span>
              <?php if ( $city ) : ?>
                <span class="ah-dot"></span>
                <span><?php echo esc_html( $city ); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Right: share buttons -->
        <div class="hero-share-topright">
          <div class="hero-share-label">Share</div>
          <div class="hero-share-btns">
            <button class="share-sticky-btn" title="Copy link"
              onclick="navigator.clipboard&&navigator.clipboard.writeText(location.href).then(function(){var el=this;el.classList.add('copied');setTimeout(function(){el.classList.remove('copied')},1500)}.bind(this))"
              aria-label="Copy link">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
            </button>
            <button class="share-sticky-btn" title="Share on Facebook"
              onclick="window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href),'_blank')"
              aria-label="Share on Facebook">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
            </button>
            <button class="share-sticky-btn" title="Share on Twitter"
              onclick="window.open('https://twitter.com/intent/tweet?url='+encodeURIComponent(location.href)+'&text='+encodeURIComponent(document.title),'_blank')"
              aria-label="Share on Twitter">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="17" y1="7" x2="7" y2="17"/><polyline points="7 7 7 17 17 17"/></svg>
            </button>
          </div>
        </div>

      </div><!-- .article-hero-inner -->
    </div><!-- .container -->
  </section>

  <!-- ════════════════════════════════════════════════
       ARTICLE BODY  (2-column layout)
  ═════════════════════════════════════════════════ -->
  <div class="container">
    <div class="article-page">

      <!-- ── Main article ─────────────────────────── -->
      <article class="article-main">

        <!-- Cover image -->
        <?php if ( has_post_thumbnail() ) : ?>
          <div class="article-cover-img">
            <?php the_post_thumbnail( 'large', [ 'alt' => esc_attr( get_the_title() ) ] ); ?>
          </div>
        <?php endif; ?>

        <!-- Article prose -->
        <div class="article-prose">
          <?php the_content(); ?>
        </div>

        <!-- ── Article footer ── -->
        <div class="article-footer">
          <div class="article-footer-row">

            <!-- Tags -->
            <?php if ( $post_tags ) : ?>
              <div class="article-tags">
                <span class="tags-label">Tags:</span>
                <?php foreach ( $post_tags as $tag ) : ?>
                  <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"
                     class="sp-tag"><?php echo esc_html( $tag->name ); ?></a>
                <?php endforeach; ?>
              </div>
            <?php else : ?>
              <div></div>
            <?php endif; ?>

            <!-- Share -->
            <button class="sp-share-btn"
              onclick="navigator.clipboard&&navigator.clipboard.writeText(location.href)"
              aria-label="Copy link to this article">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
              Copy link
            </button>

          </div><!-- .article-footer-row -->

          <!-- Author card -->
          <div class="author-card">
            <div class="author-avatar"><?php echo esc_html( $author_init ?: 'SG' ); ?></div>
            <div class="author-info">
              <h4 class="author-name"><?php echo esc_html( $author_name ); ?></h4>
              <div class="author-role">Print Expert &middot; <?php echo esc_html( $city ); ?></div>
              <p><?php echo esc_html( $author_bio ); ?></p>
            </div>
          </div>

        </div><!-- .article-footer -->

      </article><!-- .article-main -->

      <!-- ── Sidebar ───────────────────────────────── -->
      <aside class="article-sidebar">

        <!-- CTA widget -->
        <div class="sidebar-cta-widget">
          <h4>Order <span>Today</span></h4>
          <p>Real-time pricing. Ships from Fort Pierce in 24 hrs.</p>
          <a href="<?php echo esc_url( $shop_url ); ?>" class="sidebar-cta-btn">Get Instant Pricing</a>
        </div>

        <!-- TOC — populated by JS -->
        <div class="sidebar-widget" id="toc-widget" style="display:none;">
          <div class="sidebar-widget-title">In This Article</div>
          <ul class="sidebar-toc" id="sidebar-toc"></ul>
        </div>

        <!-- Sidebar related posts -->
        <?php
        $sidebar_related = new WP_Query( [
          'post_type'      => 'post',
          'posts_per_page' => 3,
          'post__not_in'   => [ get_the_ID() ],
          'orderby'        => 'rand',
          'category__in'   => $primary_cat ? [ $primary_cat->term_id ] : [],
        ] );
        if ( $sidebar_related->have_posts() ) : ?>
          <div class="sidebar-widget">
            <div class="sidebar-widget-title">Related Articles</div>
            <ul class="sidebar-related">
              <?php while ( $sidebar_related->have_posts() ) : $sidebar_related->the_post();
                $sr_words = str_word_count( strip_tags( get_the_content() ) );
                $sr_time  = max( 1, round( $sr_words / 250 ) );
              ?>
                <li class="sidebar-related-item">
                  <a href="<?php the_permalink(); ?>">
                    <div class="rel-thumb">
                      <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( [ 56, 56 ] ); ?>
                      <?php else : ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                      <?php endif; ?>
                    </div>
                    <div class="rel-text">
                      <div class="rel-title"><?php the_title(); ?></div>
                      <div class="rel-meta"><?php echo get_the_date( 'M j, Y' ); ?> &middot; <?php echo $sr_time; ?> min</div>
                    </div>
                  </a>
                </li>
              <?php endwhile; wp_reset_postdata(); ?>
            </ul>
          </div>
        <?php endif; ?>

      </aside><!-- .article-sidebar -->

    </div><!-- .article-page -->
  </div><!-- .container -->


  <!-- ════════════════════════════════════════════════
       RELATED ARTICLES  (bottom grid)
  ═════════════════════════════════════════════════ -->
  <?php
  $related_bottom = new WP_Query( [
    'post_type'      => 'post',
    'posts_per_page' => 3,
    'post__not_in'   => [ get_the_ID() ],
    'orderby'        => 'date',
    'order'          => 'DESC',
    'category__in'   => $primary_cat ? [ $primary_cat->term_id ] : [],
  ] );
  if ( $related_bottom->have_posts() ) : ?>

    <section class="related-section">
      <div class="container">
        <div class="section-head-row">
          <div>
            <div class="eyebrow">Keep Reading</div>
            <h2 class="section-title">
              <?php echo $primary_cat ? 'More from ' . esc_html( $primary_cat->name ) : 'More Articles'; ?>
            </h2>
          </div>
          <a href="<?php echo esc_url( home_url( '/blog' ) ); ?>" class="section-link">
            All articles
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
        </div>

        <div class="related-grid">
          <?php while ( $related_bottom->have_posts() ) : $related_bottom->the_post();
            $rc_cats  = get_the_category();
            $rc_cat   = $rc_cats ? $rc_cats[0] : null;
            $rc_pill  = 'cat-products';
            if ( $rc_cat ) {
              foreach ( $cat_map as $kw => $cls ) {
                if ( stripos( $rc_cat->slug, $kw ) !== false ) { $rc_pill = $cls; break; }
              }
            }
            $rc_words = str_word_count( strip_tags( get_the_content() ) );
            $rc_time  = max( 1, round( $rc_words / 250 ) );
          ?>
            <article class="article-card">
              <div class="article-card-thumb">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'medium' ); ?>
                <?php else : ?>
                  <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <?php endif; ?>
              </div>
              <div class="article-card-body">
                <?php if ( $rc_cat ) : ?>
                  <span class="article-category-pill <?php echo esc_attr( $rc_pill ); ?>"><?php echo esc_html( $rc_cat->name ); ?></span>
                <?php endif; ?>
                <h3 class="ac-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p class="ac-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
                <div class="article-card-footer">
                  <div class="article-meta">
                    <span><?php echo get_the_date( 'M j, Y' ); ?></span>
                    <span class="am-dot"></span>
                    <span><?php echo $rc_time; ?> min</span>
                  </div>
                  <a href="<?php the_permalink(); ?>" class="read-link">
                    Read
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                  </a>
                </div>
              </div>
            </article>
          <?php endwhile; wp_reset_postdata(); ?>
        </div><!-- .related-grid -->
      </div><!-- .container -->
    </section>

  <?php endif; /* related_bottom */ ?>


  <!-- ════════════════════════════════════════════════
       NEWSLETTER BAND — chỉ hiện khi có shortcode
  ═════════════════════════════════════════════════ -->
  <?php $nl_shortcode = get_theme_mod( 'giga_newsletter_shortcode', '' ); ?>
  <?php if ( $nl_shortcode ) : ?>
    <section class="newsletter-band">
      <div class="container">
        <div class="newsletter-inner">
          <div class="newsletter-copy">
            <h2>New articles every two weeks.</h2>
            <p>Join thousands of business owners and sign pros who get our print tips free.</p>
          </div>
          <?php echo do_shortcode( wp_kses_post( $nl_shortcode ) ); ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

</main><!-- #main-content -->

<?php endif; /* have_posts */ ?>

<script>
(function () {
  // Build TOC from H2 headings inside article-prose
  var prose  = document.querySelector('.article-prose');
  var tocEl  = document.getElementById('sidebar-toc');
  var widget = document.getElementById('toc-widget');
  if (!prose || !tocEl || !widget) return;

  var headings = prose.querySelectorAll('h2');
  if (headings.length < 2) return;

  headings.forEach(function (h, i) {
    if (!h.id) h.id = 'sp-h-' + i;
    var li = document.createElement('li');
    var a  = document.createElement('a');
    a.href = '#' + h.id;
    a.textContent = h.textContent;
    li.appendChild(a);
    tocEl.appendChild(li);
  });
  widget.style.display = '';

  // Highlight active TOC link on scroll
  window.addEventListener('scroll', function () {
    var y = window.scrollY + 140;
    var active = null;
    headings.forEach(function (h) { if (h.offsetTop <= y) active = h.id; });
    tocEl.querySelectorAll('a').forEach(function (a) {
      a.classList.toggle('toc-active', a.getAttribute('href') === '#' + active);
    });
  }, { passive: true });

  // Smooth scroll
  tocEl.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });
})();
</script>

<?php get_footer(); ?>
