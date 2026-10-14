<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Portal section: Apply for B2B (or show pending/active status)
 * Uses portal CSS classes (pcs-ph, pcs-pcard, etc.)
 */

/* Variables from portal.php: $user, $user_id, $tier, $is_b2b, $store, $tiers */
$store_status = $store ? $store->status : '';
$user_obj     = $user instanceof WP_User ? $user : wp_get_current_user();
$total_spent  = (float) wc_get_customer_total_spent( $user_id );

/* ---- Already B2B ---- */
if ( $is_b2b ) : ?>
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">⭐</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow">B2B Status</div>
        <h1 class="pcs-ph-title">You're a B2B member!</h1>
        <p class="pcs-ph-sub">Your B2B account is active. Enjoy your tier benefits on every order.</p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'store' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary">View B2B Store →</a>
      </div>
    </div>
  </div>
</div>
<div style="padding:20px 24px;">
  <?php $tier_def = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => $tier, 'discount' => 0 ); ?>
  <div class="pcs-pcard" style="max-width:500px;">
    <div class="pcs-pcard-head pcs-pcard-head-success">
      <h3 class="pcs-pcard-title">✅ Account active</h3>
    </div>
    <div class="pcs-pcard-body" style="text-align:center;padding:28px 20px;">
      <?php echo PCS_DB::tier_pill_html( $tier, $tier_def['label'] ); ?>
      <div style="font-size:13px;color:#6b7280;margin-top:8px;"><?php echo (int) $tier_def['discount']; ?>% off all products</div>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'store' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary" style="margin-top:16px;display:inline-flex;">My B2B Store →</a>
    </div>
  </div>
</div>

<?php /* ---- Pending application ---- */
elseif ( $store && $store_status === 'pending' ) : ?>
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">⏳</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow">B2B Application</div>
        <h1 class="pcs-ph-title">Application under review</h1>
        <p class="pcs-ph-sub">We typically respond within 24 business hours.</p>
      </div>
    </div>
  </div>
</div>
<div style="padding:20px 24px;">
  <div class="pcs-pcard">
    <div class="pcs-pcard-head" style="background:linear-gradient(135deg,#fef3c7,#fffbeb);">
      <h3 class="pcs-pcard-title">⏳ Application in progress</h3>
    </div>
    <div class="pcs-pcard-body">
      <div style="display:flex;gap:14px;align-items:flex-start;padding:8px 0 16px;">
        <div style="font-size:32px;">⏳</div>
        <div>
          <div style="font-weight:700;font-size:15px;margin-bottom:4px;">
            <?php echo esc_html( $store->company_name ); ?>
          </div>
          <div style="font-size:13px;color:#6b7280;line-height:1.5;">
            Submitted <?php echo esc_html( human_time_diff( strtotime( $store->created_at ), time() ) . ' ago' ); ?>.
            We'll email you at <strong><?php echo esc_html( $user_obj->user_email ); ?></strong> once a decision is made.
          </div>
        </div>
      </div>
      <!-- Progress steps -->
      <div style="display:flex;flex-direction:column;gap:12px;padding:14px;background:#f9fafb;border-radius:8px;">
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:26px;height:26px;border-radius:50%;background:#dcfce7;color:#16a34a;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;">✓</div>
          <div style="font-size:13px;"><strong>Application submitted</strong><br><span style="color:#6b7280;">Your details are with our team</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:26px;height:26px;border-radius:50%;background:#dbeafe;color:#2563eb;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;box-shadow:0 0 0 3px rgba(37,99,235,.15);">2</div>
          <div style="font-size:13px;"><strong style="color:#2563eb;">Under review (you are here)</strong><br><span style="color:#6b7280;">We verify your business (within 24h)</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start;opacity:.5;">
          <div style="width:26px;height:26px;border-radius:50%;background:#e5e7eb;color:#9ca3af;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;">3</div>
          <div style="font-size:13px;"><strong>Approved</strong><br><span style="color:#6b7280;">Tier &amp; discount activated</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php /* ---- Show application form ---- */
else :
  /* Rejected — show notice */
  if ( $store && $store_status === 'rejected' ) : ?>
  <div style="padding:20px 24px 0;">
    <div style="background:#fef3c7;border-left:4px solid #d97706;padding:12px 16px;border-radius:0 8px 8px 0;font-size:13px;color:#92400e;margin-bottom:0;">
      ⚠️ Your previous application was not approved. You can submit a new one below.
    </div>
  </div>
<?php endif; ?>

<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">📋</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow">Business Account</div>
        <h1 class="pcs-ph-title">Apply for B2B membership</h1>
        <p class="pcs-ph-sub">Unlock volume discounts, Net payment terms, team workspaces, and brand store features. Takes 2 minutes · Reviewed within 24 hours.</p>
      </div>
    </div>
  </div>
</div>

<div style="padding:20px 24px;">

  <!-- Tier benefits grid -->
  <div class="pcs-pcard" style="margin-bottom:20px;">
    <div class="pcs-pcard-head" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
      <div style="display:flex;align-items:center;gap:10px;">
        <h3 class="pcs-pcard-title">🏆 B2B tier benefits</h3>
        <span style="padding:3px 10px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:9999px;font-size:11px;font-weight:600;">ℹ️ For reference only</span>
      </div>
      <span style="font-size:12px;color:#6b7280;">Admin assigns your tier after approval</span>
    </div>
    <div class="pcs-pcard-body">
      <p style="font-size:13px;color:#6b7280;margin:0 0 14px;">Once approved, our team assigns the right tier based on your business volume:</p>
      <div class="pcs-tier-display-grid">
        <?php
        /* Show all tiers with a discount — automatically includes any custom tiers */
        $b2b_only = array_filter( $tiers, function( $tdef ) {
            return isset( $tdef['discount'] ) && (float) $tdef['discount'] > 0;
        } );
        foreach ( $b2b_only as $tid => $tdef ) :
          $s = PCS_DB::get_tier_style( $tid );
          $pt_map = array( 'prepay' => 'Pre-pay', 'net15' => 'Net-15', 'net30' => 'Net-30' );
          $pt = isset( $pt_map[ $tdef['payment_terms'] ] ) ? $pt_map[ $tdef['payment_terms'] ] : ( $tdef['payment_terms'] ?? '' );
          $disc = (int) $tdef['discount'];
        ?>
        <div class="pcs-tier-display-card" style="--tier-bg:<?php echo esc_attr( $s['bg'] ); ?>;--tier-color:<?php echo esc_attr( $s['color'] ); ?>;">
          <div class="pcs-tier-display-label"><?php echo esc_html( $tdef['label'] ); ?></div>
          <div class="pcs-tier-display-discount"><?php echo $disc; ?></div>
          <ul class="pcs-tier-display-perks">
            <li>off all products</li>
            <?php if ( $pt ) : ?><li><?php echo esc_html( $pt ); ?> terms</li><?php endif; ?>
            <?php if ( $disc >= 20 ) : ?><li>Free shipping always</li><?php elseif ( $disc >= 10 ) : ?><li>Free shipping over $150</li><?php endif; ?>
            <?php if ( $disc >= 20 ) : ?><li>Team workspace</li><?php endif; ?>
          </ul>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Application form -->
  <div class="pcs-pcard">
    <div class="pcs-pcard-head pcs-pcard-head-brand">
      <h3 class="pcs-pcard-title">📋 Your application</h3>
    </div>
    <div class="pcs-pcard-body">
      <?php wc_print_notices(); ?>
      <form method="post" action="">
        <?php wp_nonce_field( 'pcs_apply_b2b', 'pcs_apply_b2b_nonce' ); ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Company name <span style="color:#ef4444;">*</span></label>
            <input type="text" name="company_name" class="pcs-pinput" placeholder="e.g. ACME Corp" required value="<?php echo esc_attr( $_POST['company_name'] ?? '' ); ?>">
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Industry</label>
            <select name="industry" class="pcs-pinput" style="appearance:auto;">
              <option value="">— Select —</option>
              <?php foreach ( array( 'Marketing & Advertising','Real estate','Event management','Retail / Restaurant','Auto dealer','Construction','Healthcare','Education','Other' ) as $ind ) : ?>
              <option value="<?php echo esc_attr( $ind ); ?>" <?php selected( $_POST['industry'] ?? '', $ind ); ?>><?php echo esc_html( $ind ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Contact name <span style="color:#ef4444;">*</span></label>
            <input type="text" name="contact_name" class="pcs-pinput" required value="<?php echo esc_attr( $_POST['contact_name'] ?? $user_obj->display_name ); ?>">
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Business email <span style="color:#ef4444;">*</span></label>
            <input type="email" name="contact_email" class="pcs-pinput" required value="<?php echo esc_attr( $_POST['contact_email'] ?? $user_obj->user_email ); ?>">
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Phone</label>
            <input type="tel" name="contact_phone" class="pcs-pinput" placeholder="(555) 123-4567" value="<?php echo esc_attr( $_POST['contact_phone'] ?? '' ); ?>">
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Est. monthly volume</label>
            <select name="monthly_volume" class="pcs-pinput" style="appearance:auto;">
              <option value="">— Select range —</option>
              <?php foreach ( array( 'Under $500', '$500–$2,000', '$2,000–$5,000', '$5,000–$10,000', '$10,000+' ) as $v ) : ?>
              <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $_POST['monthly_volume'] ?? '', $v ); ?>><?php echo esc_html( $v ); ?>/month</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="pcs-pfield" style="margin-top:6px;">
          <label class="pcs-pfield-label">Tell us about your business
            <span style="font-weight:400;color:#9ca3af;"> — What products do you need?</span>
          </label>
          <textarea name="about" class="pcs-pinput" style="height:90px;resize:vertical;" placeholder="We primarily need banners, yard signs, and event backdrops for…"><?php echo esc_textarea( $_POST['about'] ?? '' ); ?></textarea>
        </div>
        <div style="background:#eff6ff;border-left:3px solid #2563eb;padding:10px 14px;border-radius:0 6px 6px 0;font-size:12px;color:#1e3a8a;margin:14px 0;">
          ℹ️ By submitting, you agree we may contact you to verify your business details. Reviewed within 24 business hours.
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'overview' ) ); ?>" class="pcs-pbtn pcs-pbtn-ghost" style="background:#fff;color:#374151;border:1px solid #e5e7eb;">Cancel</a>
          <button type="submit" class="pcs-pbtn" style="background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;height:42px;padding:0 24px;font-size:14px;">
            Submit B2B application →
          </button>
        </div>
      </form>
    </div>
  </div>

</div><!-- /padding -->
<?php endif; ?>
