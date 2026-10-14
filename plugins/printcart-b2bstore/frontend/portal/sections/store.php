<?php if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! $store ) { echo '<div class="pcs-ph"><p style="color:#fff;">No B2B store found.</p></div>'; return; }
$all_members  = PCS_DB::get_members( $store->id );
$owner_id     = (int) $store->user_id;
$members      = array_values( array_filter( $all_members, function( $m ) use ( $owner_id ) {
    return (int) $m->user_id !== $owner_id;
} ) );
$tier_def = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => $tier, 'discount' => 0 );
$style    = PCS_DB::get_tier_style( $tier );
$total_spent = (float) wc_get_customer_total_spent( $user_id );
?>

<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo"><?php echo esc_html( PCS_Customers::get_initials( $store->company_name ) ); ?></div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( get_bloginfo('name') ); ?> · B2B Store</div>
        <h1 class="pcs-ph-title"><?php echo esc_html( $store->company_name ); ?></h1>
        <p class="pcs-ph-sub"><?php echo esc_html( $store->industry ?: 'Corporate account' ); ?> · Status: <?php echo esc_html( ucfirst( $store->status ) ); ?></p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'team' ) ); ?>" class="pcs-pbtn pcs-pbtn-ghost">Team</a>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary">Shop now →</a>
      </div>
    </div>
    <div class="pcs-ph-stats">
      <div><div class="pcs-ph-stat-val"><?php echo count( $members ) + 1; ?></div><div class="pcs-ph-stat-lbl">Team members</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></div><div class="pcs-ph-stat-lbl">Total spent</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo (int) $tier_def['discount']; ?>%</div><div class="pcs-ph-stat-lbl">Your discount</div></div>
    </div>
  </div>
</div>

<div class="pcs-pgrid pcs-pgrid-aside">
  <div>
    <!-- Store info card -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head pcs-pcard-head-brand"><h3 class="pcs-pcard-title">🏢 Store profile</h3></div>
      <div class="pcs-pcard-body">
        <div class="pcs-pinfo-list">
          <?php foreach ( array(
            'Company'   => $store->company_name,
            'Industry'  => $store->industry,
            'Email'     => $store->contact_email,
            'Phone'     => $store->contact_phone,
            'Website'   => $store->website,
            'Payment'   => ( function() use ( $store, $tier_def ) {
                $pt_map = array( 'prepay' => 'Prepay', 'net15' => 'Net-15', 'net30' => 'Net-30' );
                $src    = ! empty( $tier_def['payment_terms'] ) ? $tier_def['payment_terms'] : $store->payment_terms;
                return isset( $pt_map[ $src ] ) ? $pt_map[ $src ] : ucfirst( $src );
            } )(),
            'Credit limit' => $store->credit_limit > 0 ? wc_price( $store->credit_limit ) : '',
          ) as $label => $val ) :
            if ( empty( $val ) ) continue;
          ?>
          <div class="pcs-pinfo-row">
            <span class="pcs-pinfo-label"><?php echo esc_html( $label ); ?></span>
            <span class="pcs-pinfo-value"><?php echo wp_kses_post( $val ); ?></span>
          </div>
          <?php endforeach; ?>
          <?php if ( $store->about ) : ?>
          <div class="pcs-pinfo-row" style="align-items:flex-start;">
            <span class="pcs-pinfo-label">About</span>
            <span class="pcs-pinfo-value" style="font-size:13px;line-height:1.5;"><?php echo esc_html( $store->about ); ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Team preview -->
    <?php if ( ! empty( $members ) ) : ?>
    <div class="pcs-pcard">
      <div class="pcs-pcard-head">
        <h3 class="pcs-pcard-title">👥 Team (<?php echo count( $members ) + 1; /* +1 for owner */ ?>)</h3>
        <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'team' ) ); ?>" class="pcs-pcard-action">Manage →</a>
      </div>
      <div class="pcs-pcard-body" style="padding:0;">
        <div class="pcs-pmember-row">
          <?php $cu = wp_get_current_user(); ?>
          <div class="pcs-pmember-avatar"><?php echo esc_html( PCS_Customers::get_initials( $cu->display_name ) ); ?></div>
          <div class="pcs-pmember-info">
            <div class="pcs-pmember-name"><?php echo esc_html( $cu->display_name ); ?> <span class="pcs-you-tag">YOU</span></div>
            <div class="pcs-pmember-email"><?php echo esc_html( $cu->user_email ); ?></div>
          </div>
          <span class="pcs-role-tag admin">Admin</span>
        </div>
        <?php foreach ( array_slice( $members, 0, 4 ) as $m ) :
          $mu = get_userdata( $m->user_id );
          if ( ! $mu ) continue;
        ?>
        <div class="pcs-pmember-row">
          <div class="pcs-pmember-avatar"><?php echo esc_html( PCS_Customers::get_initials( $mu->display_name ) ); ?></div>
          <div class="pcs-pmember-info">
            <div class="pcs-pmember-name"><?php echo esc_html( $mu->display_name ); ?></div>
            <div class="pcs-pmember-email"><?php echo esc_html( $mu->user_email ); ?></div>
          </div>
          <span class="pcs-role-tag <?php echo esc_attr( $m->role ); ?>"><?php echo esc_html( ucfirst( $m->role ) ); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <!-- Status + tier -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head <?php echo 'active' === $store->status ? 'pcs-pcard-head-success' : 'pcs-pcard-head-warning'; ?>">
        <h3 class="pcs-pcard-title">Account status</h3>
      </div>
      <div class="pcs-pcard-body" style="text-align:center;padding:24px 16px;">
        <?php if ( 'active' === $store->status ) : ?>
          <div style="font-size:40px;margin-bottom:8px;">✅</div>
          <div style="font-weight:700;font-size:16px;margin-bottom:4px;">Active</div>
          <div style="font-size:12px;color:#6b7280;">Your B2B account is in good standing.</div>
        <?php elseif ( 'pending' === $store->status ) : ?>
          <div style="font-size:40px;margin-bottom:8px;">⏳</div>
          <div style="font-weight:700;font-size:16px;margin-bottom:4px;">Pending review</div>
          <div style="font-size:12px;color:#6b7280;">We'll respond within 24 hours.</div>
        <?php endif; ?>

        <?php echo PCS_DB::tier_pill_html( $tier, $tier_def['label'] ); ?>
        <div style="font-size:12px;color:#6b7280;margin-top:6px;"><?php echo (int) $tier_def['discount']; ?>% off all orders</div>

        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary" style="width:100%;justify-content:center;margin-top:16px;display:flex;">
          Shop with <?php echo (int) $tier_def['discount']; ?>% off →
        </a>
      </div>
    </div>

    <!-- Benefits -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">🏷️ Tier benefits</h3></div>
      <div class="pcs-pcard-body">
        <ul class="pcs-benefit-list">
          <li><?php echo (int) $tier_def['discount']; ?>% off all products</li>
          <?php
          /* Use tier's payment_terms as primary; fall back to store's */
          $pt_source = ! empty( $tier_def['payment_terms'] ) ? $tier_def['payment_terms'] : $store->payment_terms;
          $pt_map    = array( 'prepay' => 'Prepay', 'net15' => 'Net-15', 'net30' => 'Net-30' );
          $pt_label  = isset( $pt_map[ $pt_source ] ) ? $pt_map[ $pt_source ] : ucfirst( $pt_source );
          if ( $pt_label ) : ?>
          <li><?php echo esc_html( $pt_label ); ?> payment terms</li>
          <?php endif; ?>
          <li>Team workspace</li>
          <li>Priority support</li>
        </ul>
      </div>
    </div>
  </div>
</div>
