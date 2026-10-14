<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="pcs-account">
  <div class="pcs-page-header">
    <div class="pcs-page-header-inner">
      <div class="pcs-page-header-top">
        <div class="pcs-page-header-logo">👥</div>
        <div class="pcs-page-header-info">
          <div class="pcs-page-header-eyebrow"><?php echo esc_html( $store->company_name ); ?></div>
          <h1 class="pcs-page-header-title">Teams</h1>
          <p class="pcs-page-header-sub">Manage team members and their access roles.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="pcs-card">
    <div class="pcs-card-head brand">
      <h3 class="pcs-card-title">Team members (<?php echo count( $members ) + 1; ?>)</h3>
      <span style="font-size:12px;color:var(--pfe-text-soft);">Contact admin to add members</span>
    </div>
    <div class="pcs-card-body flush">
      <?php $cu = wp_get_current_user(); ?>
      <div class="pcs-member-row">
        <div class="pcs-member-avatar"><?php echo esc_html( PCS_Customers::get_initials( $cu->display_name ) ); ?></div>
        <div>
          <div class="pcs-member-name"><?php echo esc_html( $cu->display_name ); ?> <span style="font-size:10px;background:var(--pfe-primary-s);color:var(--pfe-primary);padding:1px 6px;border-radius:4px;">YOU</span></div>
          <div class="pcs-member-email"><?php echo esc_html( $cu->user_email ); ?></div>
        </div>
        <span class="pcs-role-badge admin">Admin</span>
        <span style="font-size:11px;color:var(--pfe-text-soft);">Account owner</span>
      </div>
      <?php foreach ( $members as $m ) :
        $mu = get_userdata( $m->user_id );
        if ( ! $mu ) continue;
      ?>
      <div class="pcs-member-row">
        <div class="pcs-member-avatar"><?php echo esc_html( PCS_Customers::get_initials( $mu->display_name ) ); ?></div>
        <div>
          <div class="pcs-member-name"><?php echo esc_html( $mu->display_name ); ?></div>
          <div class="pcs-member-email"><?php echo esc_html( $mu->user_email ); ?></div>
        </div>
        <span class="pcs-role-badge <?php echo esc_attr( $m->role ); ?>"><?php echo esc_html( ucfirst( $m->role ) ); ?></span>
        <span style="font-size:12px;color:var(--pfe-text-soft);">
          <?php echo $m->spending_limit > 0 ? 'Limit: ' . wp_kses_post( wc_price( $m->spending_limit ) ) : 'No limit'; ?>
        </span>
      </div>
      <?php endforeach; ?>
      <?php if ( empty( $members ) ) : ?>
      <div style="padding:24px;text-align:center;color:var(--pfe-text-soft);">No additional team members yet. Contact your admin to invite members.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
