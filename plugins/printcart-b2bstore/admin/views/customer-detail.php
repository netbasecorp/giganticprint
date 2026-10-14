<?php if ( ! defined( 'ABSPATH' ) ) exit;

$user_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$customer = $user_id ? get_userdata( $user_id ) : null;

if ( ! $customer ) {
    echo '<div class="pcs-wrap"><p>Customer not found. <a href="' . esc_url( admin_url( 'admin.php?page=printcart-stores-customers' ) ) . '">← Back</a></p></div>';
    return;
}

$back_url    = admin_url( 'admin.php?page=printcart-stores-customers' );
$tiers       = PCS_DB::get_tiers_from_rules();
$tier        = PCS_DB::get_customer_tier( $user_id );
$tier_def    = $tiers[ $tier ] ?? array( 'label' => 'B2C', 'discount' => 0 );
$tier_style  = PCS_DB::get_tier_style( $tier );
$store       = PCS_DB::get_store_by_user( $user_id );
$total_spent = (float) wc_get_customer_total_spent( $user_id );
$order_count = wc_get_customer_order_count( $user_id );
$avg_order   = $order_count > 0 ? $total_spent / $order_count : 0;
$initials    = PCS_Customers::get_initials( $customer->display_name );
$phone       = get_user_meta( $user_id, 'billing_phone', true );
$joined      = date_i18n( 'M j, Y', strtotime( $customer->user_registered ) );

/* Recent orders */
$recent_orders = wc_get_orders( array( 'customer' => $user_id, 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC' ) );

/* Next tier calculation */
$b2b_tiers    = array_filter( $tiers, fn($t) => (float)($t['discount'] ?? 0) > 0 );
$next_tier_key = null;
$next_tier_def = null;
$next_threshold = 0;
foreach ( $b2b_tiers as $tk => $td ) {
    $threshold = (float) get_option( 'pcs_tier_threshold_' . $tk, $td['min_spend_upgrade'] ?? 0 );
    if ( $threshold > 0 && $total_spent < $threshold ) {
        $curr_disc = (float)( $tier_def['discount'] ?? 0 );
        $cand_disc = (float)( $td['discount'] ?? 0 );
        if ( $cand_disc > $curr_disc ) {
            if ( ! $next_threshold || $threshold < $next_threshold ) {
                $next_threshold = $threshold;
                $next_tier_key  = $tk;
                $next_tier_def  = $td;
            }
        }
    }
}
$progress_pct = ( $next_threshold > 0 && $total_spent > 0 ) ? min( 100, round( $total_spent / $next_threshold * 100 ) ) : 0;
?>
<div class="pcs-wrap">

  <!-- Header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <a href="<?php echo esc_url( $back_url ); ?>" style="font-size:13px;color:rgba(255,255,255,.65);text-decoration:none;display:block;margin-bottom:6px;">← Customers</a>
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;">
          <?php echo esc_html( $initials ); ?>
        </div>
        <div>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <h1 style="margin:0;font-size:22px;color:#fff;font-weight:800;"><?php echo esc_html( $customer->display_name ); ?></h1>
            <?php echo PCS_DB::tier_pill_html( $tier, $tier_def['label'] ); ?>
          </div>
          <div style="font-size:13px;color:rgba(255,255,255,.7);margin-top:3px;">
            <?php echo esc_html( $customer->user_email ); ?>
            <?php if ( $phone ) echo ' · ' . esc_html( $phone ); ?>
            <?php if ( $store ) echo ' · <strong style="color:rgba(255,255,255,.9);">' . esc_html( $store->company_name ) . '</strong>'; ?>
            · Joined <?php echo esc_html( $joined ); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="pcs-page-actions">
      <a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>" class="pcs-btn pcs-btn-secondary">✏️ WP Edit</a>
      <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order&_customer_user=' . $user_id ) ); ?>" class="pcs-btn pcs-btn-secondary">📦 Orders</a>
      <?php if ( $store ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-b2b&action=view&id=' . $store->id ) ); ?>" class="pcs-btn pcs-btn-primary">🏢 B2B Store →</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <?php
    $stats = array(
      array('TOTAL SPENT',   strip_tags(wc_price($total_spent)), 'All time',          '💰','#059669'),
      array('TOTAL ORDERS',  (int)$order_count,                  'All time',          '📦','#2563eb'),
      array('AVG ORDER',     $order_count > 0 ? strip_tags(wc_price($avg_order)) : '—', 'Per order', '📊','#7c3aed'),
    );
    foreach ($stats as $s) : ?>
    <div class="pcs-card" style="padding:0;overflow:hidden;">
      <div style="padding:20px 20px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
          <span style="font-size:11px;font-weight:700;color:#9ca3af;letter-spacing:.06em;"><?php echo esc_html($s[0]); ?></span>
          <span style="font-size:18px;opacity:.5;"><?php echo $s[4] === '#059669' ? '💰' : ($s[4] === '#2563eb' ? '📦' : '📊'); ?></span>
        </div>
        <div style="font-size:26px;font-weight:800;color:#111827;line-height:1;margin-bottom:4px;"><?php echo $s[1]; ?></div>
        <div style="font-size:12px;color:#9ca3af;"><?php echo esc_html($s[2]); ?></div>
      </div>
      <div style="height:3px;background:<?php echo esc_attr($s[4]); ?>;opacity:.25;"></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    <!-- LEFT -->
    <div>

      <!-- Contact information -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head is-brand"><span class="pcs-card-title">👤 Contact information</span></div>
        <div class="pcs-card-body">
          <?php
          $fields = array(
            'Full name'  => $customer->first_name . ' ' . $customer->last_name,
            'Email'      => $customer->user_email,
            'Phone'      => $phone ?: '—',
            'Username'   => $customer->user_login,
            'Joined'     => date_i18n( 'M j, Y · g:i A', strtotime( $customer->user_registered ) ),
          );
          foreach ($fields as $label => $value) : ?>
          <div style="display:grid;grid-template-columns:140px 1fr;gap:12px;padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <span style="color:var(--pcs-text-soft);font-weight:500;"><?php echo esc_html($label); ?></span>
            <span style="color:#111827;"><?php echo esc_html($value); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- B2B Store info -->
      <?php if ( $store ) : ?>
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head">
          <span class="pcs-card-title">🏢 Company / B2B Store</span>
          <a href="<?php echo esc_url( admin_url('admin.php?page=printcart-stores-b2b&action=view&id='.$store->id) ); ?>" class="pcs-card-action">View store profile →</a>
        </div>
        <div class="pcs-card-body">
          <?php
          $store_fields = array(
            'Company name' => $store->company_name,
            'Industry'     => $store->industry ?: '—',
            'Website'      => $store->website  ?: '—',
            'Status'       => ucfirst($store->status),
          );
          foreach ($store_fields as $label => $value) : ?>
          <div style="display:grid;grid-template-columns:140px 1fr;gap:12px;padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <span style="color:var(--pcs-text-soft);font-weight:500;"><?php echo esc_html($label); ?></span>
            <span style="color:#111827;"><?php echo esc_html($value); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Recent orders -->
      <?php if ( ! empty( $recent_orders ) ) : ?>
      <div class="pcs-card">
        <div class="pcs-card-head">
          <span class="pcs-card-title">📦 Recent orders</span>
          <a href="<?php echo esc_url( admin_url('edit.php?post_type=shop_order&_customer_user='.$user_id) ); ?>" class="pcs-card-action">View all →</a>
        </div>
        <div class="pcs-card-body p0">
          <?php
          $sl_map = array(
            'completed'  => array('#dcfce7','#065f46','Delivered'),
            'processing' => array('#fef3c7','#92400e','In production'),
            'on-hold'    => array('#fef3c7','#92400e','On hold'),
          );
          foreach ($recent_orders as $order) :
            $sl = $sl_map[$order->get_status()] ?? array('#f3f4f6','#374151',ucfirst($order->get_status()));
            $items = $order->get_items();
            $first = reset($items);
            $pname = $first ? $first->get_name() : '#'.$order->get_id();
            $extra = count($items) > 1 ? ' +' . (count($items)-1) : '';
          ?>
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <div style="flex:1;">
              <div style="font-weight:600;color:#111827;">Order #<?php echo (int)$order->get_id(); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html($pname.$extra); ?> · <?php echo esc_html($order->get_date_created()->date_i18n('M j, Y')); ?></div>
            </div>
            <span style="font-size:11px;padding:2px 8px;border-radius:9999px;background:<?php echo esc_attr($sl[0]); ?>;color:<?php echo esc_attr($sl[1]); ?>;font-weight:600;"><?php echo esc_html($sl[2]); ?></span>
            <span style="font-weight:700;"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /left -->

    <!-- RIGHT sidebar -->
    <div>

      <!-- Tier & rewards -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
          <span class="pcs-card-title">⭐ Tier & rewards</span>
        </div>
        <div class="pcs-card-body">
          <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;letter-spacing:.05em;margin-bottom:6px;">Current tier</div>
            <?php echo PCS_DB::tier_pill_html( $tier, $tier_def['label'] ); ?>
            <?php if ($tier_def['discount'] > 0) : ?>
              <div style="font-size:12px;color:var(--pcs-text-soft);margin-top:6px;"><?php echo (int)$tier_def['discount']; ?>% off all products</div>
            <?php endif; ?>
          </div>

          <?php if ( $next_tier_key && $next_threshold > 0 ) : ?>
          <div style="margin-bottom:16px;padding-top:14px;border-top:1px solid #f3f4f6;">
            <div style="font-size:12px;color:var(--pcs-text-soft);margin-bottom:6px;">
              Progress to <strong><?php echo esc_html($next_tier_def['label']); ?></strong>
            </div>
            <div style="background:#e5e7eb;height:8px;border-radius:9999px;overflow:hidden;margin-bottom:6px;">
              <div style="background:linear-gradient(90deg,#2563eb,#60a5fa);height:100%;width:<?php echo $progress_pct; ?>%;border-radius:9999px;transition:width .3s;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#9ca3af;">
              <span><?php echo wp_kses_post(wc_price($total_spent)); ?> spent</span>
              <span><?php echo wp_kses_post(wc_price($next_threshold)); ?> needed</span>
            </div>
            <div style="font-size:12px;color:#2563eb;font-weight:600;margin-top:4px;text-align:center;">
              <?php echo wp_kses_post(wc_price(max(0, $next_threshold - $total_spent))); ?> more to unlock <?php echo (int)$next_tier_def['discount']; ?>% off
            </div>
          </div>
          <?php endif; ?>

          <!-- Manual tier change -->
          <div style="padding-top:12px;border-top:1px solid #f3f4f6;">
            <div style="font-size:12px;font-weight:600;color:var(--pcs-text-soft);margin-bottom:8px;">Manually set tier</div>
            <div style="display:flex;gap:8px;">
              <select id="pcs-manual-tier-<?php echo (int)$user_id; ?>" class="pcs-select" style="flex:1;font-size:12px;">
                <?php foreach ($tiers as $tk => $td) : ?>
                  <option value="<?php echo esc_attr($tk); ?>" <?php selected($tk, $tier); ?>><?php echo esc_html($td['label']); ?></option>
                <?php endforeach; ?>
              </select>
              <button onclick="pcsUpdateCustomerTier(<?php echo (int)$user_id; ?>)" class="pcs-btn pcs-btn-primary pcs-btn-sm">Save</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tier history -->
      <?php
      $history = PCS_DB::get_tier_history( $user_id );
      if ( ! empty( $history ) ) :
      ?>
      <div class="pcs-card">
        <div class="pcs-card-head"><span class="pcs-card-title">📋 Tier history</span></div>
        <div class="pcs-card-body p0">
          <?php foreach ( array_slice($history, 0, 5) as $h ) : ?>
          <div style="padding:10px 16px;border-bottom:1px solid #f9fafb;font-size:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span><strong><?php echo esc_html($h->old_tier ?: 'new'); ?></strong> → <strong><?php echo esc_html($h->new_tier); ?></strong></span>
              <span style="color:var(--pcs-text-soft);"><?php echo esc_html(human_time_diff(strtotime($h->changed_at), time()) . ' ago'); ?></span>
            </div>
            <?php if ($h->note) : ?>
              <div style="color:var(--pcs-text-soft);margin-top:2px;"><?php echo esc_html($h->note); ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /right -->
  </div>

</div>

<script>
(function($) {
    window.pcsUpdateCustomerTier = function(userId) {
        var tier = $('#pcs-manual-tier-' + userId).val();
        pcsAjax('pcs_update_customer_tier', { user_id: userId, tier: tier, note: 'Manual update from customer profile' }, function(d) {
            pcsToast(d.message || 'Tier updated.', 'success');
            setTimeout(function(){ location.reload(); }, 800);
        });
    };
})(jQuery);
</script>
