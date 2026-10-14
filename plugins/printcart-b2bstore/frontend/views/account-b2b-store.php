<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$tier_def = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => $tier, 'discount' => 0 );
$style    = PCS_DB::get_tier_style( $tier );
$user_id  = get_current_user_id();
$orders_90d = wc_get_orders( array(
    'customer'   => $user_id,
    'date_after' => date( 'Y-m-d', strtotime( '-90 days' ) ),
    'limit'      => -1,
    'return'     => 'ids',
) );
$spend_90d = 0;
foreach ( $orders_90d as $oid ) {
    $o = wc_get_order( $oid );
    if ( $o ) $spend_90d += $o->get_total();
}
?>
<div class="pcs-account">

  <!-- Header -->
  <div class="pcs-page-header">
    <div class="pcs-page-header-inner">
      <div class="pcs-page-header-top">
        <div class="pcs-page-header-logo"><?php echo esc_html( PCS_Customers::get_initials( $store->company_name ) ); ?></div>
        <div class="pcs-page-header-info">
          <div class="pcs-page-header-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> · B2B Store</div>
          <h1 class="pcs-page-header-title"><?php echo esc_html( $store->company_name ); ?></h1>
          <p class="pcs-page-header-sub"><?php echo esc_html( $store->industry ?: 'Corporate account' ); ?> · <?php echo esc_html( ucfirst( $store->status ) ); ?></p>
        </div>
        <div class="pcs-page-header-actions">
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-team' ) ); ?>" class="pcs-btn">Team</a>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-btn pcs-btn-primary">Shop now →</a>
        </div>
      </div>
      <div class="pcs-page-header-stats">
        <div>
          <div class="pcs-page-header-stat-value"><?php echo count( $members ) + 1; ?></div>
          <div class="pcs-page-header-stat-label">Team members</div>
        </div>
        <div>
          <div class="pcs-page-header-stat-value"><?php echo wp_kses_post( wc_price( $spend_90d ) ); ?></div>
          <div class="pcs-page-header-stat-label">Spend (90d)</div>
        </div>
        <div>
          <div class="pcs-page-header-stat-value"><?php echo (int) $tier_def['discount']; ?>%</div>
          <div class="pcs-page-header-stat-label">Your discount</div>
        </div>
        <div>
          <div class="pcs-page-header-stat-value" style="background:<?php echo esc_attr( $style['bg'] ); ?>;color:<?php echo esc_attr( $style['color'] ); ?>;padding:3px 10px;border-radius:9999px;font-size:14px;">
            <?php echo esc_html( $tier_def['label'] ); ?>
          </div>
          <div class="pcs-page-header-stat-label">Tier</div>
        </div>
      </div>
    </div>
  </div>

  <div class="pcs-layout-2col">
    <div>

      <!-- Store info -->
      <div class="pcs-card">
        <div class="pcs-card-head brand">
          <h3 class="pcs-card-title">🏢 Store profile</h3>
        </div>
        <div class="pcs-card-body">
          <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
            <?php if ( $store->contact_email ) : ?>
            <div style="display:flex;gap:10px;">
              <span style="color:var(--pfe-text-soft);min-width:130px;">Email</span>
              <span><?php echo esc_html( $store->contact_email ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $store->contact_phone ) : ?>
            <div style="display:flex;gap:10px;">
              <span style="color:var(--pfe-text-soft);min-width:130px;">Phone</span>
              <span><?php echo esc_html( $store->contact_phone ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $store->address ) : ?>
            <div style="display:flex;gap:10px;">
              <span style="color:var(--pfe-text-soft);min-width:130px;">Address</span>
              <span><?php echo esc_html( $store->address ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $store->payment_terms ) : ?>
            <div style="display:flex;gap:10px;">
              <span style="color:var(--pfe-text-soft);min-width:130px;">Payment terms</span>
              <span><?php echo esc_html( ucwords( str_replace( 'net', 'Net-', $store->payment_terms ) ) ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $store->credit_limit > 0 ) : ?>
            <div style="display:flex;gap:10px;">
              <span style="color:var(--pfe-text-soft);min-width:130px;">Credit limit</span>
              <span><?php echo wp_kses_post( wc_price( $store->credit_limit ) ); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Team preview -->
      <?php if ( ! empty( $members ) ) : ?>
      <div class="pcs-card">
        <div class="pcs-card-head">
          <h3 class="pcs-card-title">👥 Team members (<?php echo count( $members ) + 1; ?>)</h3>
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-team' ) ); ?>" class="pcs-card-action">Manage →</a>
        </div>
        <div class="pcs-card-body flush">
          <?php $current_user = wp_get_current_user(); ?>
          <div class="pcs-member-row">
            <div class="pcs-member-avatar"><?php echo esc_html( PCS_Customers::get_initials( $current_user->display_name ) ); ?></div>
            <div>
              <div class="pcs-member-name"><?php echo esc_html( $current_user->display_name ); ?> <span style="font-size:10px;background:var(--pfe-primary-s);color:var(--pfe-primary);padding:1px 6px;border-radius:4px;font-weight:600;">YOU</span></div>
              <div class="pcs-member-email"><?php echo esc_html( $current_user->user_email ); ?></div>
            </div>
            <span class="pcs-role-badge admin">Admin</span>
            <span style="font-size:11px;color:var(--pfe-text-soft);">Account owner</span>
          </div>
          <?php foreach ( array_slice( $members, 0, 3 ) as $m ) :
            $mu = get_userdata( $m->user_id );
            if ( ! $mu ) continue;
            $initials = PCS_Customers::get_initials( $mu->display_name );
          ?>
          <div class="pcs-member-row">
            <div class="pcs-member-avatar"><?php echo esc_html( $initials ); ?></div>
            <div>
              <div class="pcs-member-name"><?php echo esc_html( $mu->display_name ); ?></div>
              <div class="pcs-member-email"><?php echo esc_html( $mu->user_email ); ?></div>
            </div>
            <span class="pcs-role-badge <?php echo esc_attr( $m->role ); ?>"><?php echo esc_html( ucfirst( $m->role ) ); ?></span>
            <span></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div>
      <div class="pcs-card">
        <div class="pcs-card-head <?php echo PCS_DB::store_is_approved( $store->status ) ? 'success' : 'warning'; ?>">
          <h3 class="pcs-card-title">Account status</h3>
        </div>
        <div class="pcs-card-body" style="text-align:center;padding:20px;">
          <?php if ( PCS_DB::store_is_approved( $store->status ) ) : ?>
          <div style="width:64px;height:64px;border-radius:50%;background:var(--pfe-success-s);display:grid;place-items:center;margin:0 auto 12px;font-size:28px;">✅</div>
          <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Active</div>
          <div style="font-size:12px;color:var(--pfe-text-soft);">Your B2B account is in good standing.</div>
          <?php elseif ( 'pending' === $store->status ) : ?>
          <div style="width:64px;height:64px;border-radius:50%;background:var(--pfe-warning-s);display:grid;place-items:center;margin:0 auto 12px;font-size:28px;">⏳</div>
          <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Pending review</div>
          <div style="font-size:12px;color:var(--pfe-text-soft);">We'll respond within 24 hours.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="pcs-card">
        <div class="pcs-card-head"><h3 class="pcs-card-title">Tier benefits</h3></div>
        <div class="pcs-card-body">
          <ul class="pcs-benefits">
            <li><?php echo (int) $tier_def['discount']; ?>% off all products</li>
            <?php if ( ! empty( $store->payment_terms ) ) : ?>
            <li><?php echo esc_html( ucwords( str_replace( 'net', 'Net-', $store->payment_terms ) ) ); ?> payment terms</li>
            <?php endif; ?>
            <?php if ( $store->credit_limit > 0 ) : ?>
            <li><?php echo wp_kses_post( wc_price( $store->credit_limit ) ); ?> credit limit</li>
            <?php endif; ?>
            <li>Team workspace</li>
            <li>Priority support</li>
          </ul>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-btn pcs-btn-primary" style="width:100%;justify-content:center;margin-top:14px;">
            Shop with <?php echo (int) $tier_def['discount']; ?>% off →
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
