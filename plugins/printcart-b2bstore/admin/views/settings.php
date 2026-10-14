<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$tiers = PCS_DB::get_tiers_from_rules();
?>
<div class="pcs-wrap">

  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <h1>Settings</h1>
      <p class="pcs-page-sub">Configure your B2B store preferences, tier thresholds, and notification settings.</p>
    </div>
    <div class="pcs-page-actions">
      <button form="pcs-settings-form" type="submit" class="pcs-btn pcs-btn-primary">Save changes</button>
    </div>
  </div>

  <!-- Tabs -->
  <div class="pcs-tabs">
    <a href="#" class="pcs-tab active" data-tab="general">General</a>
    <a href="#" class="pcs-tab" data-tab="b2b">B2B Settings</a>
    <a href="#" class="pcs-tab" data-tab="tiers">Tier Thresholds</a>
    <a href="#" class="pcs-tab" data-tab="advanced">Advanced</a>
  </div>

  <form id="pcs-settings-form">

    <!-- ====== General Tab ====== -->
    <div class="pcs-tab-panel active" data-panel="general">
      <div class="pcs-layout-2col">
        <div>

          <div class="pcs-card">
            <div class="pcs-card-head is-brand"><span class="pcs-card-title">Store Identity</span></div>
            <div class="pcs-card-body">
              <div class="pcs-form-row">
                <label class="pcs-form-label">Store name</label>
                <input type="text" name="pcs_store_name" class="pcs-input" value="<?php echo esc_attr( get_option( 'pcs_store_name', get_bloginfo( 'name' ) ) ); ?>">
              </div>
              <div class="pcs-form-row">
                <div class="pcs-form-label">Admin email
                  <div class="pcs-form-hint">Notification emails are sent here. To change, go to <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" style="color:var(--pcs-brand);">Settings → General</a>.</div>
                </div>
                <input type="email" class="pcs-input" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" readonly style="opacity:.7;cursor:not-allowed;background:var(--pcs-bg-soft,#f5f5f5);">
              </div>
              <div class="pcs-form-row">
                <label class="pcs-form-label">Support phone</label>
                <input type="text" name="pcs_support_phone" class="pcs-input" value="<?php echo esc_attr( get_option( 'pcs_support_phone' ) ); ?>" placeholder="(555) 123-4567">
              </div>
              <div class="pcs-form-row" style="border:none;">
                <label class="pcs-form-label">Currency</label>
                <select name="pcs_currency" class="pcs-select" style="max-width:180px;">
                  <?php
                  $wc_curr = function_exists( 'get_woocommerce_currencies' ) ? get_woocommerce_currencies() : array( 'USD' => 'USD' );
                  $cur_cur = get_option( 'pcs_currency', get_option( 'woocommerce_currency', 'USD' ) );
                  foreach ( $wc_curr as $code => $name ) : ?>
                  <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cur_cur, $code ); ?>><?php echo esc_html( $code . ' — ' . $name ); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="pcs-card">
            <div class="pcs-card-head"><span class="pcs-card-title">Production</span></div>
            <div class="pcs-card-body">
              <div class="pcs-form-row">
                <label class="pcs-form-label">Production location</label>
                <input type="text" name="pcs_production_location" class="pcs-input" value="<?php echo esc_attr( get_option( 'pcs_production_location' ) ); ?>" placeholder="City, State · ZIP">
              </div>
              <div class="pcs-form-row">
                <label class="pcs-form-label">Standard turnaround</label>
                <div class="pcs-row">
                  <input type="number" name="pcs_standard_turnaround" class="pcs-input" value="<?php echo esc_attr( get_option( 'pcs_standard_turnaround', 3 ) ); ?>" style="max-width:80px;">
                  <span style="font-size:13px;color:var(--pcs-text-soft);">business days</span>
                </div>
              </div>
              <div class="pcs-form-row">
                <div class="pcs-form-label">Rush production
                  <div class="pcs-form-hint">Enable next-day option</div>
                </div>
                <div>
                  <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="pcs_rush_enabled" value="1" <?php checked( get_option( 'pcs_rush_enabled' ), '1' ); ?>>
                    Enable rush production option
                  </label>
                  <div class="pcs-row" style="margin-top:8px;">
                    <span style="font-size:13px;">Surcharge:</span>
                    <input type="number" name="pcs_rush_surcharge" class="pcs-input" value="<?php echo esc_attr( get_option( 'pcs_rush_surcharge', 15 ) ); ?>" style="max-width:80px;">
                    <span style="font-size:13px;color:var(--pcs-text-soft);">% or flat $</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div>
          <div class="pcs-card">
            <div class="pcs-card-head"><span class="pcs-card-title">System info</span></div>
            <div class="pcs-card-body" style="font-size:13px;line-height:1.7;">
              <div class="pcs-info-row"><span class="pcs-info-label">Plugin version</span><span class="pcs-info-value"><?php echo PCS_VERSION; ?></span></div>
              <div class="pcs-info-row"><span class="pcs-info-label">WordPress</span><span class="pcs-info-value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span></div>
              <div class="pcs-info-row"><span class="pcs-info-label">WooCommerce</span><span class="pcs-info-value"><?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : 'Not active'; ?></span></div>
              <div class="pcs-info-row"><span class="pcs-info-label">CMSmart</span><span class="pcs-info-value"><?php echo defined( 'NBDESIGNER_VERSION' ) ? esc_html( NBDESIGNER_VERSION ) : 'Not active'; ?></span></div>
              <div class="pcs-info-row" style="border:none;"><span class="pcs-info-label">PHP</span><span class="pcs-info-value"><?php echo esc_html( PHP_VERSION ); ?></span></div>
            </div>
          </div>

          <div class="pcs-card">
            <div class="pcs-card-head"><span class="pcs-card-title">Save</span></div>
            <div class="pcs-card-body">
              <button form="pcs-settings-form" type="submit" class="pcs-btn pcs-btn-primary" style="width:100%;justify-content:center;">Save all settings</button>
              <p style="font-size:11px;color:var(--pcs-text-mute);text-align:center;margin-top:8px;">Settings saved via secure AJAX · no page reload</p>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /general tab -->

    <!-- ====== B2B Tab ====== -->
    <div class="pcs-tab-panel" data-panel="b2b" style="display:none;">
      <div class="pcs-card">
        <div class="pcs-card-head is-brand"><span class="pcs-card-title">B2B Application Settings</span></div>
        <div class="pcs-card-body">
          <div class="pcs-form-row">
            <div class="pcs-form-label">Allow self-registration
              <div class="pcs-form-hint">Show "Apply for B2B" on account page</div>
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="pcs_b2b_self_register" value="1" <?php checked( get_option( 'pcs_b2b_self_register', '1' ), '1' ); ?>>
              Allow customers to apply for B2B status
            </label>
          </div>
          <div class="pcs-form-row">
            <div class="pcs-form-label">Auto-approve applications</div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="pcs_b2b_auto_approve" value="1" <?php checked( get_option( 'pcs_b2b_auto_approve' ), '1' ); ?>>
              Auto-approve applications (skip manual review)
            </label>
          </div>
          <div class="pcs-form-row" style="border:none;">
            <div class="pcs-form-label">Default tier on approval</div>
            <select name="pcs_b2b_default_tier" class="pcs-select" style="max-width:200px;">
              <?php foreach ( $tiers as $tid => $tdef ) : ?>
                <option value="<?php echo esc_attr( $tid ); ?>" <?php selected( get_option( 'pcs_b2b_default_tier', 'b2b_a' ), $tid ); ?>>
                  <?php echo esc_html( $tdef['label'] ); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ====== Tier Thresholds Tab ====== -->
    <div class="pcs-tab-panel" data-panel="tiers" style="display:none;">
      <div class="pcs-card">
        <div class="pcs-card-head is-brand"><span class="pcs-card-title">Auto-upgrade Thresholds (cumulative spend)</span></div>
        <div class="pcs-card-body">
          <p style="color:var(--pcs-text-soft);font-size:13px;margin-top:0;">When a customer's lifetime spend reaches a threshold, they're automatically upgraded to that tier.</p>
          <?php foreach ( $tiers as $tid => $tdef ) :
            if ( $tid === 'b2c' ) continue; // B2C is the starting tier
            $opt_key = 'pcs_tier_threshold_' . $tid;
            $default = $tdef['min_spend_upgrade'] ?? 0;
          ?>
          <div class="pcs-form-row" style="<?php echo $tid === 'vip' ? 'border:none;' : ''; ?>">
            <div class="pcs-form-label">
              <span class="pcs-tier-pill" data-tier="<?php echo esc_attr( $tid ); ?>"><?php echo esc_html( $tdef['label'] ); ?></span>
              <div class="pcs-form-hint">0 = manual only</div>
            </div>
            <div class="pcs-row">
              <span style="font-size:13px;">$</span>
              <input type="number" name="<?php echo esc_attr( $opt_key ); ?>" class="pcs-input"
                     value="<?php echo esc_attr( get_option( $opt_key, $default ) ); ?>"
                     style="max-width:120px;" min="0" step="100">
              <span style="font-size:13px;color:var(--pcs-text-soft);">cumulative spend</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ====== Advanced Tab ====== -->
    <div class="pcs-tab-panel" data-panel="advanced" style="display:none;">
      <div class="pcs-card">
        <div class="pcs-card-head"><span class="pcs-card-title">Advanced Options</span></div>
        <div class="pcs-card-body">
          <div class="pcs-form-row">
            <div class="pcs-form-label">Apply discounts in cart</div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="pcs_apply_in_cart" value="1" <?php checked( get_option( 'pcs_apply_in_cart', '1' ), '1' ); ?>>
              Apply tier discounts to WooCommerce cart prices
            </label>
          </div>
          <div class="pcs-form-row">
            <div class="pcs-form-label">Show tier badge</div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="pcs_show_tier_badge" value="1" <?php checked( get_option( 'pcs_show_tier_badge', '1' ), '1' ); ?>>
              Show B2B tier discount badge on product pages
            </label>
          </div>
          <div class="pcs-form-row" style="border:none;">
            <div class="pcs-form-label">Debug mode</div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="pcs_debug_mode" value="1" <?php checked( get_option( 'pcs_debug_mode' ), '1' ); ?>>
              Enable debug logging (writes to WP debug.log)
            </label>
          </div>
        </div>
      </div>
    </div>

  </form>

</div>

<style>
.pcs-tab-panel.active { display: block !important; }
</style>
