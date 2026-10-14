<?php
/**
 * Template Name: Contact Page
 * Template Post Type: page
 *
 * Apply: WP Admin → Pages → Contact → Page Attributes → Template → "Contact Page"
 * If the page slug is "contact" this template is also used automatically.
 *
 * Form: set a WPForms shortcode in Customize → Contact Page → Form shortcode.
 * If left empty the raw page content (the_content) is rendered in the form area —
 * edit the page in the block editor to contain only the WPForms block/shortcode.
 */

get_header();

$phone      = giga_option( 'giga_footer_phone',      '' );
$phone_href = giga_option( 'giga_footer_phone_href',  $phone ? preg_replace( '/[^0-9+]/', '', $phone ) : '' );
$email      = giga_option( 'giga_footer_email',      '' );
$address    = giga_option( 'giga_footer_address',    '' );
$hours      = giga_option( 'giga_footer_hours',      '' );
$city       = giga_option( 'giga_city',              '' );
$hero_title = giga_option( 'giga_contact_hero_title', 'Talk to us.<br><span class="accent">We actually pick up.</span>' );
$hero_desc  = giga_option( 'giga_contact_hero_desc',  'No chatbots. No ticket queues. Call, email, or use the form below — same team behind every order.' );
$form_sc    = giga_option( 'giga_contact_form_shortcode', '' );
$map_src    = giga_option( 'giga_contact_map_src',   '' );
$soc_ig     = giga_option( 'giga_social_instagram',  '' );
$soc_fb     = giga_option( 'giga_social_facebook',   '' );
$soc_yt     = giga_option( 'giga_social_youtube',    '' );

?>
<main id="main-content" class="contact-page">

  <!-- ── BREADCRUMB ── -->
  <div class="contact-bc-wrap">
    <div class="container"><?php giga_breadcrumb(); ?></div>
  </div>

  <!-- ════════════════════════════════════════════════
       HERO — dark, matches about-page pattern
  ════════════════════════════════════════════════ -->
  <section class="contact-hero">
    <div class="container">
      <div class="contact-hero-inner">

        <!-- Copy side -->
        <div class="contact-hero-copy">
          <?php if ( $city || $phone ) : ?>
          <div class="contact-eyebrow">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015 12.84 19.79 19.79 0 011.95 4.18 2 2 0 013.93 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
            <?php echo esc_html( ( $city ? $city . ' · ' : '' ) . __( 'Real people answer', 'storecmsmart' ) ); ?>
          </div>
          <?php endif; ?>

          <h1 class="contact-h1"><?php echo wp_kses_post( $hero_title ); ?></h1>
          <p class="contact-lead"><?php echo wp_kses_post( $hero_desc ); ?></p>

          <div class="contact-hero-actions">
            <?php if ( $phone_href ) : ?>
            <a href="tel:<?php echo esc_attr( $phone_href ); ?>" class="cta-primary">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015 12.84 19.79 19.79 0 011.95 4.18 2 2 0 013.93 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
              <?php echo esc_html( $phone ? __( 'Call', 'storecmsmart' ) . ' ' . $phone : __( 'Call us', 'storecmsmart' ) ); ?>
            </a>
            <?php endif; ?>
            <a href="#contact-form" class="cta-ghost-light"><?php esc_html_e( 'Send a message', 'storecmsmart' ); ?></a>
          </div>
        </div>

        <!-- Right info cards (hidden on mobile) -->
        <div class="contact-hero-cards">
          <?php if ( $address ) : ?>
          <div class="cahc">
            <div class="cahc-label"><?php esc_html_e( 'Find us', 'storecmsmart' ); ?></div>
            <div class="cahc-row">
              <div class="cahc-icon">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div><strong><?php echo esc_html( $address ); ?></strong><?php if ( $city ) echo '<small>' . esc_html( $city ) . '</small>'; ?></div>
            </div>
          </div>
          <?php endif; ?>
          <?php if ( $hours ) : ?>
          <div class="cahc">
            <div class="cahc-label"><?php esc_html_e( 'Business hours', 'storecmsmart' ); ?></div>
            <div class="cahc-row">
              <div class="cahc-icon">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              </div>
              <div><strong><?php echo esc_html( $hours ); ?></strong></div>
            </div>
          </div>
          <?php endif; ?>
          <?php if ( $email ) : ?>
          <div class="cahc">
            <div class="cahc-label"><?php esc_html_e( 'Email us', 'storecmsmart' ); ?></div>
            <div class="cahc-row">
              <div class="cahc-icon">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
              </div>
              <div><strong><?php echo esc_html( $email ); ?></strong><small><?php esc_html_e( 'Replies same business day', 'storecmsmart' ); ?></small></div>
            </div>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════
       QUICK INFO CARDS
  ════════════════════════════════════════════════ -->
  <?php if ( $phone || $email || $address || $hours ) : ?>
  <section class="contact-info-section">
    <div class="container">
      <div class="contact-cards">
        <?php if ( $phone ) : ?>
        <div class="ccard">
          <div class="ccard-icon ccard-icon--red">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015 12.84 19.79 19.79 0 011.95 4.18 2 2 0 013.93 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
          </div>
          <div class="ccard-label"><?php esc_html_e( 'Phone', 'storecmsmart' ); ?></div>
          <div class="ccard-value"><a href="tel:<?php echo esc_attr( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></div>
          <?php if ( $hours ) : ?><div class="ccard-sub"><?php echo esc_html( $hours ); ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ( $email ) : ?>
        <div class="ccard">
          <div class="ccard-icon ccard-icon--yellow">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
          </div>
          <div class="ccard-label"><?php esc_html_e( 'Email', 'storecmsmart' ); ?></div>
          <div class="ccard-value"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div>
          <div class="ccard-sub"><?php esc_html_e( 'Same-business-day replies', 'storecmsmart' ); ?></div>
        </div>
        <?php endif; ?>
        <?php if ( $address ) : ?>
        <div class="ccard">
          <div class="ccard-icon ccard-icon--ink">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div class="ccard-label"><?php esc_html_e( 'Address', 'storecmsmart' ); ?></div>
          <div class="ccard-value"><?php echo esc_html( $address ); ?></div>
          <?php if ( $city ) : ?><div class="ccard-sub"><?php echo esc_html( $city ); ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ( $hours ) : ?>
        <div class="ccard">
          <div class="ccard-icon ccard-icon--green">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="ccard-label"><?php esc_html_e( 'Hours', 'storecmsmart' ); ?></div>
          <div class="ccard-value"><?php echo esc_html( $hours ); ?></div>
          <div class="ccard-sub"><?php esc_html_e( '24/7 online ordering', 'storecmsmart' ); ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ════════════════════════════════════════════════
       FORM  +  SIDEBAR
  ════════════════════════════════════════════════ -->
  <?php $_has_sidebar = ( $phone || $email || $address ) || $hours || $soc_ig || $soc_fb || $soc_yt; ?>
  <section class="contact-form-section">
    <div class="container">
      <div class="contact-grid<?php echo $_has_sidebar ? '' : ' contact-grid--wide'; ?>" id="contact-form">

        <!-- Form panel -->
        <div class="contact-form-wrap">
          <h2 class="contact-form-title"><?php esc_html_e( 'Send us a message', 'storecmsmart' ); ?></h2>
          <p class="contact-form-sub"><?php esc_html_e( "Tell us about your project — we'll get back to you the same business day.", 'storecmsmart' ); ?></p>

          <?php if ( $form_sc ) : ?>
            <?php echo do_shortcode( wp_kses_post( $form_sc ) ); ?>
          <?php else : ?>
            <?php
            while ( have_posts() ) : the_post();
              the_content();
            endwhile;
            ?>
          <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside class="contact-sidebar">

          <?php if ( $phone || $email || $address ) : ?>
          <div class="contact-sb-block">
            <div class="contact-sb-title"><?php esc_html_e( 'Contact info', 'storecmsmart' ); ?></div>
            <?php if ( $phone ) : ?>
            <div class="contact-sb-row">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--nbd-mb-primary)" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 015 12.84 19.79 19.79 0 011.95 4.18 2 2 0 013.93 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
              <div>
                <a href="tel:<?php echo esc_attr( $phone_href ); ?>" class="contact-sb-link"><?php echo esc_html( $phone ); ?></a>
                <?php if ( $hours ) : ?><span class="contact-sb-sub"><?php echo esc_html( $hours ); ?></span><?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php if ( $email ) : ?>
            <div class="contact-sb-row">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--nbd-mb-primary)" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
              <div>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="contact-sb-link"><?php echo esc_html( $email ); ?></a>
                <span class="contact-sb-sub"><?php esc_html_e( 'Same-day replies on business days', 'storecmsmart' ); ?></span>
              </div>
            </div>
            <?php endif; ?>
            <?php if ( $address ) : ?>
            <div class="contact-sb-row">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--nbd-mb-primary)" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <div>
                <strong class="contact-sb-strong"><?php echo esc_html( $address ); ?></strong>
                <?php if ( $city ) : ?><span class="contact-sb-sub"><?php echo esc_html( $city ); ?></span><?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
            <div class="contact-resp-badge">
              <span class="contact-resp-dot"></span>
              <span><?php esc_html_e( 'Typically replies within 2 hours', 'storecmsmart' ); ?></span>
            </div>
          </div>
          <?php endif; ?>

          <?php if ( $hours ) : ?>
          <div class="contact-sb-block">
            <div class="contact-sb-title"><?php esc_html_e( 'Business hours', 'storecmsmart' ); ?></div>
            <div class="contact-sb-row">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--nbd-mb-primary)" stroke-width="2" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              <div>
                <strong class="contact-sb-strong"><?php echo esc_html( $hours ); ?></strong>
                <span class="contact-sb-sub"><?php esc_html_e( '24/7 online ordering available', 'storecmsmart' ); ?></span>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ( $soc_ig || $soc_fb || $soc_yt ) : ?>
          <div class="contact-sb-block">
            <div class="contact-sb-title"><?php esc_html_e( 'Follow us', 'storecmsmart' ); ?></div>
            <p class="contact-sb-follow-desc"><?php esc_html_e( 'See recent work and behind-the-scenes from the shop.', 'storecmsmart' ); ?></p>
            <div class="contact-social-btns">
              <?php if ( $soc_fb ) : ?>
              <a class="contact-social-btn" href="<?php echo esc_url( $soc_fb ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
              </a>
              <?php endif; ?>
              <?php if ( $soc_ig ) : ?>
              <a class="contact-social-btn" href="<?php echo esc_url( $soc_ig ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor" stroke="none"/></svg>
              </a>
              <?php endif; ?>
              <?php if ( $soc_yt ) : ?>
              <a class="contact-social-btn" href="<?php echo esc_url( $soc_yt ); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M22.54 6.42a2.78 2.78 0 00-1.95-1.97C18.88 4 12 4 12 4s-6.88 0-8.59.45A2.78 2.78 0 001.46 6.42 29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.4a2.78 2.78 0 001.95-1.97A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon style="fill:var(--nbd-mb-bg)" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

        </aside>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════
       MAP  (only shown when map src is configured)
  ════════════════════════════════════════════════ -->
  <?php if ( $map_src ) : ?>
  <section class="contact-map-section">
    <div class="container">
      <div class="contact-map-header">
        <div>
          <div class="contact-map-eyebrow"><?php esc_html_e( 'Find us', 'storecmsmart' ); ?></div>
          <h2 class="contact-map-title"><?php esc_html_e( 'Visit the shop', 'storecmsmart' ); ?></h2>
          <?php if ( $address ) : ?><div class="contact-map-sub"><?php echo esc_html( $address ); ?></div><?php endif; ?>
        </div>
        <?php if ( $address ) : ?>
        <a href="<?php echo esc_url( 'https://maps.google.com/?q=' . rawurlencode( $address ) ); ?>" target="_blank" rel="noopener noreferrer" class="cta-primary" style="flex-shrink:0">
          <?php esc_html_e( 'Get directions', 'storecmsmart' ); ?>
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
        <?php endif; ?>
      </div>
      <div class="contact-map-wrap">
        <iframe src="<?php echo esc_url( $map_src ); ?>"
          allowfullscreen loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="<?php esc_attr_e( 'Location on Google Maps', 'storecmsmart' ); ?>"
          style="border:0;width:100%;height:100%;display:block;"></iframe>
        <?php if ( $address ) : ?>
        <div class="contact-map-badge">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="var(--nbd-mb-highlight)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <?php echo esc_html( $address ); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>
