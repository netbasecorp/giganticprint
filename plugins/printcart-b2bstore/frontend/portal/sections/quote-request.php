<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var WP_User $user @var int $user_id @var string $tier @var bool $is_b2b @var object|null $store */

$categories = array(
    'Banners & Signs'        => 'Banners (vinyl, fabric, mesh)',
    'T-shirts & Apparel'     => 'T-shirts & Apparel',
    'Flyers & Brochures'     => 'Flyers & Brochures',
    'Stickers & Labels'      => 'Stickers & Labels',
    'Packaging'              => 'Packaging',
    'Trade Show Displays'    => 'Trade Show Displays',
    'Vehicle Wraps'          => 'Vehicle Wraps',
    'Other'                  => 'Other / Not sure',
);
$project_types = array(
    array( 'key' => 'bulk_order',    'icon' => '📦', 'label' => 'Bulk order',     'desc' => '50+ identical items' ),
    array( 'key' => 'custom_size',   'icon' => '📐', 'label' => 'Custom size',    'desc' => 'Non-standard dimensions' ),
    array( 'key' => 'multi_product', 'icon' => '🎁', 'label' => 'Multi-product',  'desc' => 'Event/campaign package' ),
    array( 'key' => 'special_mat',   'icon' => '✨', 'label' => 'Special material','desc' => 'Mesh, fabric, rigid, etc.' ),
    array( 'key' => 'installation',  'icon' => '🔧', 'label' => 'Installation',   'desc' => 'Sign + install service' ),
    array( 'key' => 'not_sure',      'icon' => '💡', 'label' => 'Not sure?',      'desc' => 'We\'ll help you decide' ),
);
?>

<!-- Page header -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">📋</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( get_bloginfo('name') ); ?> · Custom Quotes</div>
        <h1 class="pcs-ph-title">Request a Custom Quote</h1>
        <p class="pcs-ph-sub">Tell us about your project. We respond within 24 business hours with a detailed quote.</p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( PCS_B2B_Portal::url('quotes') ); ?>" class="pcs-pbtn pcs-pbtn-ghost">My quotes →</a>
      </div>
    </div>
  </div>
</div>

<div style="padding:20px 24px;display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

  <!-- LEFT: Form -->
  <div>
    <div id="pcs-qr-success" style="display:none;background:#dcfce7;color:#065f46;padding:16px 20px;border-radius:10px;font-size:14px;font-weight:600;margin-bottom:16px;">
      ✅ Quote request submitted! We'll respond within 24 hours.
    </div>
    <div id="pcs-qr-error" style="display:none;background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;"></div>

    <!-- Section 1: Project type -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head pcs-pcard-head-brand">
        <h3 class="pcs-pcard-title">1. What are you ordering?</h3>
      </div>
      <div class="pcs-pcard-body">
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">Project type <span style="color:#ef4444;">*</span></label>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="pcs-type-grid">
            <?php foreach ( $project_types as $pt ) : ?>
            <div class="pcs-project-type-card" data-type="<?php echo esc_attr( $pt['key'] ); ?>"
                 onclick="pcsSelectType('<?php echo esc_js( $pt['key'] ); ?>')"
                 style="border:2px solid #e5e7eb;border-radius:10px;padding:12px;cursor:pointer;transition:all .15s;">
              <div style="font-size:18px;margin-bottom:4px;"><?php echo $pt['icon']; ?></div>
              <div style="font-size:13px;font-weight:700;color:#111827;"><?php echo esc_html( $pt['label'] ); ?></div>
              <div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?php echo esc_html( $pt['desc'] ); ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="pcs-qr-type" value="">
        </div>

        <div class="pcs-pfield" style="margin-bottom:14px;">
          <label class="pcs-pfield-label">Project name <span style="color:#ef4444;">*</span></label>
          <input type="text" id="pcs-qr-title" class="pcs-pinput" placeholder="e.g. Fall event backdrops 2026">
          <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">For reference — what should we call this project?</p>
        </div>

        <div class="pcs-pfield">
          <label class="pcs-pfield-label">Product category <span style="color:#ef4444;">*</span></label>
          <select id="pcs-qr-category" class="pcs-pinput" style="appearance:auto;">
            <option value="">— Select category —</option>
            <?php foreach ( $categories as $v => $l ) : ?>
              <option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $l ); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Section 2: Details -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head pcs-pcard-head-brand">
        <h3 class="pcs-pcard-title">2. Details & specifications</h3>
      </div>
      <div class="pcs-pcard-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Quantity <span style="color:#ef4444;">*</span></label>
            <input type="number" id="pcs-qr-quantity" class="pcs-pinput" placeholder="e.g. 500" min="1">
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Deadline</label>
            <input type="date" id="pcs-qr-deadline" class="pcs-pinput">
          </div>
        </div>
        <div class="pcs-pfield">
          <label class="pcs-pfield-label">Full specifications <span style="color:#ef4444;">*</span></label>
          <textarea id="pcs-qr-message" class="pcs-pinput" rows="5" style="resize:vertical;"
            placeholder="Please include:&#10;• Size / dimensions&#10;• Material preferences&#10;• Design requirements&#10;• Any special finishing (lamination, grommets, etc.)&#10;• Delivery location"></textarea>
        </div>
      </div>
    </div>

    <!-- Section 3: Timing & design -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head pcs-pcard-head-brand">
        <h3 class="pcs-pcard-title">3. Budget & design files</h3>
      </div>
      <div class="pcs-pcard-body">

        <!-- Budget range -->
        <div class="pcs-pfield" style="margin-bottom:18px;">
          <label class="pcs-pfield-label">Budget range</label>
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:6px;" id="pcs-budget-grid">
            <?php
            $budgets = array( 'under_1k' => 'Under $1K', '1k_5k' => '$1K – $5K', '5k_15k' => '$5K – $15K', '15k_plus' => '$15K+' );
            foreach ( $budgets as $bk => $bl ) : ?>
            <div class="pcs-budget-card" data-budget="<?php echo esc_attr( $bk ); ?>"
                 onclick="pcsSelectBudget('<?php echo esc_js( $bk ); ?>')"
                 style="border:2px solid #e5e7eb;border-radius:8px;padding:10px;text-align:center;cursor:pointer;font-size:13px;font-weight:600;color:#374151;transition:all .15s;">
              <?php echo esc_html( $bl ); ?>
            </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="pcs-qr-budget" value="">
        </div>

        <!-- Design status -->
        <div class="pcs-pfield">
          <label class="pcs-pfield-label">Design status <span style="color:#ef4444;">*</span></label>
          <p style="font-size:11px;color:#9ca3af;margin:2px 0 8px;">Do you have print-ready files?</p>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="pcs-design-grid">
            <div class="pcs-design-card" data-design="have_files"
                 onclick="pcsSelectDesign('have_files')"
                 style="border:2px solid #e5e7eb;border-radius:10px;padding:14px 12px;cursor:pointer;transition:all .15s;">
              <div style="font-size:16px;margin-bottom:4px;">✅</div>
              <div style="font-size:13px;font-weight:700;color:#111827;">Have files</div>
              <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Print-ready</div>
            </div>
            <div class="pcs-design-card" data-design="draft_only"
                 onclick="pcsSelectDesign('draft_only')"
                 style="border:2px solid #e5e7eb;border-radius:10px;padding:14px 12px;cursor:pointer;transition:all .15s;">
              <div style="font-size:16px;margin-bottom:4px;">📄</div>
              <div style="font-size:13px;font-weight:700;color:#111827;">Draft only</div>
              <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Need help finalizing</div>
            </div>
            <div class="pcs-design-card" data-design="need_design"
                 onclick="pcsSelectDesign('need_design')"
                 style="border:2px solid #e5e7eb;border-radius:10px;padding:14px 12px;cursor:pointer;transition:all .15s;">
              <div style="font-size:16px;margin-bottom:4px;">🎨</div>
              <div style="font-size:13px;font-weight:700;color:#111827;">Need design</div>
              <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Design service +$$</div>
            </div>
          </div>
          <input type="hidden" id="pcs-qr-design" value="">
        </div>

      </div>
    </div>

    <!-- Submit -->
    <div style="background:#eff6ff;border-left:3px solid #2563eb;padding:10px 14px;border-radius:0 6px 6px 0;font-size:12px;color:#1e3a8a;margin-bottom:16px;">
      ℹ️ We'll review your request and send a detailed quote by email within 24 business hours.
    </div>
    <div style="display:flex;gap:10px;">
      <button id="pcs-qr-submit" onclick="pcsSubmitQuoteRequest()" class="pcs-pbtn pcs-pbtn-primary" style="flex:2;justify-content:center;">
        Submit request →
      </button>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url('quotes') ); ?>" class="pcs-pbtn pcs-pbtn-ghost" style="flex:1;justify-content:center;">
        Cancel
      </a>
    </div>
  </div>

  <!-- RIGHT: How it works -->
  <div>
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">🕐 How it works</h3></div>
      <div class="pcs-pcard-body">
        <?php
        $steps = array(
            array( '1', '#2563eb', 'Submit your request',  'Takes ~5 min' ),
            array( '2', '#7c3aed', 'We review & quote',    'Within 24 business hours' ),
            array( '3', '#059669', 'Review your quote',    'Accept, decline, or ask questions' ),
            array( '4', '#d97706', 'Production starts',    'After you accept' ),
        );
        foreach ( $steps as $step ) : ?>
        <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;">
          <div style="width:28px;height:28px;border-radius:50%;background:<?php echo esc_attr( $step[1] ); ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">
            <?php echo esc_html( $step[0] ); ?>
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;color:#111827;"><?php echo esc_html( $step[2] ); ?></div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;"><?php echo esc_html( $step[3] ); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="pcs-pcard">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">💬 Need help?</h3></div>
      <div class="pcs-pcard-body" style="font-size:13px;line-height:2;color:#374151;">
        <?php
        $phone = get_option('pcs_support_phone');
        $email = get_option('pcs_admin_email', get_option('admin_email'));
        if ( $phone ) echo '<div>📞 ' . esc_html( $phone ) . '</div>';
        ?>
        <div>✉️ <a href="mailto:<?php echo esc_attr( $email ); ?>" style="color:#2563eb;"><?php echo esc_html( $email ); ?></a></div>
        <div style="margin-top:10px;font-size:12px;color:#9ca3af;">Mon–Fri, 9 AM – 5 PM</div>
      </div>
    </div>
  </div>

</div>

<style>
.pcs-project-type-card:hover,
.pcs-design-card:hover { border-color:#2563eb !important; background:#eff6ff; }
.pcs-project-type-card.selected { border-color:#2563eb !important; background:#eff6ff; }
.pcs-project-type-card.selected div:first-child + div { color:#2563eb !important; }
.pcs-budget-card:hover { border-color:#2563eb !important; background:#eff6ff; color:#2563eb; }
</style>

<script>
(function($) {
    window.pcsSelectType = function(type) {
        $('.pcs-project-type-card').removeClass('selected');
        $('.pcs-project-type-card[data-type="' + type + '"]').addClass('selected');
        $('#pcs-qr-type').val(type);
    };
    window.pcsSelectBudget = function(b) {
        $('.pcs-budget-card').css({'border-color':'#e5e7eb','background':'#fff','color':'#374151'});
        $('.pcs-budget-card[data-budget="' + b + '"]').css({'border-color':'#2563eb','background':'#eff6ff','color':'#2563eb'});
        $('#pcs-qr-budget').val(b);
    };
    window.pcsSelectDesign = function(d) {
        $('.pcs-design-card').css({'border-color':'#e5e7eb','background':'#fff'});
        $('.pcs-design-card[data-design="' + d + '"]').css({'border-color':'#2563eb','background':'#eff6ff'});
        $('#pcs-qr-design').val(d);
    };

    window.pcsSubmitQuoteRequest = function() {
        var type     = $('#pcs-qr-type').val();
        var title    = $('#pcs-qr-title').val().trim();
        var category = $('#pcs-qr-category').val();
        var qty      = $('#pcs-qr-quantity').val();
        var message  = $('#pcs-qr-message').val().trim();
        var design   = $('#pcs-qr-design').val();

        if (!type)    { $('#pcs-qr-error').text('Please select a project type.').show(); return; }
        if (!title)   { $('#pcs-qr-error').text('Project name is required.').show(); return; }
        if (!category){ $('#pcs-qr-error').text('Please select a product category.').show(); return; }
        if (!message) { $('#pcs-qr-error').text('Please describe your specifications.').show(); return; }
        if (!design)  { $('#pcs-qr-error').text('Please select your design status.').show(); return; }

        $('#pcs-qr-error').hide();
        var $btn = $('#pcs-qr-submit').prop('disabled', true).text('Submitting…');

        var budgetLabels = { 'under_1k':'Under $1K', '1k_5k':'$1K–$5K', '5k_15k':'$5K–$15K', '15k_plus':'$15K+' };
        var designLabels = { 'have_files':'Have files (print-ready)', 'draft_only':'Draft only (need help finalizing)', 'need_design':'Need design service' };

        /* Build full message with all details */
        var fullMsg = 'Project type: ' + type + '\n'
                    + 'Category: ' + category + '\n'
                    + (qty ? 'Quantity: ' + qty + '\n' : '')
                    + (($('#pcs-qr-deadline').val()) ? 'Deadline: ' + $('#pcs-qr-deadline').val() + '\n' : '')
                    + ($('#pcs-qr-budget').val() ? 'Budget range: ' + (budgetLabels[$('#pcs-qr-budget').val()] || '') + '\n' : '')
                    + 'Design status: ' + (designLabels[design] || design) + '\n'
                    + '\nSpecifications:\n' + message;

        $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
            action:  'pcs_submit_quote_request',
            _nonce:  (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
            title:   title,
            message: fullMsg,
        }, function(res) {
            $btn.prop('disabled', false).text('Submit request →');
            if (res.success) {
                $('#pcs-qr-success').show();
                $('html, body').animate({ scrollTop: 0 }, 300);
                setTimeout(function() {
                    location.href = <?php echo wp_json_encode( PCS_B2B_Portal::url('quotes') ); ?>;
                }, 2000);
            } else {
                $('#pcs-qr-error').text(res.data && res.data.message ? res.data.message : 'Error submitting.').show();
            }
        });
    };
})(jQuery);
</script>
