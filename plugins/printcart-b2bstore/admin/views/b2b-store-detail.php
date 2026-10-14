<?php if ( ! defined( 'ABSPATH' ) ) exit;

$store_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$store    = $store_id ? PCS_DB::get_store( $store_id ) : null;

if ( ! $store ) {
    echo '<div class="pcs-wrap"><p>Store not found. <a href="' . esc_url( admin_url( 'admin.php?page=printcart-stores-b2b' ) ) . '">← Back</a></p></div>';
    return;
}

$back_url   = admin_url( 'admin.php?page=printcart-stores-b2b' );
$owner      = $store->user_id ? get_userdata( $store->user_id ) : null;
$members    = PCS_DB::get_members( $store_id );
$owner_id   = (int) $store->user_id;
$team       = array_filter( $members, function($m) use ($owner_id) { return (int)$m->user_id !== $owner_id; } );
$tiers      = PCS_DB::get_tiers_from_rules();
$tier_def   = isset( $tiers[ $store->tier ] ) ? $tiers[ $store->tier ] : array( 'label' => $store->tier, 'discount' => 0 );
$order_count = $owner ? wc_get_customer_order_count( $owner->ID ) : 0;
$total_spent = $owner ? (float) wc_get_customer_total_spent( $owner->ID ) : 0;

/* Recent orders */
$recent_orders = $owner ? wc_get_orders( array( 'customer' => $owner->ID, 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC' ) ) : array();

$pt_map = array( 'prepay' => 'Credit card (pre-pay)', 'net15' => 'Net-15', 'net30' => 'Net-30' );
$status_colors = array(
    'pending'  => array( '#fef3c7', '#92400e' ),
    'active'   => array( '#dcfce7', '#065f46' ),
    'rejected' => array( '#fee2e2', '#991b1b' ),
);
$sc = $status_colors[ $store->status ] ?? array( '#f3f4f6', '#374151' );
?>
<div class="pcs-wrap">

  <!-- Header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <a href="<?php echo esc_url( $back_url ); ?>" style="font-size:13px;color:rgba(255,255,255,.65);text-decoration:none;display:block;margin-bottom:6px;">← Back to B2B Stores</a>
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;">
          <?php echo esc_html( strtoupper( substr( $store->company_name, 0, 2 ) ) ); ?>
        </div>
        <div>
          <h1 style="margin:0;font-size:22px;color:#fff;font-weight:800;"><?php echo esc_html( $store->company_name ); ?></h1>
          <div style="font-size:13px;color:rgba(255,255,255,.7);margin-top:2px;"><?php echo esc_html( $store->industry ?: '—' ); ?> · <?php echo esc_html( $store->contact_email ); ?></div>
        </div>
      </div>
    </div>
    <div class="pcs-page-actions">
      <span style="font-size:12px;font-weight:600;padding:5px 14px;border-radius:9999px;background:<?php echo esc_attr($sc[0]); ?>;color:<?php echo esc_attr($sc[1]); ?>;">
        <?php echo esc_html( ucfirst( $store->status ) ); ?>
      </span>
      <?php if ( 'pending' === $store->status ) : ?>
        <button class="pcs-btn pcs-btn-primary pcs-btn-approve-store"
                data-store-id="<?php echo (int) $store->id; ?>"
                data-store-tier="<?php echo esc_attr( $store->tier ); ?>"
                data-store-payment="<?php echo esc_attr( $store->payment_terms ); ?>"
                data-store-credit="0"
                data-store-team="<?php echo esc_attr( $store->team_size ); ?>">
          ✅ Approve
        </button>
        <button class="pcs-btn pcs-btn-danger pcs-btn-sm pcs-btn-reject-store" data-store-id="<?php echo (int) $store->id; ?>">Reject</button>
      <?php else : ?>
        <button class="pcs-btn pcs-btn-secondary pcs-btn-approve-store"
                data-store-id="<?php echo (int) $store->id; ?>"
                data-store-tier="<?php echo esc_attr( $store->tier ); ?>"
                data-store-payment="<?php echo esc_attr( $store->payment_terms ); ?>"
                data-store-credit="0"
                data-store-team="<?php echo esc_attr( $store->team_size ); ?>">
          ✏️ Edit store
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    <!-- LEFT -->
    <div>

      <!-- Company information -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head is-brand"><span class="pcs-card-title">🏢 Company information</span></div>
        <div class="pcs-card-body">
          <?php
          $info_rows = array(
            'Company name'   => $store->company_name,
            'Industry'       => $store->industry,
            'Contact name'   => $store->contact_name,
            'Email'          => $store->contact_email,
            'Phone'          => $store->contact_phone,
            'Website'        => $store->website ? '<a href="' . esc_url($store->website) . '" target="_blank" rel="noopener">' . esc_html($store->website) . '</a>' : '—',
            'Monthly volume' => $store->monthly_volume,
            'Applied'        => $store->created_at ? date_i18n( 'M j, Y · g:i A', strtotime( $store->created_at ) ) : '—',
          );
          foreach ( $info_rows as $label => $value ) :
            if ( empty( $value ) ) continue;
          ?>
          <div style="display:grid;grid-template-columns:160px 1fr;gap:12px;padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <span style="color:var(--pcs-text-soft);font-weight:500;"><?php echo esc_html( $label ); ?></span>
            <span style="color:#111827;"><?php echo $label === 'Website' ? $value : esc_html( $value ); ?></span>
          </div>
          <?php endforeach; ?>

          <?php if ( $store->about ) : ?>
          <div style="margin-top:14px;">
            <div style="font-size:11px;font-weight:700;color:var(--pcs-text-soft);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">About / Use case</div>
            <div style="font-size:13px;color:#374151;line-height:1.7;background:#f9fafb;padding:12px 14px;border-radius:8px;"><?php echo nl2br( esc_html( $store->about ) ); ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Team members -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head">
          <span class="pcs-card-title">👥 Team (<?php echo count( $team ) + 1; ?> / <?php echo (int) $store->team_size; ?> seats)</span>
        </div>
        <div class="pcs-card-body p0">
          <!-- Owner -->
          <?php if ( $owner ) : ?>
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f3f4f6;">
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
              <?php echo esc_html( PCS_Customers::get_initials( $owner->display_name ) ); ?>
            </div>
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:600;"><?php echo esc_html( $owner->display_name ); ?> <span style="font-size:10px;background:#e0e7ff;color:#3730a3;padding:1px 6px;border-radius:9999px;font-weight:600;">Owner</span></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $owner->user_email ); ?></div>
            </div>
            <a href="<?php echo esc_url( get_edit_user_link( $owner->ID ) ); ?>" class="pcs-btn pcs-btn-tertiary pcs-btn-sm" style="font-size:11px;">View profile</a>
          </div>
          <?php endif; ?>
          <!-- Members -->
          <?php foreach ( $team as $m ) :
            $mu = get_userdata( $m->user_id );
            if ( ! $mu ) continue;
          ?>
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f3f4f6;">
            <div style="width:36px;height:36px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#374151;flex-shrink:0;">
              <?php echo esc_html( PCS_Customers::get_initials( $mu->display_name ) ); ?>
            </div>
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:600;"><?php echo esc_html( $mu->display_name ); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $mu->user_email ); ?></div>
            </div>
            <span style="font-size:11px;background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:9999px;font-weight:600;"><?php echo esc_html( ucfirst( $m->role ) ); ?></span>
          </div>
          <?php endforeach; ?>
          <?php if ( empty( $team ) && ! $owner ) : ?>
          <div style="padding:20px;text-align:center;color:var(--pcs-text-soft);font-size:13px;">No members yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent orders -->
      <?php if ( ! empty( $recent_orders ) ) : ?>
      <div class="pcs-card">
        <div class="pcs-card-head">
          <span class="pcs-card-title">📦 Recent orders</span>
          <?php if ( $owner ) : ?>
          <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order&_customer_user=' . $owner->ID ) ); ?>" class="pcs-card-action">View all →</a>
          <?php endif; ?>
        </div>
        <div class="pcs-card-body p0">
          <?php
          $status_map = array(
            'completed'  => array('Delivered',   '#dcfce7','#065f46'),
            'processing' => array('In production','#fef3c7','#92400e'),
            'on-hold'    => array('On hold',     '#fef3c7','#92400e'),
          );
          foreach ( $recent_orders as $order ) :
            $sl = $status_map[ $order->get_status() ] ?? array( ucfirst($order->get_status()), '#f3f4f6', '#6b7280' );
          ?>
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <div style="flex:1;">
              <div style="font-weight:600;color:#111827;">Order #<?php echo (int) $order->get_id(); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $order->get_date_created()->date_i18n('M j, Y') ); ?></div>
            </div>
            <span style="font-size:11px;padding:2px 8px;border-radius:9999px;background:<?php echo esc_attr($sl[1]); ?>;color:<?php echo esc_attr($sl[2]); ?>;font-weight:600;"><?php echo esc_html($sl[0]); ?></span>
            <span style="font-weight:700;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /left -->

    <!-- RIGHT sidebar -->
    <div>

      <!-- B2B settings -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head"><span class="pcs-card-title">⚙️ B2B settings</span></div>
        <div class="pcs-card-body" style="font-size:13px;">
          <div style="margin-bottom:12px;">
            <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Tier</div>
            <?php echo PCS_DB::tier_pill_html( $store->tier, $tier_def['label'] ); ?>
            <?php if ( $tier_def['discount'] > 0 ) : ?>
              <div style="font-size:12px;color:var(--pcs-text-soft);margin-top:4px;"><?php echo (int) $tier_def['discount']; ?>% off all products</div>
            <?php endif; ?>
          </div>
          <div style="margin-bottom:12px;padding-top:12px;border-top:1px solid #f3f4f6;">
            <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Payment terms</div>
            <div style="font-weight:600;"><?php echo esc_html( $pt_map[$store->payment_terms] ?? $store->payment_terms ?: '—' ); ?></div>
          </div>
          <div style="padding-top:12px;border-top:1px solid #f3f4f6;">
            <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Team seats</div>
            <div style="font-weight:600;"><?php echo count($team) + 1; ?> / <?php echo (int) $store->team_size; ?></div>
          </div>
        </div>
      </div>

      <!-- Account owner -->
      <?php if ( $owner ) : ?>
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head"><span class="pcs-card-title">👤 Account owner</span></div>
        <div class="pcs-card-body" style="font-size:13px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">
              <?php echo esc_html( PCS_Customers::get_initials( $owner->display_name ) ); ?>
            </div>
            <div>
              <div style="font-weight:700;"><?php echo esc_html( $owner->display_name ); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $owner->user_email ); ?></div>
            </div>
          </div>
          <div style="line-height:1.9;color:var(--pcs-text-soft);">
            <div>📦 <?php echo (int) $order_count; ?> orders all time</div>
            <div>💰 <?php echo wp_kses_post( wc_price( $total_spent ) ); ?> total spent</div>
          </div>
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-customers&s=' . urlencode($owner->user_email) ) ); ?>" class="pcs-btn pcs-btn-secondary" style="width:100%;justify-content:center;margin-top:12px;display:flex;font-size:12px;">
            View customer profile →
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="pcs-card">
        <div class="pcs-card-head"><span class="pcs-card-title">📊 Account stats</span></div>
        <div class="pcs-card-body" style="font-size:13px;line-height:2.2;">
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--pcs-text-soft);">Orders (all time)</span>
            <strong><?php echo (int) $order_count; ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--pcs-text-soft);">Total spent</span>
            <strong><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--pcs-text-soft);">Team members</span>
            <strong><?php echo count($team) + 1; ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--pcs-text-soft);">Member since</span>
            <strong><?php echo esc_html( date_i18n('M j, Y', strtotime($store->created_at)) ); ?></strong>
          </div>
        </div>
      </div>

    </div><!-- /right -->
  </div>

</div>

<!-- ===== Approve / Edit Modal ===== -->
<div class="pcs-modal-backdrop" id="pcs-modal-approve">
  <div class="pcs-modal">
    <div class="pcs-modal-head">
      <h3 class="pcs-modal-title">✏️ Edit B2B Store</h3>
      <button class="pcs-modal-close">×</button>
    </div>
    <div class="pcs-modal-body">
      <input type="hidden" id="pcs-approve-store-id">
      <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
        <label class="pcs-form-label">Tier</label>
        <select id="pcs-approve-tier" class="pcs-select" style="max-width:100%;">
          <?php foreach ( $tiers as $tid => $tdef ) : ?>
            <option value="<?php echo esc_attr( $tid ); ?>"><?php echo esc_html( $tdef['label'] ); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
        <label class="pcs-form-label">Payment terms</label>
        <select id="pcs-approve-payment-terms" class="pcs-select" style="max-width:100%;">
          <option value="prepay">Credit card (pre-pay)</option>
          <option value="net15">Net-15</option>
          <option value="net30">Net-30</option>
        </select>
      </div>
      <input type="hidden" id="pcs-approve-credit-limit" value="0">
      <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;border:none;">
        <label class="pcs-form-label">Team seats</label>
        <input type="number" id="pcs-approve-team-size" class="pcs-input" value="5" style="max-width:80px;">
      </div>
    </div>
    <div class="pcs-modal-foot">
      <button class="pcs-btn pcs-btn-tertiary pcs-modal-close">Cancel</button>
      <button id="pcs-confirm-approve" class="pcs-btn pcs-btn-primary">Save changes →</button>
    </div>
  </div>
</div>

<!-- ===== Reject Modal ===== -->
<div class="pcs-modal-backdrop" id="pcs-modal-reject">
  <div class="pcs-modal" style="max-width:460px;">
    <div class="pcs-modal-head">
      <h3 class="pcs-modal-title">❌ Reject Application</h3>
      <button class="pcs-modal-close">×</button>
    </div>
    <div class="pcs-modal-body">
      <input type="hidden" id="pcs-reject-store-id">
      <p style="color:var(--pcs-text-soft);margin-top:0;">An email will be sent to the applicant notifying them of the decision.</p>
      <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Reason (optional)</label>
      <textarea id="pcs-reject-reason" class="pcs-textarea" placeholder="e.g. Insufficient business documentation…" style="max-width:100%;width:100%;"></textarea>
    </div>
    <div class="pcs-modal-foot">
      <button class="pcs-btn pcs-btn-tertiary pcs-modal-close">Cancel</button>
      <button id="pcs-confirm-reject" class="pcs-btn pcs-btn-danger">Reject application</button>
    </div>
  </div>
</div>
