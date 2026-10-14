<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$user_id = $user->ID;
/* Check if they have a pending/active application */
$has_applied = ! empty( $store );
$status      = $has_applied ? $store->status : '';
?>
<div class="pcs-account">

  <?php if ( $is_b2b ) : /* Already B2B */ ?>
  <div class="pcs-page-header">
    <div class="pcs-page-header-inner">
      <div class="pcs-page-header-top">
        <div class="pcs-page-header-logo">★</div>
        <div class="pcs-page-header-info">
          <div class="pcs-page-header-eyebrow">B2B Status</div>
          <h1 class="pcs-page-header-title">You're a B2B member!</h1>
          <p class="pcs-page-header-sub">Your B2B account is active. Enjoy your tier benefits.</p>
        </div>
        <div class="pcs-page-header-actions">
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-b2b-store' ) ); ?>" class="pcs-btn pcs-btn-primary">View B2B Store →</a>
        </div>
      </div>
    </div>
  </div>
  <?php
  $tier_def_cur = isset( $tiers[ $tier ] ) ? $tiers[ $tier ] : array( 'label' => $tier, 'discount' => 0 );
  $style_cur    = PCS_DB::get_tier_style( $tier );
  echo PCS_DB::tier_pill_html( $tier );
  echo '<p style="margin-top:12px;font-size:13px;color:var(--pfe-text-soft);">Discount: <strong>' . esc_html( $tier_def_cur['discount'] ) . '%</strong> on all orders.</p>';
  ?>

  <?php elseif ( $has_applied && $status === 'pending' ) : ?>
  <!-- PENDING state -->
  <div class="pcs-page-header">
    <div class="pcs-page-header-inner">
      <div class="pcs-page-header-top">
        <div class="pcs-page-header-logo">⏳</div>
        <div class="pcs-page-header-info">
          <div class="pcs-page-header-eyebrow">B2B Application</div>
          <h1 class="pcs-page-header-title">Application under review</h1>
          <p class="pcs-page-header-sub">We typically respond within 24 business hours. Check back soon!</p>
        </div>
      </div>
    </div>
  </div>

  <div class="pcs-pending-card">
    <div class="pcs-pending-icon">⏳</div>
    <div>
      <div class="pcs-pending-title">Your application is being reviewed</div>
      <div class="pcs-pending-desc">
        <strong><?php echo esc_html( $store->company_name ); ?></strong> — submitted
        <?php echo esc_html( human_time_diff( strtotime( $store->created_at ), time() ) . ' ago' ); ?>.
        <br>We'll email you at <strong><?php echo esc_html( $user->user_email ); ?></strong> once a decision is made.
      </div>
    </div>
  </div>

  <div class="pcs-card">
    <div class="pcs-card-head"><h3 class="pcs-card-title">What happens next?</h3></div>
    <div class="pcs-card-body">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:28px;height:28px;border-radius:50%;background:#dcfce7;color:#16a34a;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;">✓</div>
          <div style="font-size:13px;"><strong>Application submitted</strong><br><span style="color:var(--pfe-text-soft);">Your details are with our team</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:28px;height:28px;border-radius:50%;background:var(--pfe-primary-s);color:var(--pfe-primary);display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;box-shadow:0 0 0 4px rgba(37,99,235,.15);">2</div>
          <div style="font-size:13px;"><strong style="color:var(--pfe-primary);">Under review</strong><br><span style="color:var(--pfe-text-soft);">We verify your business details (within 24h)</span></div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start;opacity:.5;">
          <div style="width:28px;height:28px;border-radius:50%;background:#e5e7eb;color:#9ca3af;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;">3</div>
          <div style="font-size:13px;"><strong>Approved</strong><br><span style="color:var(--pfe-text-soft);">Tier & discount activated, email confirmation sent</span></div>
        </div>
      </div>
    </div>
  </div>

  <?php elseif ( $has_applied && $status === 'rejected' ) : ?>
  <!-- REJECTED state -->
  <div class="pcs-notice pcs-notice-warning" style="margin-bottom:20px;">
    ⚠️ Your previous B2B application was not approved. You can submit a new application below.
  </div>
  <?php /* Fall through to show form */ ?>
  <?php $has_applied = false; ?>

  <?php endif; ?>

  <?php if ( ! $has_applied ) : /* Show application form */ ?>

  <!-- Hero banner -->
  <div class="pcs-apply-hero">
    <div style="position:relative;z-index:1;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.8;margin-bottom:6px;">Business Account</div>
      <h2 style="color:#fff;font-size:22px;font-weight:800;margin:0 0 8px;">Apply for B2B membership</h2>
      <p style="opacity:.92;font-size:13px;line-height:1.5;margin:0;">
        Get a tier discount on every order, a company portal with team members and quotes, and a private Image Zone and templates.
        Takes 2 minutes · Reviewed within 24 hours.
      </p>
    </div>
  </div>

  <!-- Tier overview — informational display only -->
  <div class="pcs-card pcs-tier-info-card" style="margin-bottom:20px;">
    <div class="pcs-card-head" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
      <div style="display:flex;align-items:center;gap:10px;">
        <h3 class="pcs-card-title" style="margin:0;">🏆 B2B tier benefits</h3>
        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:9999px;font-size:11px;font-weight:600;">
          ℹ️ For reference only
        </span>
      </div>
      <span style="font-size:12px;color:var(--pfe-text-soft);">Admin assigns your tier after approval</span>
    </div>
    <div class="pcs-card-body">
      <p style="font-size:13px;color:var(--pfe-text-soft);margin:0 0 16px;">
        Once your application is approved, our team will assign the right tier based on your business volume. Here's what each tier includes:
      </p>
      <div class="pcs-tier-display-grid">
        <?php
        $b2b_tiers = array_filter( $tiers, function( $t ) { return in_array( $t, array( 'b2b_a', 'b2b_b', 'b2b_c', 'vip' ) ); }, ARRAY_FILTER_USE_KEY );
        foreach ( $b2b_tiers as $tid => $tdef ) :
          $s = PCS_DB::get_tier_style( $tid );
          $discount = (int) $tdef['discount'];
        ?>
        <div class="pcs-tier-display-card" style="--tier-bg:<?php echo esc_attr( $s['bg'] ); ?>;--tier-color:<?php echo esc_attr( $s['color'] ); ?>;">
          <div class="pcs-tier-display-label"><?php echo esc_html( $tdef['label'] ); ?></div>
          <div class="pcs-tier-display-discount"><?php echo $discount; ?>%</div>
          <ul class="pcs-tier-display-perks">
            <li>off all products</li>
            <li>Company portal: orders, team members and quotes</li>
            <li>Company Image Zone for your logos and artwork</li>
            <li>Private company templates</li>
            <li>Products reserved for your company</li>
            <li>One-click reorder with your original art</li>
          </ul>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Application form -->
  <div class="pcs-card">
    <div class="pcs-card-head brand">
      <h3 class="pcs-card-title">📋 Your application</h3>
    </div>
    <div class="pcs-card-body">
      <?php wc_print_notices(); ?>
      <form method="post" action="">
        <?php wp_nonce_field( 'pcs_apply_b2b', 'pcs_apply_b2b_nonce' ); ?>

        <div class="pcs-form-grid">
          <div class="pcs-form-field">
            <label class="pcs-form-label" for="pcs_company_name">Company name <span style="color:red;">*</span></label>
            <input type="text" id="pcs_company_name" name="company_name" class="pcs-input"
                   placeholder="e.g. ACME Corp" required
                   value="<?php echo esc_attr( $_POST['company_name'] ?? '' ); ?>">
          </div>
          <div class="pcs-form-field">
            <label class="pcs-form-label" for="pcs_industry">Industry</label>
            <select id="pcs_industry" name="industry" class="pcs-select">
              <option value="">— Select —</option>
              <?php
              $industries = array( 'Marketing & Advertising', 'Real estate', 'Event management', 'Retail / Restaurant', 'Auto dealer', 'Construction', 'Healthcare', 'Education', 'Other' );
              foreach ( $industries as $ind ) : ?>
              <option value="<?php echo esc_attr( $ind ); ?>" <?php selected( $_POST['industry'] ?? '', $ind ); ?>><?php echo esc_html( $ind ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="pcs-form-grid">
          <div class="pcs-form-field">
            <label class="pcs-form-label" for="pcs_contact_name">Contact name <span style="color:red;">*</span></label>
            <input type="text" id="pcs_contact_name" name="contact_name" class="pcs-input"
                   value="<?php echo esc_attr( $_POST['contact_name'] ?? $user->display_name ); ?>" required>
          </div>
          <div class="pcs-form-field">
            <label class="pcs-form-label" for="pcs_contact_email">Business email <span style="color:red;">*</span></label>
            <input type="email" id="pcs_contact_email" name="contact_email" class="pcs-input"
                   value="<?php echo esc_attr( $_POST['contact_email'] ?? $user->user_email ); ?>" required>
          </div>
        </div>

        <div class="pcs-form-grid">
          <div class="pcs-form-field">
            <label class="pcs-form-label" for="pcs_contact_phone">Phone</label>
            <input type="tel" id="pcs_contact_phone" name="contact_phone" class="pcs-input"
                   placeholder="(555) 123-4567"
                   value="<?php echo esc_attr( $_POST['contact_phone'] ?? '' ); ?>">
          </div>
          <div class="pcs-form-field">
            <label class="pcs-form-label" for="pcs_website">Website</label>
            <input type="url" id="pcs_website" name="website" class="pcs-input"
                   placeholder="https://yourcompany.com"
                   value="<?php echo esc_attr( $_POST['website'] ?? '' ); ?>">
          </div>
        </div>

        <div class="pcs-form-field">
          <label class="pcs-form-label" for="pcs_monthly_volume">
            Estimated monthly print volume
            <span class="pcs-form-hint">Helps us assign the right tier</span>
          </label>
          <select id="pcs_monthly_volume" name="monthly_volume" class="pcs-select" style="max-width:320px;">
            <option value="">— Select range —</option>
            <option value="Under $500"  <?php selected( $_POST['monthly_volume'] ?? '', 'Under $500' ); ?>>Under $500/month</option>
            <option value="$500-$2,000" <?php selected( $_POST['monthly_volume'] ?? '', '$500-$2,000' ); ?>>$500 – $2,000/month</option>
            <option value="$2,000-$5,000" <?php selected( $_POST['monthly_volume'] ?? '', '$2,000-$5,000' ); ?>>$2,000 – $5,000/month</option>
            <option value="$5,000-$10,000" <?php selected( $_POST['monthly_volume'] ?? '', '$5,000-$10,000' ); ?>>$5,000 – $10,000/month</option>
            <option value="$10,000+" <?php selected( $_POST['monthly_volume'] ?? '', '$10,000+' ); ?>>$10,000+/month</option>
          </select>
        </div>

        <div class="pcs-form-field">
          <label class="pcs-form-label" for="pcs_about">
            Tell us about your business
            <span class="pcs-form-hint">What products do you need? Any special requirements?</span>
          </label>
          <textarea id="pcs_about" name="about" class="pcs-textarea" placeholder="We primarily need banners, yard signs, and event backdrops for…"><?php echo esc_textarea( $_POST['about'] ?? '' ); ?></textarea>
        </div>

        <div class="pcs-notice pcs-notice-info" style="margin-bottom:16px;">
          ℹ️ By submitting, you agree that we may contact you to verify your business details. Applications are reviewed within 24 business hours.
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-overview' ) ); ?>" class="pcs-btn pcs-btn-tertiary">Cancel</a>
          <button type="submit" class="pcs-btn pcs-btn-primary pcs-btn-lg">
            Submit B2B application →
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php endif; // end form ?>

</div>
