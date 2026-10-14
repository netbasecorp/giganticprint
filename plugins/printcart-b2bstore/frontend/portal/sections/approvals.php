<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var object|null $store @var int $user_id @var string $tier @var bool $is_b2b */
?>
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">✅</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo $store ? esc_html( $store->company_name ) : ''; ?></div>
        <h1 class="pcs-ph-title">Approval Queue</h1>
        <p class="pcs-ph-sub">Review and approve orders submitted by your team members.</p>
      </div>
    </div>
  </div>
</div>
<div class="pcs-pcard">
  <div class="pcs-pcard-head pcs-pcard-head-brand"><h3 class="pcs-pcard-title">Pending approvals</h3></div>
  <div class="pcs-pcard-body" style="text-align:center;padding:48px 20px;">
    <div style="font-size:40px;margin-bottom:12px;">✅</div>
    <div style="font-size:15px;font-weight:600;margin-bottom:6px;">No pending approvals</div>
    <div style="font-size:13px;color:#6b7280;">Orders from your team will appear here for review.</div>
    <div style="margin-top:12px;">
      <span style="font-size:12px;background:#f3f4f6;color:#9ca3af;padding:3px 12px;border-radius:9999px;letter-spacing:.3px;">🚧 Coming soon</span>
    </div>
  </div>
</div>
