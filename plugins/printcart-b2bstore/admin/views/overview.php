<?php if ( ! defined( 'ABSPATH' ) ) exit;

/* ---- Data gathering ---- */
$store_stats  = PCS_DB::get_store_stats();
$inv_stats    = PCS_DB::get_invitation_stats();
$tier_counts  = PCS_DB::get_tier_counts();
$quote_stats  = PCS_Quotes::get_stats();
$all_tiers    = PCS_DB::get_tiers_from_rules();

/* B2B totals */
$b2b_total    = 0;
foreach ( $tier_counts as $tk => $cnt ) {
    if ( isset( $all_tiers[$tk] ) && (float)($all_tiers[$tk]['discount'] ?? 0) > 0 ) {
        $b2b_total += (int) $cnt;
    }
}
$pending_apps    = (int) $store_stats['pending'];
$pending_invites = (int) $inv_stats['pending'];
$pending_quotes  = (int) $quote_stats['new_request'];
$total_pending   = $pending_apps + $pending_invites + $pending_quotes;

/* WC data */
$revenue_30d   = 0;
$active_orders = 0;
$recent_orders = array();
if ( function_exists( 'wc_get_orders' ) ) {
    $date_from = date( 'Y-m-d', strtotime( '-30 days' ) );
    $all_30d   = wc_get_orders( array(
        'status'     => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
        'date_after' => $date_from,
        'limit'      => -1,
        'return'     => 'ids',
    ) );
    foreach ( $all_30d as $oid ) {
        $o = wc_get_order( $oid );
        if ( ! $o ) continue;
        $revenue_30d += (float) $o->get_total();
        if ( in_array( $o->get_status(), array( 'processing', 'on-hold' ), true ) ) {
            $active_orders++;
        }
    }
    $recent_orders = wc_get_orders( array( 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC' ) );
}

/* Revenue trend — last 30 days split B2C vs B2B */
$chart_data = array();
$b2c_total_chart = 0;
$b2b_total_chart = 0;
for ( $i = 29; $i >= 0; $i-- ) {
    $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
    $chart_data[ $date ] = array( 'b2c' => 0, 'b2b' => 0, 'label' => date( 'M j', strtotime( "-{$i} days" ) ) );
}
if ( function_exists( 'wc_get_orders' ) ) {
    $chart_orders = wc_get_orders( array(
        'status'     => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
        'date_after' => date( 'Y-m-d', strtotime( '-30 days' ) ),
        'limit'      => -1,
    ) );
    foreach ( $chart_orders as $co ) {
        $d = $co->get_date_created() ? $co->get_date_created()->format( 'Y-m-d' ) : '';
        if ( ! isset( $chart_data[ $d ] ) ) continue;
        $cid   = $co->get_customer_id();
        $is_b2b = false;
        if ( $cid ) {
            $ctier = PCS_DB::get_customer_tier( $cid );
            $is_b2b = isset( $all_tiers[ $ctier ] ) && (float)( $all_tiers[ $ctier ]['discount'] ?? 0 ) > 0;
        }
        $amt = (float) $co->get_total();
        if ( $is_b2b ) { $chart_data[ $d ]['b2b'] += $amt; $b2b_total_chart += $amt; }
        else           { $chart_data[ $d ]['b2c'] += $amt; $b2c_total_chart += $amt; }
    }
}
$chart_labels = array_column( $chart_data, 'label' );
$chart_b2c    = array_values( array_column( $chart_data, 'b2c' ) );
$chart_b2b    = array_values( array_column( $chart_data, 'b2b' ) );

/* Recent activity: tier history + recent B2B applications */
$tier_history = array();
global $wpdb;
$tier_history = $wpdb->get_results(
    "SELECT h.*, u.display_name FROM {$wpdb->prefix}pcs_tier_history h
     LEFT JOIN {$wpdb->users} u ON u.ID = h.user_id
     ORDER BY h.changed_at DESC LIMIT 6"
);
$recent_stores = PCS_DB::get_stores( array( 'orderby' => 'created_at', 'order' => 'DESC', 'per_page' => 4 ) );

$current_user = wp_get_current_user();

$status_labels = array(
    'completed'  => array( 'Delivered',    '#dcfce7', '#065f46' ),
    'processing' => array( 'Production',   '#fef3c7', '#92400e' ),
    'on-hold'    => array( 'On hold',      '#fef3c7', '#92400e' ),
    'shipped'    => array( 'Shipped',      '#dbeafe', '#1e40af' ),
    'pending'    => array( 'Pending',      '#f3f4f6', '#6b7280' ),
    'cancelled'  => array( 'Cancelled',    '#fee2e2', '#991b1b' ),
);
?>
<div class="pcs-wrap" style="max-width:none;">

  <!-- ===== WELCOME BANNER ===== -->
  <div style="background:linear-gradient(135deg,#1e3a8a,#2563eb);border-radius:12px;padding:22px 28px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:16px;">
      <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:10px;display:grid;place-items:center;font-size:22px;flex-shrink:0;">🏪</div>
      <div>
        <h2 style="margin:0 0 4px;font-size:17px;font-weight:700;color:#fff;">
          Welcome back, <?php echo esc_html( $current_user->first_name ?: $current_user->display_name ); ?> 👋
        </h2>
        <div style="font-size:13px;color:rgba(255,255,255,.85);line-height:1.5;">
          <?php
          $parts = array();
          if ( $pending_apps > 0 )    $parts[] = '<strong>' . $pending_apps . ' B2B application' . ($pending_apps > 1 ? 's' : '') . '</strong> awaiting review';
          if ( $pending_invites > 0 ) $parts[] = '<strong>' . $pending_invites . ' invitation' . ($pending_invites > 1 ? 's' : '') . '</strong> pending acceptance';
          if ( $pending_quotes > 0 )  $parts[] = '<strong>' . $pending_quotes . ' quote request' . ($pending_quotes > 1 ? 's' : '') . '</strong> need attention';
          echo $parts ? 'You have ' . implode( ' and ', $parts ) . '.' : 'All clear — no pending actions. 🎉';
          ?>
        </div>
      </div>
    </div>
    <?php if ( $total_pending > 0 ) : ?>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-b2b&status=pending' ) ); ?>"
       style="padding:10px 20px;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;transition:background .15s;"
       onmouseenter="this.style.background='rgba(255,255,255,.3)'" onmouseleave="this.style.background='rgba(255,255,255,.2)'">
      Review now →
    </a>
    <?php endif; ?>
  </div>

  <!-- ===== STATS CARDS ===== -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <?php
    $cards = array(
      array( 'TOTAL REVENUE (30D)', wc_price($revenue_30d), 'WooCommerce orders', '#059669', '💰' ),
      array( 'ACTIVE ORDERS',       (int) $active_orders,   'Processing + on hold','#2563eb', '📦' ),
      array( 'PENDING ACTIONS',     (int) $total_pending,   $pending_apps.'B2B · '.$pending_invites.' invites · '.$pending_quotes.' quotes', $total_pending > 0 ? '#d97706' : '#059669', '⏰' ),
      array( 'B2B STORES ACTIVE',   (int) $store_stats['active'], '+'.($store_stats['pending']).' pending', '#7c3aed', '🏢' ),
    );
    foreach ( $cards as $c ) : ?>
    <div class="pcs-card" style="padding:0;overflow:hidden;">
      <div style="padding:18px 20px 14px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
          <span style="font-size:10px;font-weight:700;color:#9ca3af;letter-spacing:.07em;"><?php echo esc_html( $c[0] ); ?></span>
          <span style="font-size:16px;opacity:.5;"><?php echo $c[4]; ?></span>
        </div>
        <div style="font-size:24px;font-weight:800;color:#111827;line-height:1;margin-bottom:5px;"><?php echo is_string($c[1]) ? wp_kses_post($c[1]) : esc_html($c[1]); ?></div>
        <div style="font-size:11px;color:#9ca3af;"><?php echo esc_html( $c[2] ); ?></div>
      </div>
      <div style="height:3px;background:<?php echo esc_attr($c[3]); ?>;opacity:.3;"></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ===== REVENUE TREND CHART ===== -->
  <div class="pcs-card" style="margin-bottom:24px;">
    <div class="pcs-card-head">
      <div>
        <span class="pcs-card-title">📊 Revenue trend</span>
        <div style="font-size:11px;color:var(--pcs-text-soft);margin-top:2px;">Daily breakdown — last 30 days</div>
      </div>
      <a href="<?php echo esc_url( admin_url('admin.php?page=wc-admin&path=/analytics/revenue') ); ?>" class="pcs-card-action">Full report →</a>
    </div>
    <div class="pcs-card-body">
      <!-- Chart canvas -->
      <div style="position:relative;height:200px;">
        <canvas id="pcs-revenue-chart"></canvas>
      </div>

      <!-- Fallback table (shown if Chart.js fails) -->
      <div id="pcs-chart-fallback" style="display:none;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead><tr style="background:#f8fafc;">
            <th style="padding:8px 12px;text-align:left;color:#9ca3af;font-weight:600;">Type</th>
            <th style="padding:8px 12px;text-align:right;color:#9ca3af;font-weight:600;">Revenue (30d)</th>
          </tr></thead>
          <tbody>
            <tr style="border-bottom:1px solid #f3f4f6;">
              <td style="padding:10px 12px;display:flex;align-items:center;gap:8px;">
                <span style="width:12px;height:12px;border-radius:3px;background:rgba(99,102,241,.25);display:inline-block;"></span>B2C
              </td>
              <td style="padding:10px 12px;text-align:right;font-weight:600;"><?php echo wp_kses_post(wc_price($b2c_total_chart)); ?></td>
            </tr>
            <tr>
              <td style="padding:10px 12px;display:flex;align-items:center;gap:8px;">
                <span style="width:12px;height:12px;border-radius:3px;background:#2563eb;display:inline-block;"></span>B2B
              </td>
              <td style="padding:10px 12px;text-align:right;font-weight:600;color:#2563eb;"><?php echo wp_kses_post(wc_price($b2b_total_chart)); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Legend -->
      <div style="display:flex;gap:20px;margin-top:12px;font-size:12px;color:var(--pcs-text-soft);">
        <span style="display:flex;align-items:center;gap:6px;">
          <span style="width:12px;height:12px;border-radius:3px;background:rgba(99,102,241,.25);display:inline-block;"></span>
          B2C · <?php echo wp_kses_post( wc_price($b2c_total_chart) ); ?>
        </span>
        <span style="display:flex;align-items:center;gap:6px;">
          <span style="width:12px;height:12px;border-radius:3px;background:#2563eb;display:inline-block;"></span>
          B2B · <?php echo wp_kses_post( wc_price($b2b_total_chart) ); ?>
        </span>
      </div>
    </div>
  </div>

  <!-- ===== MAIN GRID ===== -->
  <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    <!-- LEFT: Recent orders + Recent stores -->
    <div>

      <!-- Recent orders -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head">
          <span class="pcs-card-title">📋 Recent orders</span>
          <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>" class="pcs-card-action">View all →</a>
        </div>
        <div class="pcs-card-body p0">
          <?php if ( empty( $recent_orders ) ) : ?>
            <div style="padding:28px;text-align:center;color:var(--pcs-text-soft);">No orders yet.</div>
          <?php else : ?>
          <table class="pcs-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Status</th>
                <th style="text-align:right;">Total</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ( $recent_orders as $order ) :
              $cust_id   = $order->get_customer_id();
              $cust_name = $cust_id ? get_userdata($cust_id)->display_name : $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
              $cust_tier = $cust_id ? PCS_DB::get_customer_tier($cust_id) : 'b2c';
              $cust_store = $cust_id ? PCS_DB::get_store_by_user($cust_id) : null;
              $sl = $status_labels[$order->get_status()] ?? array(ucfirst($order->get_status()),'#f3f4f6','#374151');
              $initials = PCS_Customers::get_initials(trim($cust_name));
              $av_colors = array('b2b_a'=>'#2563eb','b2b_b'=>'#d97706','b2b_c'=>'#7c3aed','vip'=>'#d97706');
              $av_color = $av_colors[$cust_tier] ?? '#6b7280';
            ?>
            <tr>
              <td style="font-weight:700;color:var(--pcs-brand);">
                <a href="<?php echo esc_url( get_edit_post_link($order->get_id()) ); ?>" style="color:var(--pcs-brand);">#<?php echo (int) $order->get_id(); ?></a>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,<?php echo esc_attr($av_color); ?>,<?php echo esc_attr($av_color); ?>bb);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;">
                    <?php echo esc_html($initials); ?>
                  </div>
                  <div>
                    <div style="font-size:13px;font-weight:600;"><?php echo esc_html(trim($cust_name)); ?></div>
                    <?php if ($cust_store) : ?><div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html($cust_store->company_name); ?></div><?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:9999px;background:<?php echo esc_attr($sl[1]); ?>;color:<?php echo esc_attr($sl[2]); ?>;">
                  <span style="width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0;"></span>
                  <?php echo esc_html($sl[0]); ?>
                </span>
              </td>
              <td style="text-align:right;font-weight:700;"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent B2B stores -->
      <?php if ( ! empty( $recent_stores ) ) : ?>
      <div class="pcs-card">
        <div class="pcs-card-head">
          <span class="pcs-card-title">🏢 Recent B2B stores</span>
          <a href="<?php echo esc_url( admin_url('admin.php?page=printcart-stores-b2b') ); ?>" class="pcs-card-action">View all →</a>
        </div>
        <div class="pcs-card-body p0">
          <?php foreach ( $recent_stores as $s ) :
            $tier_def = $all_tiers[$s->tier] ?? array('label'=>$s->tier,'discount'=>0);
          ?>
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f3f4f6;cursor:pointer;"
               onclick="location.href='<?php echo esc_url(admin_url('admin.php?page=printcart-stores-b2b&action=view&id='.$s->id)); ?>'">
            <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
              <?php echo esc_html(strtoupper(substr($s->company_name,0,2))); ?>
            </div>
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html($s->company_name); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html($s->industry ?: $s->contact_email); ?></div>
            </div>
            <?php echo PCS_DB::tier_pill_html($s->tier, $tier_def['label']); ?>
            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:9999px;background:<?php echo $s->status === 'active' ? '#dcfce7' : '#fef3c7'; ?>;color:<?php echo $s->status === 'active' ? '#065f46' : '#92400e'; ?>;">
              <?php echo esc_html(ucfirst($s->status)); ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /left -->

    <!-- RIGHT: Pending actions + Activity -->
    <div>

      <!-- Pending actions -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head">
          <span class="pcs-card-title">⚠️ Pending actions</span>
          <?php if ($total_pending > 0) : ?>
            <span style="background:#ef4444;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:9999px;"><?php echo $total_pending; ?></span>
          <?php endif; ?>
        </div>
        <div class="pcs-card-body p0">
          <?php
          $actions = array(
            array( $pending_apps,    'B2B applications',    'Awaiting your review',   admin_url('admin.php?page=printcart-stores-b2b&status=pending'),    '#f59e0b' ),
            array( $pending_invites, 'Invitations sent',    'Awaiting acceptance',    admin_url('admin.php?page=printcart-stores-invitations'),            '#6366f1' ),
            array( $pending_quotes,  'Quote requests',      'New · needs response',   admin_url('admin.php?page=printcart-quotes&status=new_request'),     '#2563eb' ),
          );
          $any = false;
          foreach ( $actions as $a ) :
            if ( $a[0] <= 0 ) continue;
            $any = true;
          ?>
          <a href="<?php echo esc_url($a[3]); ?>" style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #f3f4f6;text-decoration:none;transition:background .1s;"
             onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='transparent'">
            <div style="width:36px;height:36px;border-radius:8px;background:<?php echo esc_attr($a[4]); ?>;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:#fff;flex-shrink:0;">
              <?php echo (int) $a[0]; ?>
            </div>
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html($a[1]); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html($a[2]); ?></div>
            </div>
            <span style="color:#9ca3af;font-size:16px;">›</span>
          </a>
          <?php endforeach;
          if ( ! $any ) : ?>
          <div style="padding:24px;text-align:center;color:var(--pcs-text-soft);font-size:13px;">
            ✅ No pending actions
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent activity -->
      <?php if ( ! empty( $tier_history ) || ! empty( $recent_stores ) ) : ?>
      <div class="pcs-card">
        <div class="pcs-card-head"><span class="pcs-card-title">🕐 Recent activity</span></div>
        <div class="pcs-card-body p0">
          <?php foreach ( $tier_history as $h ) :
            $name = $h->display_name ?: '#'.$h->user_id;
          ?>
          <div style="display:flex;gap:10px;padding:10px 16px;border-bottom:1px solid #f9fafb;font-size:12px;">
            <div style="width:6px;height:6px;border-radius:50%;background:#2563eb;flex-shrink:0;margin-top:4px;"></div>
            <div>
              <div style="color:#111827;"><strong><?php echo esc_html($name); ?></strong> tier changed <strong><?php echo esc_html($h->old_tier ?: 'new'); ?> → <?php echo esc_html($h->new_tier); ?></strong></div>
              <div style="color:#9ca3af;margin-top:2px;"><?php echo esc_html(human_time_diff(strtotime($h->changed_at), time()) . ' ago'); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php foreach ( array_slice($recent_stores, 0, 3) as $s ) : ?>
          <div style="display:flex;gap:10px;padding:10px 16px;border-bottom:1px solid #f9fafb;font-size:12px;">
            <div style="width:6px;height:6px;border-radius:50%;background:#059669;flex-shrink:0;margin-top:4px;"></div>
            <div>
              <div style="color:#111827;"><strong><?php echo esc_html($s->company_name); ?></strong> applied for B2B</div>
              <div style="color:#9ca3af;margin-top:2px;"><?php echo esc_html(human_time_diff(strtotime($s->created_at), time()) . ' ago'); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /right -->
  </div>

</div>

<script src="<?php echo esc_url( PCS_PLUGIN_URL . 'admin/js/chart.min.js' ); ?>"></script>
<script>
(function() {
    var ctx = document.getElementById('pcs-revenue-chart');
    if (!ctx) return;

    /* Fallback: if Chart.js failed to load, show table instead */
    if (typeof Chart === 'undefined') {
        document.getElementById('pcs-chart-fallback').style.display = 'block';
        ctx.parentElement.style.display = 'none';
        return;
    }

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo wp_json_encode( $chart_labels ); ?>,
            datasets: [
                {
                    label: 'B2C',
                    data: <?php echo wp_json_encode( $chart_b2c ); ?>,
                    backgroundColor: 'rgba(99,102,241,0.2)',
                    borderWidth: 0,
                    borderRadius: 3,
                    stack: 'revenue',
                },
                {
                    label: 'B2B',
                    data: <?php echo wp_json_encode( $chart_b2b ); ?>,
                    backgroundColor: '#2563eb',
                    borderWidth: 0,
                    borderRadius: 3,
                    stack: 'revenue',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(c) { return c.dataset.label + ': $' + c.parsed.y.toFixed(2); }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font:{ size:10 }, color:'#9ca3af', maxRotation:0,
                             maxTicksLimit: 10 }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color:'#f3f4f6' },
                    ticks: { font:{ size:10 }, color:'#9ca3af',
                             callback: function(v){ return '$'+v; } }
                }
            }
        }
    });
})();
</script>
