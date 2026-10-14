<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="pcs-account">
  <div class="pcs-page-header">
    <div class="pcs-page-header-inner">
      <div class="pcs-page-header-top">
        <div class="pcs-page-header-logo">✅</div>
        <div class="pcs-page-header-info">
          <div class="pcs-page-header-eyebrow"><?php echo esc_html( $store->company_name ); ?></div>
          <h1 class="pcs-page-header-title">Approval Queue</h1>
          <p class="pcs-page-header-sub">Review and approve orders submitted by your team members.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="pcs-card">
    <div class="pcs-card-head brand">
      <h3 class="pcs-card-title">Pending approvals</h3>
    </div>
    <div class="pcs-card-body" style="text-align:center;padding:40px;">
      <div style="font-size:32px;margin-bottom:12px;">✅</div>
      <div style="font-size:15px;font-weight:600;margin-bottom:6px;">No pending approvals</div>
      <div style="font-size:13px;color:var(--pfe-text-soft);">Orders from your team will appear here for review.</div>
    </div>
  </div>
</div>
