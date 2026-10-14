<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var int $user_id @var string $tier @var bool $is_b2b @var object|null $store @var array $tiers @var string $current @var WP_User $user */
$user          = wp_get_current_user();
$tier_def      = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => 'B2C', 'discount' => 0 );
$tier_style    = PCS_DB::get_tier_style( $tier );
$initials      = PCS_Customers::get_initials( $user->display_name );
$portal_base   = PCS_B2B_Portal::url( '' );
?>
<div class="pcs-portal-wrap">

  <!-- ===== SIDEBAR ===== -->
  <aside class="pcs-portal-sidebar">

    <!-- User avatar + tier -->
    <div class="pcs-portal-user">
      <div class="pcs-portal-avatar"><?php echo esc_html( $initials ); ?></div>
      <div class="pcs-portal-user-info">
        <div class="pcs-portal-user-name"><?php echo esc_html( $user->display_name ); ?></div>
        <div class="pcs-portal-user-email"><?php echo esc_html( $user->user_email ); ?></div>
        <div class="pcs-portal-user-tier" style="background:<?php echo esc_attr( $tier_style['bg'] ); ?>;color:<?php echo esc_attr( $tier_style['color'] ); ?>;">
          <?php echo esc_html( $tier_def['label'] ); ?>
          <?php if ( $tier_def['discount'] > 0 ) : ?>
            · <?php echo (int) $tier_def['discount']; ?>% off
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="pcs-portal-nav">

      <?php if ( $is_b2b ) : ?>
      <div class="pcs-portal-nav-section-label">B2B Dashboard</div>
      <?php
      foreach ( PCS_B2B_Portal::SECTIONS as $key => $meta ) :
        if ( $meta['b2b_only'] && ! $is_b2b ) continue;
        $is_active = $current === $key;
      ?>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( $key ) ); ?>"
         class="pcs-portal-nav-item <?php echo $is_active ? 'active' : ''; ?>">
        <span class="pcs-portal-nav-icon"><?php echo $meta['icon']; ?></span>
        <span><?php echo esc_html( $meta['label'] ); ?></span>
        <?php if ( $key === 'approvals' ) : ?>
          <span class="pcs-portal-nav-badge" style="display:none;" id="portal-approval-badge">0</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>

      <div class="pcs-portal-nav-divider"></div>
      <?php endif; ?>

      <!-- Custom Quotes group — shown for all logged-in users -->
      <div class="pcs-portal-nav-section-label">Custom Quotes</div>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'quote-request' ) ); ?>"
         class="pcs-portal-nav-item <?php echo in_array( $current, array( 'quote-request' ), true ) ? 'active' : ''; ?>">
        <span class="pcs-portal-nav-icon">💬</span><span>Request quote</span>
      </a>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'quotes' ) ); ?>"
         class="pcs-portal-nav-item <?php echo in_array( $current, array( 'quotes' ), true ) ? 'active' : ''; ?>">
        <span class="pcs-portal-nav-icon">📋</span><span>My quotes</span>
        <?php
        $q_count = PCS_Quotes::count( array( 'user_id' => $user_id ) );
        if ( $q_count > 0 ) : ?>
          <span class="pcs-portal-nav-badge" style="background:#2563eb;color:#fff;font-size:10px;padding:1px 6px;border-radius:9999px;font-weight:700;margin-left:auto;"><?php echo (int) $q_count; ?></span>
        <?php endif; ?>
      </a>

      <div class="pcs-portal-nav-divider"></div>

      <div class="pcs-portal-nav-section-label">My Account</div>
      <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="pcs-portal-nav-item">
        <span class="pcs-portal-nav-icon">🏠</span><span>Dashboard</span>
      </a>
      <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="pcs-portal-nav-item">
        <span class="pcs-portal-nav-icon">📦</span><span>Orders</span>
      </a>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'profile' ) ); ?>"
         class="pcs-portal-nav-item <?php echo $current === 'profile' ? 'active' : ''; ?>">
        <span class="pcs-portal-nav-icon">👤</span><span>My Profile</span>
      </a>
      <a href="<?php echo esc_url( add_query_arg( 'pcs_noredirect', '1', wc_get_page_permalink( 'myaccount' ) ) ); ?>"
         class="pcs-portal-nav-item" style="font-size:12px;color:#9ca3af !important;"
         title="Downloads, designs, addresses and more">
        <span class="pcs-portal-nav-icon" style="font-size:12px;">↗</span>
        <span>More account features</span>
      </a>
      <?php if ( ! $is_b2b && PCS_Company_Images::user_store_ids( $user_id ) ) : ?>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'images' ) ); ?>"
         class="pcs-portal-nav-item <?php echo $current === 'images' ? 'active' : ''; ?>">
        <span class="pcs-portal-nav-icon">🖼️</span><span>Image Zone</span>
      </a>
      <?php endif; ?>
      <?php if ( ! $is_b2b && get_option( 'pcs_b2b_self_register', '1' ) !== '0' ) : ?>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'apply' ) ); ?>"
         class="pcs-portal-nav-item <?php echo $current === 'apply' ? 'active' : ''; ?>" style="color:#f59e0b;font-weight:600;">
        <span class="pcs-portal-nav-icon">⭐</span><span>Apply for B2B</span>
      </a>
      <?php endif; ?>

      <div class="pcs-portal-nav-divider"></div>
      <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="pcs-portal-nav-item pcs-portal-nav-logout">
        <span class="pcs-portal-nav-icon">↩</span><span>Log out</span>
      </a>

    </nav>

    <!-- Shop CTA -->
    <div class="pcs-portal-sidebar-cta">
      <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-portal-shop-btn">
        🛒 Shop now
        <?php if ( $tier_def['discount'] > 0 ) : ?>
          <span><?php echo (int) $tier_def['discount']; ?>% off</span>
        <?php endif; ?>
      </a>
    </div>

  </aside>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="pcs-portal-main">
    <?php
    /* Suppress IDE undefined-variable warnings — vars come from extract() in render_portal() */
    /** @var int    $user_id */
    /** @var string $tier */
    /** @var array  $tier_def */
    /** @var array  $tiers */
    /** @var bool   $is_b2b */
    /** @var object|null $store */
    /** @var string $current */
    /** @var WP_User $user */

    /* Allow any section file, including order-detail */
    $allowed = array( 'overview', 'orders', 'order-detail', 'quotes', 'quote-request', 'quote-view', 'store', 'team', 'approvals', 'images', 'templates', 'apply', 'profile' );
    $safe_current = in_array( $current, $allowed, true ) ? $current : 'overview';
    $section_file = PCS_PLUGIN_DIR . 'frontend/portal/sections/' . $safe_current . '.php';

    if ( file_exists( $section_file ) ) {
        include $section_file;
    } elseif ( $safe_current === 'apply' ) {
        include PCS_PLUGIN_DIR . 'frontend/views/account-apply-b2b.php';
    } else {
        include PCS_PLUGIN_DIR . 'frontend/portal/sections/overview.php';
    }
    ?>
  </main>

</div><!-- .pcs-portal-wrap -->
