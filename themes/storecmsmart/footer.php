<?php $img_base = get_stylesheet_directory_uri() . '/assets/images'; ?>

<!-- ============ FOOTER ============ -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand column -->
      <div>
        <?php if ( has_custom_logo() ) : ?>
          <div class="footer-logo-wrap"><?php the_custom_logo(); ?></div>
        <?php else : ?>
          <img src="<?php echo esc_url( $img_base . '/logo.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="footer-logo">
        <?php endif; ?>
        <div class="footer-tagline">
          <?php echo wp_kses_post( giga_option( 'giga_footer_tagline', get_bloginfo( 'description' ) ) ); ?>
        </div>
        <?php
        $_pw_title = giga_option( 'giga_footer_powered_title', '' );
        $_pw_sub   = giga_option( 'giga_footer_powered_sub',   '' );
        if ( $_pw_title || $_pw_sub ) : ?>
        <div class="footer-powered">
          <?php if ( $_pw_title ) : ?><strong><?php echo esc_html( $_pw_title ); ?></strong><?php if ( $_pw_sub ) echo '<br>'; endif; ?>
          <?php if ( $_pw_sub ) echo esc_html( $_pw_sub ); ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Contact column -->
      <div>
        <h4><?php esc_html_e( 'Contact', 'storecmsmart' ); ?></h4>
        <?php $_phone = giga_option( 'giga_footer_phone', '' ); if ( $_phone ) : ?>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
          <a href="tel:<?php echo esc_attr( giga_option( 'giga_footer_phone_href', preg_replace( '/[^0-9]/', '', $_phone ) ) ); ?>"><?php echo esc_html( $_phone ); ?></a>
        </div>
        <?php endif; ?>
        <?php $_email = giga_option( 'giga_footer_email', '' ); if ( $_email ) : ?>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
          <a href="mailto:<?php echo esc_attr( $_email ); ?>"><?php echo esc_html( $_email ); ?></a>
        </div>
        <?php endif; ?>
        <?php $_address = giga_option( 'giga_footer_address', '' ); if ( $_address ) : ?>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <?php echo esc_html( $_address ); ?>
        </div>
        <?php endif; ?>
        <?php $_hours = giga_option( 'giga_footer_hours', '' ); if ( $_hours ) : ?>
        <div class="footer-contact-row" style="margin-top:14px">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <?php echo esc_html( $_hours ); ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Products column -->
      <div>
        <h4><?php esc_html_e( 'Products', 'storecmsmart' ); ?></h4>
        <?php if ( has_nav_menu( 'footer-products' ) ) : ?>
          <?php wp_nav_menu( [ 'theme_location' => 'footer-products', 'container' => false, 'depth' => 1, 'fallback_cb' => false ] ); ?>
        <?php else :
          // Auto-pull top-level product categories, exclude WooCommerce's default "Uncategorized"
          $_default_cat = function_exists( 'get_option' ) ? (int) get_option( 'default_product_cat', 0 ) : 0;
          $cats = function_exists( 'get_terms' ) ? get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
            'number'     => 8,
            'exclude'    => array_filter( [ $_default_cat ] ),
            'orderby'    => 'name',
            'order'      => 'ASC',
          ] ) : [];
          $has_cats = ! is_wp_error( $cats ) && ! empty( $cats );
        ?>
          <ul>
            <?php if ( $has_cats ) :
              foreach ( $cats as $cat ) : ?>
                <li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a></li>
              <?php endforeach;
            else :
              // Static fallback — shown on fresh installs with no categories yet
              $shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
              $fallback_links = [
                __( 'Banners',        'storecmsmart' ) => $shop_url,
                __( 'Rigid Signs',    'storecmsmart' ) => $shop_url,
                __( 'Adhesive Vinyl', 'storecmsmart' ) => $shop_url,
                __( 'Apparel / DTF',  'storecmsmart' ) => $shop_url,
                __( 'Magnetic Signs', 'storecmsmart' ) => $shop_url,
                __( 'Photo Prints',   'storecmsmart' ) => $shop_url,
                __( 'Promo & Print',  'storecmsmart' ) => $shop_url,
                __( 'Image Zone',     'storecmsmart' ) => $shop_url,
              ];
              foreach ( $fallback_links as $label => $url ) : ?>
                <li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
              <?php endforeach;
            endif; ?>
          </ul>
        <?php endif; ?>
      </div>

      <!-- Account column -->
      <div>
        <h4><?php esc_html_e( 'Account', 'storecmsmart' ); ?></h4>
        <?php if ( has_nav_menu( 'footer-account' ) ) : ?>
          <?php wp_nav_menu( [ 'theme_location' => 'footer-account', 'container' => false, 'depth' => 1, 'fallback_cb' => false ] ); ?>
        <?php else : ?>
          <ul>
            <?php if ( function_exists( 'wc_get_page_id' ) ) : ?>
            <li><a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Sign in', 'storecmsmart' ); ?></a></li>
            <?php endif; ?>
            <?php if ( function_exists( 'wc_get_account_endpoint_url' ) ) : ?>
            <li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'Order history', 'storecmsmart' ); ?></a></li>
            <?php endif; ?>
            <?php if ( function_exists( 'wc_get_page_id' ) ) : ?>
            <?php
            $_iz_url = get_permalink( wc_get_page_id( 'myaccount' ) );
            if ( is_user_logged_in() && class_exists( 'PCS_Company_Images' ) && class_exists( 'PCS_B2B_Portal' ) && PCS_Company_Images::user_store_ids( get_current_user_id() ) ) {
                $_iz_url = PCS_B2B_Portal::url( 'images' );
            }
            ?>
            <li><a href="<?php echo esc_url( $_iz_url ); ?>"><?php esc_html_e( 'Image Zone', 'storecmsmart' ); ?></a></li>
            <?php endif; ?>
            <li><a href="#b2b"><?php esc_html_e( 'Apply for B2B', 'storecmsmart' ); ?></a></li>
            <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'track-order' ) ) ?: '#' ); ?>"><?php esc_html_e( 'Track order', 'storecmsmart' ); ?></a></li>
            <?php $_pb = giga_option( 'giga_parent_brand_name', '' ); if ( $_pb ) : ?>
            <li><a href="<?php echo esc_url( giga_option( 'giga_parent_brand_url', '#' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $_pb ); ?> →</a></li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>

    </div>

    <div class="footer-bottom">
      <div>© <span id="yr"><?php echo esc_html( date( 'Y' ) ); ?></span> <?php $cp_name = giga_option( 'giga_footer_copyright_name', '' ); echo esc_html( $cp_name ?: get_bloginfo( 'name' ) ); ?> · <?php echo esc_html( giga_option( 'giga_footer_copyright', 'A SignEdge Graphics company. All rights reserved.' ) ); ?></div>
      <div>
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'terms' ) ) ?: '#' ); ?>"><?php esc_html_e( 'Terms', 'storecmsmart' ); ?></a> ·
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'privacy-policy' ) ) ?: get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy', 'storecmsmart' ); ?></a> ·
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'refund-policy' ) ) ?: '#' ); ?>"><?php esc_html_e( 'Refund policy', 'storecmsmart' ); ?></a> ·
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'accessibility' ) ) ?: '#' ); ?>"><?php esc_html_e( 'Accessibility', 'storecmsmart' ); ?></a>
      </div>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
