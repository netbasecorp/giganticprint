<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$tier_rules  = PCS_DB::get_pricing_rules( array( 'rule_type' => 'tier',  'per_page' => 20 ) );
$bulk_rules  = PCS_DB::get_pricing_rules( array( 'rule_type' => 'bulk',  'per_page' => 20 ) );
$promo_rules = PCS_DB::get_pricing_rules( array( 'rule_type' => 'promo', 'per_page' => 20 ) );
$tiers       = PCS_DB::get_tiers_from_rules();

$payment_terms_labels = array(
    'prepay' => 'Credit card (pre-pay)',
    'net15'  => 'Net-15',
    'net30'  => 'Net-30',
    'net60'  => 'Net-60',
);
?>
<div class="pcs-wrap">

  <!-- Header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <h1>Pricing Rules</h1>
      <p class="pcs-page-sub">Define tier discounts, bulk pricing, and special rules. Rules cascade — higher priority wins.</p>
    </div>
    <div class="pcs-page-actions">
      <button class="pcs-btn pcs-btn-secondary" onclick="pcsOpenModal('pcs-modal-new-rule')">+ Add rule</button>
    </div>
  </div>

  <!-- ====== Tier ladder ====== -->
  <div class="pcs-card">
    <div class="pcs-card-head">
      <span class="pcs-card-title">🏆 Tier Discount Ladder</span>
      <span style="font-size:12px;color:var(--pcs-text-soft);">Applied to all products unless overridden at product level</span>
    </div>
    <div class="pcs-card-body p0">
      <table class="pcs-table">
        <thead>
          <tr>
            <th>Tier</th>
            <th>Discount %</th>
            <th>Payment terms</th>
            <th>Customers</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ( empty( $tier_rules ) ) : ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--pcs-text-soft);">No tier rules. <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-pricing' ) ); ?>">Activate plugin</a> to seed defaults.</td></tr>
          <?php else : ?>
          <?php
          $tier_counts = PCS_DB::get_tier_counts();
          foreach ( $tier_rules as $rule ) :
            $tc       = isset( $tier_counts[ $rule->target_value ] ) ? $tier_counts[ $rule->target_value ] : 0;
            $pt_label = $payment_terms_labels[ $rule->payment_terms ] ?? $rule->payment_terms;
            $ship_val = $rule->free_shipping_threshold !== null ? wc_price( $rule->free_shipping_threshold ) : 'Always free';
            if ( (float) $rule->free_shipping_threshold === 0.0 ) $ship_val = 'Always free';
          ?>
          <tr>
            <td><?php echo PCS_DB::tier_pill_html( $rule->target_value, $rule->name ); ?></td>
            <td><strong><?php echo (float) $rule->discount_value; ?>%</strong></td>
            <td><?php echo esc_html( $pt_label ); ?></td>
            <td><?php echo (int) $tc; ?></td>
            <td>
              <span class="pcs-status-pill" data-status="<?php echo 'active' === $rule->status ? 'active' : 'disabled'; ?>">
                <span class="dot"></span><?php echo esc_html( ucfirst( $rule->status ) ); ?>
              </span>
            </td>
            <td>
              <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm"
                      onclick="pcsEditRule(<?php echo (int) $rule->id; ?>, <?php echo esc_attr( json_encode( $rule ) ); ?>)">Edit</button>
              <button class="pcs-btn pcs-btn-danger pcs-btn-sm pcs-btn-delete-rule"
                      data-rule-id="<?php echo (int) $rule->id; ?>"
                      data-rule-name="<?php echo esc_attr( $rule->name ); ?>">×</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="pcs-card-foot">
      <span style="font-size:12px;color:var(--pcs-text-soft);">Changes apply to all future orders. Past orders unaffected.</span>
      <button class="pcs-btn pcs-btn-secondary pcs-btn-sm" data-open-rule-type="tier">+ Add tier rule</button>
    </div>
  </div>

  <!-- ====== Bulk pricing ====== -->
  <div class="pcs-card">
    <div class="pcs-card-head">
      <span class="pcs-card-title">📦 Quantity-Based Discounts</span>
      <span style="font-size:12px;color:var(--pcs-text-soft);">Stack on top of tier discount</span>
    </div>
    <div class="pcs-card-body p0">
      <table class="pcs-table">
        <thead>
          <tr>
            <th>Rule name</th>
            <th>Quantity trigger</th>
            <th>Extra discount</th>
            <th>Applies to</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ( empty( $bulk_rules ) ) : ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:24px;color:var(--pcs-text-soft);">
              No bulk rules yet.
              <button class="pcs-btn pcs-btn-secondary pcs-btn-sm" data-open-rule-type="bulk" style="margin-left:8px;">+ Add bulk rule</button>
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ( $bulk_rules as $rule ) : ?>
          <tr>
            <td><?php echo esc_html( $rule->name ); ?></td>
            <td><?php echo esc_html( $rule->target_value ); ?>+ units</td>
            <td><strong>+<?php echo (float) $rule->discount_value; ?>%</strong></td>
            <td>
              <?php
              $applies_label = 'All B2B tiers';
              if ( $rule->target_type && $rule->target_type !== 'tier' && $rule->target_type !== 'quantity' ) {
                  $applies_label = 'Tier: ' . esc_html( $rule->target_type );
              }
              echo esc_html( $applies_label );
              ?>
            </td>
            <td>
              <span class="pcs-status-pill" data-status="<?php echo 'active' === $rule->status ? 'active' : 'disabled'; ?>">
                <span class="dot"></span><?php echo esc_html( ucfirst( $rule->status ) ); ?>
              </span>
            </td>
            <td>
              <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm" onclick="pcsEditRule(<?php echo (int) $rule->id; ?>, <?php echo esc_attr( json_encode( $rule ) ); ?>)">Edit</button>
              <button class="pcs-btn pcs-btn-danger pcs-btn-sm pcs-btn-delete-rule" data-rule-id="<?php echo (int) $rule->id; ?>" data-rule-name="<?php echo esc_attr( $rule->name ); ?>">×</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="pcs-card-foot">
      <span></span>
      <button class="pcs-btn pcs-btn-secondary pcs-btn-sm" data-open-rule-type="bulk">+ Add quantity break</button>
    </div>
  </div>

  <!-- ====== Special / Promo rules ====== -->
  <div class="pcs-card">
    <div class="pcs-card-head">
      <span class="pcs-card-title">🎁 Special & Promo Rules</span>
      <span style="font-size:12px;color:var(--pcs-text-soft);">Promo codes, time-limited campaigns</span>
    </div>
    <div class="pcs-card-body p0">
      <table class="pcs-table">
        <thead>
          <tr>
            <th>Rule name</th>
            <th>Trigger</th>
            <th>Discount</th>
            <th>Valid until</th>
            <th>Priority</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ( empty( $promo_rules ) ) : ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:24px;color:var(--pcs-text-soft);">
              No promo rules yet.
              <span style="display:inline-flex;align-items:center;gap:6px;margin-left:8px;">
                <button class="pcs-btn pcs-btn-secondary pcs-btn-sm" disabled style="opacity:.5;cursor:not-allowed;">+ Add promo rule</button>
                <span style="font-size:10px;background:#f3f4f6;color:#9ca3af;padding:1px 8px;border-radius:9999px;">Coming soon</span>
              </span>
            </td>
          </tr>
          <?php else : ?>
          <?php foreach ( $promo_rules as $rule ) : ?>
          <tr>
            <td><?php echo esc_html( $rule->name ); ?></td>
            <td><?php echo $rule->promo_code ? 'Code: <code>' . esc_html( $rule->promo_code ) . '</code>' : esc_html( $rule->target_type ); ?></td>
            <td><?php echo (float) $rule->discount_value; ?>%</td>
            <td style="font-size:12px;"><?php echo $rule->valid_until ? esc_html( date_i18n( 'M j, Y', strtotime( $rule->valid_until ) ) ) : 'No expiry'; ?></td>
            <td><?php echo (int) $rule->priority; ?></td>
            <td>
              <span class="pcs-status-pill" data-status="<?php echo 'active' === $rule->status ? 'active' : 'disabled'; ?>">
                <span class="dot"></span><?php echo esc_html( ucfirst( $rule->status ) ); ?>
              </span>
            </td>
            <td>
              <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm" onclick="pcsEditRule(<?php echo (int) $rule->id; ?>, <?php echo esc_attr( json_encode( $rule ) ); ?>)">Edit</button>
              <button class="pcs-btn pcs-btn-danger pcs-btn-sm pcs-btn-delete-rule" data-rule-id="<?php echo (int) $rule->id; ?>" data-rule-name="<?php echo esc_attr( $rule->name ); ?>">×</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="pcs-card-foot">
      <span></span>
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <button class="pcs-btn pcs-btn-secondary pcs-btn-sm" disabled style="opacity:.5;cursor:not-allowed;">+ New promo rule</button>
        <span style="font-size:10px;background:#f3f4f6;color:#9ca3af;padding:1px 8px;border-radius:9999px;">Coming soon</span>
      </span>
    </div>
  </div>

</div>

<!-- ===== Pricing Rule Modal ===== -->
<div class="pcs-modal-backdrop" id="pcs-modal-new-rule">
  <div class="pcs-modal" style="max-width:560px;">
    <div class="pcs-modal-head">
      <h3 class="pcs-modal-title" id="pcs-rule-modal-title">Add pricing rule</h3>
      <button class="pcs-modal-close">×</button>
    </div>
    <div class="pcs-modal-body">
      <form id="pcs-form-pricing-rule" autocomplete="off">
        <input type="hidden" id="pcs-rule-id" name="id" value="">

        <!-- Rule type -->
        <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">Rule type</label>
          <select id="pcs-rule-type" name="rule_type" class="pcs-select" style="max-width:200px;" onchange="pcsToggleRuleFields(this.value)">
            <option value="tier">🏷️ Tier discount</option>
            <option value="bulk">📦 Bulk / quantity</option>
            <option value="promo" disabled style="color:#9ca3af;">🎁 Promo / campaign (coming soon)</option>
          </select>
        </div>

        <!-- Rule name -->
        <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">Rule name *</label>
          <input type="text" id="pcs-rule-name" name="name" class="pcs-input" required style="max-width:100%;" placeholder="e.g. B2B Gold (30%)">
        </div>

        <!-- Target value — label/hint change by type -->
        <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">
            <span id="pcs-tv-label">Tier key *</span>
            <div class="pcs-form-hint" id="pcs-tv-hint">Lowercase letters &amp; underscores. e.g. <code>b2b_gold</code></div>
          </label>
          <div>
            <input type="text" id="pcs-rule-target-value" name="target_value"
                   class="pcs-input" style="max-width:220px;" placeholder="e.g. b2b_gold">
            <!-- Quick-pick buttons for tier keys -->
            <div id="pcs-tier-quickpick" style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
              <span style="font-size:11px;color:var(--pcs-text-soft);">Quick pick:</span>
              <?php
              $suggested_keys = array( 'b2c', 'b2b_a', 'b2b_b', 'b2b_c', 'vip' );
              foreach ( $suggested_keys as $tk ) :
                $in_use = isset( $tiers[ $tk ] );
              ?>
              <button type="button"
                      class="pcs-btn pcs-btn-tertiary pcs-btn-sm"
                      style="font-size:10px;height:22px;padding:0 8px;<?php echo $in_use ? 'border-color:#2563eb;color:#2563eb;' : ''; ?>"
                      onclick="document.getElementById('pcs-rule-target-value').value='<?php echo esc_js( $tk ); ?>'">
                <?php echo esc_html( $tk ); ?><?php echo $in_use ? ' ✓' : ''; ?>
              </button>
              <?php endforeach; ?>
            </div>
            <div id="pcs-tv-note" style="font-size:11px;color:var(--pcs-text-mute);margin-top:4px;">
              Active tiers: <strong><?php echo esc_html( implode( ', ', array_keys( $tiers ) ) ); ?></strong>.
              Type a <strong>new key</strong> to create a brand-new tier.
            </div>
          </div>
        </div>

        <!-- Discount -->
        <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">Discount *</label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="number" id="pcs-rule-discount-value" name="discount_value" class="pcs-input"
                   min="0" max="100" step="0.01" required value="" placeholder="0"
                   style="max-width:80px;">
            <select id="pcs-rule-discount-type" name="discount_type" class="pcs-select" style="max-width:160px;">
              <option value="percentage">% off</option>
              <option value="fixed">Fixed $ off</option>
            </select>
          </div>
        </div>

        <!-- Tier-only fields -->
        <div id="pcs-tier-fields">
          <!-- Min order and Free shipping hidden (stored but not enforced yet) -->
          <input type="hidden" id="pcs-rule-min-order" name="min_order" value="0">
          <input type="hidden" id="pcs-rule-free-shipping" name="free_shipping_threshold" value="0">
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Payment terms</label>
            <select id="pcs-rule-payment-terms" name="payment_terms" class="pcs-select" style="max-width:210px;">
              <option value="">— Store default —</option>
              <?php foreach ( $payment_terms_labels as $v => $l ) : ?>
                <option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $l ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Promo-only fields -->
        <div id="pcs-promo-fields" style="display:none;">
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Promo code</label>
            <input type="text" id="pcs-rule-promo-code" name="promo_code" class="pcs-input" placeholder="e.g. SAVE20" style="max-width:180px;">
          </div>
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Valid until</label>
            <input type="date" id="pcs-rule-valid-until" name="valid_until" class="pcs-input" style="max-width:160px;">
          </div>
        </div>

        <!-- Status -->
        <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;border:none;">
          <label class="pcs-form-label">Status</label>
          <select id="pcs-rule-status" name="status" class="pcs-select" style="max-width:140px;">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </form>
    </div>
    <div class="pcs-modal-foot">
      <button class="pcs-btn pcs-btn-tertiary pcs-modal-close">Cancel</button>
      <button id="pcs-save-rule-btn" class="pcs-btn pcs-btn-primary" onclick="pcsSaveRule()">Save rule</button>
    </div>
  </div>
</div>

<script>
(function($) {

  /* Show/hide fields by rule type */
  window.pcsToggleRuleFields = function(type) {
    var isTier  = (type === 'tier');
    var isPromo = (type === 'promo');
    var isBulk  = (type === 'bulk');
    $('#pcs-tier-fields').toggle(isTier);
    $('#pcs-promo-fields').toggle(isPromo);
    $('#pcs-tier-quickpick, #pcs-tv-note').toggle(isTier);

    if (isTier) {
      $('#pcs-tv-label').text('Tier key *');
      $('#pcs-tv-hint').html('Lowercase letters &amp; underscores only. e.g. <code>b2b_gold</code>');
      $('#pcs-rule-target-value').attr('placeholder', 'e.g. b2b_gold');
    } else if (isBulk) {
      $('#pcs-tv-label').text('Min quantity *');
      $('#pcs-tv-hint').text('Apply extra discount when order qty reaches this number.');
      $('#pcs-rule-target-value').attr('placeholder', 'e.g. 50');
    } else {
      $('#pcs-tv-label').text('Applies to');
      $('#pcs-tv-hint').text('Use "all" for all products.');
      $('#pcs-rule-target-value').val('all').attr('placeholder', 'all');
    }
  };

  /* Reset and open modal */
  function openRuleModal(type) {
    document.getElementById('pcs-form-pricing-rule').reset();
    $('#pcs-rule-id').val('');
    $('#pcs-rule-type').val(type || 'tier');
    pcsToggleRuleFields(type || 'tier');
    $('#pcs-rule-modal-title').text('Add pricing rule');
    $('#pcs-modal-new-rule').addClass('open');
    $('body').css('overflow', 'hidden');
  }

  /* Buttons with data-open-rule-type */
  $(document).on('click', '[data-open-rule-type]', function() {
    openRuleModal($(this).data('open-rule-type'));
  });

  /* Header "+ Add rule" fallback */
  $(document).on('click', '.pcs-btn-open-rule-modal', function() {
    openRuleModal('tier');
  });

  /* Override pcsOpenModal for this modal */
  var _origPcsOpenModal = window.pcsOpenModal;
  window.pcsOpenModal = function(id) {
    if (id === 'pcs-modal-new-rule') {
      openRuleModal('tier');
      return;
    }
    if (typeof _origPcsOpenModal === 'function') _origPcsOpenModal(id);
    else $('#' + id).addClass('open');
  };

  /* Edit existing rule — use ID selectors for reliability */
  window.pcsEditRule = function(id, rule) {
    var type = rule.rule_type || 'tier';

    // 1. Switch type first (shows/hides sections)
    $('#pcs-rule-type').val(type);
    pcsToggleRuleFields(type);

    // 2. Fill fields by ID — guaranteed to find the right element
    $('#pcs-rule-id').val(rule.id          || '');
    $('#pcs-rule-name').val(rule.name      || '');
    $('#pcs-rule-target-value').val(rule.target_value || '');

    // discount_value may be "10.0000" from DB — parse to float for clean display
    var discVal = rule.discount_value !== undefined && rule.discount_value !== null
                  ? parseFloat(rule.discount_value) : '';
    $('#pcs-rule-discount-value').val(discVal !== '' ? discVal : '');
    $('#pcs-rule-discount-type').val(rule.discount_type    || 'percentage');

    // min_order / free_shipping — convert "0.00" to 0 properly
    $('#pcs-rule-min-order').val(parseFloat(rule.min_order  || 0));
    $('#pcs-rule-payment-terms').val(rule.payment_terms     || '');
    $('#pcs-rule-free-shipping').val(parseFloat(rule.free_shipping_threshold || 0));

    // Promo / date
    $('#pcs-rule-promo-code').val(rule.promo_code   || '');
    $('#pcs-rule-valid-until').val(rule.valid_until || '');
    $('#pcs-rule-status').val(rule.status           || 'active');

    // Update modal title and quick-pick hint
    var tierKey = rule.target_value || '';
    if (type === 'tier' && tierKey) {
      $('#pcs-tier-preset').val(
        $('#pcs-tier-preset option[value="' + tierKey + '"]').length ? tierKey : '_custom'
      );
    }

    $('#pcs-rule-modal-title').text('Edit: ' + (rule.name || 'rule'));
    $('#pcs-modal-new-rule').addClass('open');
    $('body').css('overflow', 'hidden');
  };

  /* Save rule — explicit field collection, no :input ambiguity */
  window.pcsSaveRule = function() {
    var form = document.getElementById('pcs-form-pricing-rule');
    var $btn = $('#pcs-save-rule-btn').prop('disabled', true).text('Saving…');
    var type = $('#pcs-rule-type').val();
    var tv   = $('#pcs-rule-target-value').val().trim();

    /* Client-side validation */
    if (!$('[name="name"]', form).val().trim()) {
      pcsToast('Please enter a rule name.', 'error');
      $btn.prop('disabled', false).text('Save rule');
      return;
    }
    if (type === 'tier' && !tv) {
      pcsToast('Please enter a Tier key (e.g. b2b_gold).', 'error');
      $('#pcs-rule-target-value').focus();
      $btn.prop('disabled', false).text('Save rule');
      return;
    }
    if (type === 'bulk' && !tv) {
      pcsToast('Please enter a minimum quantity.', 'error');
      $btn.prop('disabled', false).text('Save rule');
      return;
    }

    var ttMap  = { tier: 'tier', bulk: 'quantity', promo: 'all' };
    var data = {
      _nonce:                   (typeof PCS !== 'undefined') ? PCS.nonce : '',
      action:                   'pcs_save_pricing_rule',
      rule_type:                type,
      target_type:              ttMap[type] || type,
      target_value:             tv,
      name:                     $('[name="name"]',                    form).val(),
      discount_value:           $('[name="discount_value"]',          form).val(),
      discount_type:            $('[name="discount_type"]',           form).val(),
      min_order:                $('[name="min_order"]',               form).val() || 0,
      payment_terms:            $('[name="payment_terms"]',           form).val(),
      free_shipping_threshold:  $('[name="free_shipping_threshold"]', form).val() || 0,
      promo_code:               $('[name="promo_code"]',              form).val(),
      valid_until:              $('[name="valid_until"]',             form).val(),
      status:                   $('[name="status"]',                  form).val(),
    };
    var rid = $('#pcs-rule-id').val();
    if (rid) data.id = rid;

    $.post((typeof PCS !== 'undefined') ? PCS.ajax_url : ajaxurl, data, function(res) {
      if (res.success) {
        pcsToast(res.data.message || 'Rule saved!', 'success');
        pcsCloseModal('pcs-modal-new-rule');
        setTimeout(function() { location.reload(); }, 700);
      } else {
        pcsToast((res.data && res.data.message) || 'Save failed.', 'error');
        $btn.prop('disabled', false).text('Save rule');
      }
    }).fail(function() {
      pcsToast('Request failed.', 'error');
      $btn.prop('disabled', false).text('Save rule');
    });
  };

  /* Init */
  $(function() { pcsToggleRuleFields('tier'); });

})(jQuery);
</script>

<!-- ===== New/Edit Rule Modal ===== -->
<div class="pcs-modal-backdrop" id="pcs-modal-new-rule">
  <div class="pcs-modal" style="max-width:580px;">
    <div class="pcs-modal-head">
      <h3 class="pcs-modal-title" id="pcs-rule-modal-title">Add pricing rule</h3>
      <button class="pcs-modal-close">×</button>
    </div>
    <div class="pcs-modal-body">
      <form id="pcs-form-pricing-rule">
        <input type="hidden" id="pcs-rule-id"          name="id"          value="">
        <input type="hidden" id="pcs-rule-target-type" name="target_type" value="tier">

        <!-- Rule type selector — dạng radio card cho trực quan -->
        <div style="margin-bottom:14px;">
          <div style="font-size:12px;font-weight:600;color:var(--pcs-text-soft);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Rule type</div>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
            <label id="pcs-rtype-tier"  class="pcs-rule-type-card active" onclick="pcsSetRuleType('tier')">
              <input type="radio" name="rule_type" value="tier" checked style="display:none;">
              <div style="font-size:13px;font-weight:700;">🏷️ Tier</div>
              <div style="font-size:11px;color:var(--pcs-text-soft);">Discount by customer tier</div>
            </label>
            <label id="pcs-rtype-bulk"  class="pcs-rule-type-card" onclick="pcsSetRuleType('bulk')">
              <input type="radio" name="rule_type" value="bulk" style="display:none;">
              <div style="font-size:13px;font-weight:700;">📦 Bulk qty</div>
              <div style="font-size:11px;color:var(--pcs-text-soft);">Discount by order qty</div>
            </label>
            <label id="pcs-rtype-promo" class="pcs-rule-type-card" style="opacity:.45;cursor:not-allowed;pointer-events:none;" title="Coming soon">
              <input type="radio" name="rule_type" value="promo" disabled style="display:none;">
              <div style="font-size:13px;font-weight:700;">🎁 Promo</div>
              <div style="font-size:11px;color:var(--pcs-text-soft);">Promo code / campaign</div>
              <div style="font-size:10px;background:#f3f4f6;color:#9ca3af;padding:1px 6px;border-radius:9999px;margin-top:3px;display:inline-block;">Coming soon</div>
            </label>
          </div>
        </div>

        <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">Rule name *</label>
          <input type="text" id="pcs-rule-name" name="name" class="pcs-input" required style="max-width:100%;" placeholder="e.g. B2B — Tier A (10%)">
        </div>

        <!-- Tier: dropdown chọn tier key -->
        <div id="pcs-field-tier-key" class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">Tier *
            <div class="pcs-form-hint">Chọn tier hoặc nhập key mới</div>
          </label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <select id="pcs-tier-preset" class="pcs-select" style="max-width:180px;" onchange="pcsOnTierPreset(this.value)">
              <option value="">— Chọn tier có sẵn —</option>
              <?php
              $existing_tier_keys = array_keys( $tiers );
              $all_tier_keys = array( 'b2c', 'b2b_a', 'b2b_b', 'b2b_c', 'vip', 'b2b_wholesale', 'b2b_enterprise' );
              foreach ( $all_tier_keys as $tk ) :
                  $label = isset( $tiers[ $tk ] ) ? $tiers[ $tk ]['label'] : ucwords( str_replace( '_', ' ', $tk ) );
                  $used  = in_array( $tk, $existing_tier_keys, true ) ? ' (hiện có)' : '';
              ?>
              <option value="<?php echo esc_attr( $tk ); ?>"><?php echo esc_html( $label . $used ); ?></option>
              <?php endforeach; ?>
              <option value="_custom">✏️ Nhập key tùy chỉnh…</option>
            </select>
            <input type="text" id="pcs-rule-target-value" name="target_value" class="pcs-input"
                   placeholder="key: b2b_a, vip, custom_tier…" style="max-width:180px;" value="">
          </div>
          <div></div>
          <div style="font-size:11px;color:var(--pcs-text-mute);margin-top:4px;">Key phải là chữ thường, không dấu, dùng <code>_</code>. Ví dụ: <code>b2b_a</code>, <code>vip</code></div>
        </div>

        <!-- Bulk: nhập số lượng -->
        <div id="pcs-field-bulk-qty" class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;display:none;">
          <label class="pcs-form-label">Min quantity *</label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="number" id="pcs-bulk-qty" class="pcs-input" min="1" placeholder="e.g. 10" style="max-width:100px;"
                   oninput="document.getElementById('pcs-rule-target-value').value=this.value">
            <span style="font-size:13px;color:var(--pcs-text-soft);">+ units</span>
          </div>
        </div>

        <!-- Promo: promo code -->
        <div id="pcs-field-promo-code" class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;display:none;">
          <label class="pcs-form-label">Promo code</label>
          <input type="text" id="pcs-rule-promo-code" name="promo_code" class="pcs-input" placeholder="e.g. SAVE20" style="max-width:180px;">
        </div>

        <!-- Discount -->
        <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
          <label class="pcs-form-label">Discount *</label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="number" id="pcs-rule-discount-value" name="discount_value" class="pcs-input"
                   min="0" max="100" step="0.01" required placeholder="0" style="max-width:90px;">
            <select id="pcs-rule-discount-type" name="discount_type" class="pcs-select" style="max-width:140px;">
              <option value="percentage">% giảm giá</option>
              <option value="fixed">Giảm cố định ($)</option>
            </select>
          </div>
        </div>

        <!-- Tier-specific fields -->
        <div id="pcs-tier-extra-fields">
          <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Min order ($)</label>
            <input type="number" id="pcs-rule-min-order" name="min_order" class="pcs-input" value="0" min="0" style="max-width:120px;">
          </div>
          <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Payment terms</label>
            <select id="pcs-rule-payment-terms" name="payment_terms" class="pcs-select" style="max-width:220px;">
              <option value="">— Mặc định —</option>
              <?php foreach ( $payment_terms_labels as $val => $lbl ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Free shipping<div class="pcs-form-hint">0 = luôn miễn phí</div></label>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-size:13px;">$</span>
              <input type="number" name="free_shipping_threshold" class="pcs-input" value="0" min="0" style="max-width:100px;">
              <span style="font-size:12px;color:var(--pcs-text-soft);">trở lên</span>
            </div>
          </div>
        </div>

        <!-- Promo valid until -->
        <div id="pcs-promo-extra-fields" style="display:none;">
          <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Valid until</label>
            <input type="date" id="pcs-rule-valid-until" name="valid_until" class="pcs-input" style="max-width:160px;">
          </div>
        </div>

        <div class="pcs-form-row" style="grid-template-columns:130px 1fr;gap:12px;padding:8px 0;border:none;">
          <label class="pcs-form-label">Status</label>
          <select id="pcs-rule-status" name="status" class="pcs-select" style="max-width:140px;">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </form>
    </div>
    <div class="pcs-modal-foot">
      <button class="pcs-btn pcs-btn-tertiary pcs-modal-close">Cancel</button>
      <button id="pcs-save-rule-btn" class="pcs-btn pcs-btn-primary" onclick="pcsSaveRule()">Save rule</button>
    </div>
  </div>
</div>

<style>
.pcs-rule-type-card {
  display: block;
  padding: 10px 12px;
  border: 1.5px solid var(--pcs-border-light);
  border-radius: var(--pcs-radius-md);
  background: var(--pcs-bg);
  cursor: pointer;
  transition: all .12s;
}
.pcs-rule-type-card:hover { border-color: var(--pcs-primary); }
.pcs-rule-type-card.active {
  border-color: var(--pcs-primary);
  background: var(--pcs-primary-soft);
}
</style>

<script>
(function($) {

  /* ---- Rule type UI ---- */
  window.pcsSetRuleType = function(type) {
    // radio cards highlight
    ['tier','bulk','promo'].forEach(function(t) {
      $('#pcs-rtype-' + t).toggleClass('active', t === type);
      $('#pcs-rtype-' + t + ' input').prop('checked', t === type);
    });
    // hidden target_type
    var ttMap = { tier: 'tier', bulk: 'quantity', promo: 'all' };
    $('#pcs-rule-target-type').val(ttMap[type] || type);

    // show/hide fields
    $('#pcs-field-tier-key').toggle(type === 'tier');
    $('#pcs-field-bulk-qty').toggle(type === 'bulk');
    $('#pcs-field-promo-code').toggle(type === 'promo');
    $('#pcs-tier-extra-fields').toggle(type === 'tier');
    $('#pcs-promo-extra-fields').toggle(type === 'promo');

    // clear target value when switching type
    if (type !== 'tier') $('#pcs-tier-preset').val('');
    if (type === 'bulk')  {
      $('#pcs-rule-target-value').val('');
      $('#pcs-bulk-qty').val('');
    }
    if (type === 'promo') $('#pcs-rule-target-value').val('all');
  };

  /* ---- Tier preset dropdown ---- */
  window.pcsOnTierPreset = function(val) {
    if (val === '_custom') {
      $('#pcs-rule-target-value').val('').focus();
    } else if (val) {
      $('#pcs-rule-target-value').val(val);
      // Auto-fill name nếu chưa điền
      if (!$('#pcs-rule-name').val()) {
        var label = $('#pcs-tier-preset option:selected').text().replace(' (hiện có)','');
        $('#pcs-rule-name').val(label);
      }
    }
  };

  /* ---- Open modal clean ---- */
  var origOpen = window.pcsOpenModal;
  window.pcsOpenModal = function(id) {
    if (id === 'pcs-modal-new-rule') {
      // reset form khi mở mới (không phải từ Edit)
      if (!$('#pcs-rule-id').val()) {
        $('#pcs-form-pricing-rule')[0].reset();
        $('#pcs-rule-id').val('');
        pcsSetRuleType('tier');
        $('#pcs-rule-modal-title').text('Add pricing rule');
      }
    }
    if (origOpen) origOpen(id);
    else $('#' + id).addClass('open');
  };

  /* ---- Edit rule ---- */
  window.pcsEditRule = function(id, rule) {
    $('#pcs-rule-id').val(rule.id || '');
    $('#pcs-rule-name').val(rule.name || '');
    var rtype = rule.rule_type || 'tier';
    pcsSetRuleType(rtype);
    // target value
    $('#pcs-rule-target-value').val(rule.target_value || '');
    if (rtype === 'tier') {
      var opt = $('#pcs-tier-preset option[value="' + rule.target_value + '"]');
      $('#pcs-tier-preset').val(opt.length ? rule.target_value : '_custom');
    }
    if (rtype === 'bulk') $('#pcs-bulk-qty').val(rule.target_value || '');
    if (rtype === 'promo') $('#pcs-rule-promo-code').val(rule.promo_code || '');
    $('#pcs-rule-discount-type').val(rule.discount_type || 'percentage');
    $('#pcs-rule-discount-value').val(rule.discount_value || '');
    $('#pcs-rule-min-order').val(rule.min_order || 0);
    $('#pcs-rule-payment-terms').val(rule.payment_terms || '');
    $('input[name="free_shipping_threshold"]').val(rule.free_shipping_threshold || 0);
    if ($('#pcs-rule-valid-until').length) $('#pcs-rule-valid-until').val(rule.valid_until || '');
    $('#pcs-rule-status').val(rule.status || 'active');
    $('#pcs-rule-modal-title').text('Edit pricing rule');
    $('#pcs-modal-new-rule').addClass('open');
    $('body').css('overflow','hidden');
  };

  /* ---- Save rule ---- */
  window.pcsSaveRule = function() {
    var $btn = $('#pcs-save-rule-btn').prop('disabled', true).text('Saving…');
    var data = {};
    $('#pcs-form-pricing-rule').find(':input').each(function() {
      if ($(this).attr('name')) data[$(this).attr('name')] = $(this).val();
    });
    // radio checked value
    data.rule_type = $('input[name="rule_type"]:checked').val();
    data._nonce = (typeof PCS !== 'undefined') ? PCS.nonce : '';
    data.action  = 'pcs_save_pricing_rule';
    $.post((typeof PCS !== 'undefined') ? PCS.ajax_url : ajaxurl, data, function(res) {
      if (res.success) {
        pcsToast(res.data.message || 'Saved!', 'success');
        pcsCloseModal('pcs-modal-new-rule');
        setTimeout(function() { location.reload(); }, 600);
      } else {
        pcsToast((res.data && res.data.message) ? res.data.message : 'Error.', 'error');
        $btn.prop('disabled', false).text('Save rule');
      }
    });
  };

  /* ---- Buttons trigger correct type ---- */
  $(document).on('click', '[data-open-rule-type]', function() {
    var type = $(this).data('open-rule-type');
    $('#pcs-rule-id').val('');
    pcsSetRuleType(type);
    $('#pcs-modal-new-rule').addClass('open');
    $('body').css('overflow','hidden');
  });

  // Initial state
  $(function() { pcsSetRuleType('tier'); });

})(jQuery);
</script>
