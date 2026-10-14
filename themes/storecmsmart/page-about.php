<?php
/**
 * Template Name: About
 */
get_header();
if ( have_posts() ) { the_post(); }
?>

<main id="main-content" class="site-main page-template-page-about">

  <!-- ── Breadcrumb bar ── -->
  <div class="about-bc-wrap">
    <div class="container">
      <?php giga_breadcrumb(); ?>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════
       HERO  (dark)
  ═══════════════════════════════════════════════════════════ -->
  <section class="about-hero">
    <div class="container">
      <div class="about-hero-inner">

        <!-- LEFT: copy & CTAs -->
        <div class="about-hero-content">
          <div class="about-eyebrow">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            Fort Pierce, Florida &middot; Est. 2003
          </div>

          <h1 class="about-h1">
            Over 20 Years of<br>
            <span class="accent">Expertise in Branding</span><br>
            &amp; Design.
          </h1>

          <p class="about-lead">
            Vehicle Wrap &amp; Branding Specialist — SignEdge Graphics was established over two decades ago with a mission to elevate brands through creative design. From a garage start-up to a 5,000+ sq ft full-service facility, we serve local startups and established corporations across Florida and the nation.
          </p>

          <div class="about-hero-actions">
            <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn-primary">
              Get a Free Quote
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
            <a href="<?php echo esc_url( home_url( '/gallery' ) ); ?>" class="btn-ghost-light">
              See Our Work
            </a>
          </div>
        </div>

        <!-- RIGHT: stat + product + address cards -->
        <div class="about-hero-cards">

          <!-- Card 1: Stats -->
          <div class="ahc">
            <div class="ahc-label">By the Numbers</div>
            <div class="ahc-stats">
              <div class="ahc-stat-item">
                <span class="ahc-num">20<span class="ahc-sup">+</span></span>
                <span class="ahc-stat-label">Years in Business</span>
              </div>
              <div class="ahc-stat-item">
                <span class="ahc-num">5,000<span class="ahc-sup">+</span></span>
                <span class="ahc-stat-label">Sq Ft Facility</span>
              </div>
            </div>
          </div>

          <!-- Card 2: Products -->
          <div class="ahc">
            <div class="ahc-label">What We Make</div>
            <div class="ahc-chips">
              <span class="ahc-chip">Vehicle Wraps</span>
              <span class="ahc-chip">Marine Graphics</span>
              <span class="ahc-chip">Signage</span>
              <span class="ahc-chip">Storefronts</span>
              <span class="ahc-chip">Wall Murals</span>
              <span class="ahc-chip">Custom Logos</span>
              <span class="ahc-chip">Art &amp; Design</span>
            </div>
          </div>

          <!-- Card 3: Address -->
          <div class="ahc ahc--addr">
            <div class="ahc-label">Find Us</div>
            <div class="ahc-addr-row">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>2101 Orange Avenue, Fort Pierce, FL<br><small>Nationwide shipping available</small></span>
            </div>
          </div>

        </div><!-- .about-hero-cards -->
      </div><!-- .about-hero-inner -->
    </div><!-- .container -->
  </section><!-- .about-hero -->


  <!-- ══════════════════════════════════════════════════════════
       OUR STORY + TIMELINE
  ═══════════════════════════════════════════════════════════ -->
  <section class="section" id="story">
    <div class="container">

      <!-- Brand mark -->
      <div class="se-brand-block">
        <div class="se-brand-logo-wrap">
          <?php
          $se_logo_url = get_theme_mod( 'signedge_brand_logo', '' );
          if ( ! $se_logo_url ) {
            $se_logo_url = get_template_directory_uri() . '/assets/images/logo.svg';
          }
          ?>
          <img src="<?php echo esc_url( $se_logo_url ); ?>" alt="SignEdge Graphics" class="se-brand-logo-img">
        </div>
        <div class="se-brand-content">
          <strong class="se-brand-heading">Powered by SignEdge Graphics</strong>
          <p class="se-brand-desc">GiganticPrints.com is the online ordering arm of SignEdge Graphics — Fort Pierce's premier sign &amp; wrap studio since 2003. Every order placed here is produced in-house by our own team, on our own equipment.</p>
          <span class="se-brand-badge">Est. 2003 &middot; Fort Pierce, FL</span>
        </div>
      </div>

      <div class="about-grid">

        <!-- LEFT: story + photo -->
        <div class="about-story-col">
          <h2 class="about-story-heading">From a garage to 5,000 sq ft — and still growing.</h2>

          <div class="about-story">
            <p>SignEdge Graphics was established over two decades ago in a garage with one mission: to elevate brands through creative design. Co-founders Hoyle and Jeremy — who met in their hometown of Fort Pierce — set out to prove that world-class wraps and signage didn't require a trip to a big city. They could be done right here on the Treasure Coast.</p>

            <p>Over the years, the shop evolved from humble beginnings into a 5,000+ square foot full-service facility. Today, SignEdge serves everyone from local startups to established corporations with custom logos, vehicle wraps, marine graphics, signage, storefronts, and art — producing every order in-house, on their own equipment, with their own team.</p>

            <blockquote class="about-quote">
              <p>"To empower businesses with impactful branding through innovative design, helping them communicate their story with authenticity and style."</p>
              <cite>Hoyle &amp; Jeremy, Co-Founders</cite>
            </blockquote>
          </div>

          <div class="facility-photo-wrap">
            <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'large', [ 'class' => 'facility-photo', 'alt' => 'SignEdge Graphics facility — Fort Pierce, FL' ] ); ?>
          <?php else : ?>
            <div class="facility-photo-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
              <span>Chưa có ảnh — set Featured Image của trang About trong WP Admin</span>
            </div>
          <?php endif; ?>
            <p class="facility-photo-caption">Our production facility in Fort Pierce, Florida</p>
          </div>
        </div>

        <!-- RIGHT: timeline -->
        <div class="about-timeline-col">
          <h3 class="tl-title">Our Journey</h3>

          <div class="tl-row">
            <div class="tl-yr">Early 2000s</div>
            <div class="tl-track"><div class="tl-dot"></div><div class="tl-line"></div></div>
            <div class="tl-body">
              <strong class="tl-event">Garage Beginnings</strong>
              <p>Hoyle and Jeremy — two Fort Pierce locals with a passion for design — start SignEdge Graphics from a garage with a clear mission: elevate brands through creative design.</p>
            </div>
          </div>

          <div class="tl-row">
            <div class="tl-yr">~2005</div>
            <div class="tl-track"><div class="tl-dot"></div><div class="tl-line"></div></div>
            <div class="tl-body">
              <strong class="tl-event">First Commercial Space</strong>
              <p>Growing demand pushes SignEdge out of the garage and into a real shop. Vehicle wraps and custom signage become the core service offering.</p>
            </div>
          </div>

          <div class="tl-row">
            <div class="tl-yr">~2010</div>
            <div class="tl-track"><div class="tl-dot"></div><div class="tl-line"></div></div>
            <div class="tl-body">
              <strong class="tl-event">Marine &amp; Storefront Expansion</strong>
              <p>Added marine graphics and storefront design services, becoming the Treasure Coast's go-to for full-brand identity beyond just vehicle wraps.</p>
            </div>
          </div>

          <div class="tl-row">
            <div class="tl-yr">~2015</div>
            <div class="tl-track"><div class="tl-dot"></div><div class="tl-line"></div></div>
            <div class="tl-body">
              <strong class="tl-event">5,000+ Sq Ft Facility</strong>
              <p>Moved into the current 5,000+ sq ft production facility at 2101 Orange Avenue, Fort Pierce — with full in-house printing, laminating, and installation capabilities.</p>
            </div>
          </div>

          <div class="tl-row tl-row--now">
            <div class="tl-yr">Today</div>
            <div class="tl-track"><div class="tl-dot tl-dot--now"></div></div>
            <div class="tl-body">
              <strong class="tl-event">Expanding Digital Reach</strong>
              <p>20+ years in, Hoyle &amp; Jeremy continue leading — now embracing digital ordering and new technologies while keeping the personal, client-first service that built the brand.</p>
            </div>
          </div>

        </div><!-- .about-timeline-col -->
      </div><!-- .about-grid -->

    </div><!-- .container -->
  </section>


  <!-- ══════════════════════════════════════════════════════════
       WHY CHOOSE US  (soft bg)
  ═══════════════════════════════════════════════════════════ -->
  <section class="section section--soft" id="why">
    <div class="container">

      <div class="section-head-row">
        <div class="section-title-block">
          <span class="eyebrow">Why Choose Us</span>
          <h2 class="section-title">What sets us apart</h2>
          <p class="section-sub">Four reasons thousands of Treasure Coast customers keep coming back.</p>
        </div>
      </div>

      <div class="about-pillars">

        <div class="pillar">
          <div class="pillar-ic pillar-ic--yellow">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          </div>
          <h3 class="pillar-title">Stupid-level quality</h3>
          <p class="pillar-body">We inspect every job, every print — not because we have to, but because we can't help it. Quality control here is a personality trait.</p>
        </div>

        <div class="pillar">
          <div class="pillar-ic pillar-ic--red">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <h3 class="pillar-title">We're fast</h3>
          <p class="pillar-body">Standard orders enter production the next business day from our Fort Pierce facility, and ship nationwide.</p>
        </div>

        <div class="pillar">
          <div class="pillar-ic pillar-ic--ink">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <h3 class="pillar-title">Real people answer</h3>
          <p class="pillar-body">No chatbots, no ticket queues. Call <a href="tel:7724298818" style="color:inherit">772-429-8818</a> and you'll talk to Jeremy or Hoyle — the co-founders themselves.</p>
        </div>

        <div class="pillar">
          <div class="pillar-ic pillar-ic--red">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8Z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          </div>
          <h3 class="pillar-title">Vehicle wrap experts</h3>
          <p class="pillar-body">3M &amp; Oracal certified installers. The Treasure Coast's premier source for commercial vehicle graphics for over 20 years.</p>
        </div>

      </div><!-- .about-pillars -->
    </div><!-- .container -->
  </section>


  <!-- ══════════════════════════════════════════════════════════
       CTA BAND
  ═══════════════════════════════════════════════════════════ -->
  <div class="about-cta">
    <div class="container">
      <div class="about-cta-inner">
        <div class="about-cta-copy">
          <h2 class="about-cta-heading">Ready to Work With Us?</h2>
          <p class="about-cta-sub">Get a free design consultation and quote. Real answers from Hoyle, Jeremy, and the team who'll actually do the work — no middlemen, no bots.</p>
        </div>
        <div class="about-cta-actions">
          <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn-ink">
            Get a Free Quote
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
          <a href="<?php echo esc_url( home_url( '/gallery' ) ); ?>" class="btn-ink-outline">
            Browse the Gallery
          </a>
        </div>
      </div>
    </div>
  </div>

</main>

<?php get_footer(); ?>
