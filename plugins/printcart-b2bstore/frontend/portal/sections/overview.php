<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var WP_User $user @var int $user_id @var string $tier @var array $tier_def @var bool $is_b2b @var object|null $store @var array $tiers */
$order_count = wc_get_customer_order_count( $user_id );
$total_spent = (float) wc_get_customer_total_spent( $user_id );
$savings     = $tier_def['discount'] > 0
               ? ( $total_spent / ( 1 - $tier_def['discount'] / 100 ) * $tier_def['discount'] / 100 ) : 0;

/* B2B-specific stats */
$members_count = 0;
$store_status  = '';
if ( $store ) {
    $members_count = count( PCS_DB::get_members( $store->id ) ) + 1;
    $store_status  = $store->status;
}

/* Recent 5 orders */
$recent_orders = wc_get_orders( array( 'customer' => $user_id, 'limit' => 5 ) );
?>

<?php
/* Pending team invitations for this user */
$my_pending_invites = PCS_DB::get_pending_invites_for_email( $user->user_email );
if ( ! empty( $my_pending_invites ) ) :
    foreach ( $my_pending_invites as $inv ) :
        $accept_url  = add_query_arg( array( 'pcs_team_invite' => $inv->token, 'pcs_inv_action' => 'accept' ),  home_url( '/' ) );
        $decline_url = add_query_arg( array( 'pcs_team_invite' => $inv->token, 'pcs_inv_action' => 'decline' ), home_url( '/' ) );
?>
<div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:12px;padding:16px 20px;margin:16px 24px 0;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
  <span style="font-size:24px;">📬</span>
  <div style="flex:1;min-width:180px;">
    <div style="font-size:14px;font-weight:700;color:#1e40af;">Team invitation</div>
    <div style="font-size:13px;color:#374151;margin-top:2px;">
      <strong><?php echo esc_html( $inv->company_name ); ?></strong> has invited you to join their B2B workspace as <strong><?php echo esc_html( ucfirst( $inv->role ) ); ?></strong>.
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-shrink:0;">
    <a href="<?php echo esc_url( $accept_url ); ?>"
       style="padding:8px 16px;background:#2563eb;color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;">
      ✓ Accept
    </a>
    <a href="<?php echo esc_url( $decline_url ); ?>"
       style="padding:8px 16px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
      Decline
    </a>
  </div>
</div>
<?php endforeach; endif; ?>

<!-- Page header -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo"><?php echo esc_html( PCS_Customers::get_initials( $user->display_name ) ); ?></div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> · My Portal</div>
        <h1 class="pcs-ph-title">Welcome back, <?php echo esc_html( $user->first_name ?: $user->display_name ); ?> 👋</h1>
        <p class="pcs-ph-sub">Here's your B2B account overview — orders, tier status, and quick actions.</p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-pbtn pcs-pbtn-ghost">Shop</a>
        <?php if ( $is_b2b ) : ?>
          <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'store' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary">B2B Store →</a>
        <?php else : ?>
          <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'apply' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary">Apply for B2B →</a>
        <?php endif; ?>
      </div>
    </div>
    <!-- Stats strip -->
    <div class="pcs-ph-stats">
      <div><div class="pcs-ph-stat-val"><?php echo (int) $order_count; ?></div><div class="pcs-ph-stat-lbl">Total orders</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></div><div class="pcs-ph-stat-lbl">Total spent</div></div>
      <?php if ( $tier_def['discount'] > 0 ) : ?>
      <div><div class="pcs-ph-stat-val"><?php echo wp_kses_post( wc_price( $savings ) ); ?></div><div class="pcs-ph-stat-lbl">Savings YTD</div></div>
      <?php endif; ?>
      <?php if ( $is_b2b && $members_count > 0 ) : ?>
      <div><div class="pcs-ph-stat-val"><?php echo (int) $members_count; ?></div><div class="pcs-ph-stat-lbl">Team members</div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Stats grid -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
  <?php
  $stat_cards = array(
    array( 'label' => 'TOTAL ORDERS', 'value' => (int)$order_count,                                    'delta' => 'All time',              'icon' => '📦', 'color' => '#2563eb' ),
    array( 'label' => 'TOTAL SPENT',  'value' => strip_tags(wc_price($total_spent)),                   'delta' => 'Cumulative',             'icon' => '💰', 'color' => '#059669' ),
    array( 'label' => 'DISCOUNT',     'value' => $tier_def['discount'] > 0 ? $tier_def['discount'].'%' : '—', 'delta' => esc_html($tier_def['label']), 'icon' => '🏷️', 'color' => '#7c3aed' ),
    array( 'label' => 'SAVINGS YTD',  'value' => strip_tags(wc_price($savings)),                       'delta' => 'From tier discount',     'icon' => '⭐', 'color' => '#d97706' ),
  );
  foreach ( $stat_cards as $sc ) : ?>
  <div class="pcs-pcard" style="padding:0;overflow:hidden;">
    <div style="padding:20px 20px 16px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
        <span style="font-size:11px;font-weight:700;color:#9ca3af;letter-spacing:.06em;"><?php echo esc_html( $sc['label'] ); ?></span>
        <span style="font-size:18px;opacity:.6;"><?php echo $sc['icon']; ?></span>
      </div>
      <div style="font-size:26px;font-weight:800;color:#111827;line-height:1;margin-bottom:6px;"><?php echo $sc['value']; ?></div>
      <div style="font-size:12px;color:#9ca3af;"><?php echo $sc['delta']; ?></div>
    </div>
    <div style="height:3px;background:<?php echo esc_attr($sc['color']); ?>;opacity:.25;"></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Two-column: orders + sidebar -->
<div class="pcs-pgrid pcs-pgrid-aside">

  <!-- Recent orders -->
  <div>
    <div class="pcs-pcard">
      <div class="pcs-pcard-head">
        <h3 class="pcs-pcard-title">📋 Recent orders</h3>
        <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'orders' ) ); ?>" class="pcs-pcard-action">View all →</a>
      </div>
      <div class="pcs-pcard-body" style="padding:0;">
        <?php if ( empty( $recent_orders ) ) : ?>
          <div style="padding:40px;text-align:center;color:#6b7280;">No orders yet. <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Start shopping →</a></div>
        <?php else : ?>
          <?php foreach ( $recent_orders as $order ) :
            $status = $order->get_status();
            $items  = $order->get_items();
            $first  = reset( $items );
            $pname  = $first ? $first->get_name() : '#' . $order->get_id();
            $extra  = count( $items ) > 1 ? ' +' . ( count( $items ) - 1 ) : '';
            $status_labels = array(
              'completed'  => array( 'Delivered', '#dcfce7', '#065f46' ),
              'processing' => array( 'In production', '#fef3c7', '#92400e' ),
              'shipped'    => array( 'Shipped', '#dbeafe', '#1e40af' ),
              'on-hold'    => array( 'On hold', '#f3f4f6', '#6b7280' ),
              'cancelled'  => array( 'Cancelled', '#fee2e2', '#991b1b' ),
            );
            $sl = $status_labels[ $status ] ?? array( ucfirst( $status ), '#f3f4f6', '#6b7280' );
          ?>
          <?php $detail_url = add_query_arg( array( 'section' => 'order-detail', 'order_id' => $order->get_id() ), PCS_B2B_Portal::url( '' ) ); ?>
          <div onclick="location.href='<?php echo esc_url( $detail_url ); ?>'"
               style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #f3f4f6;cursor:pointer;transition:background .12s;"
               onmouseenter="this.style.background='#f8faff'" onmouseleave="this.style.background='transparent'">
            <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;letter-spacing:.5px;">
              <?php echo esc_html( strtoupper( substr( $pname, 0, 3 ) ) ); ?>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:14px;color:#111827;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html( $pname . $extra ); ?></div>
              <div style="font-size:12px;color:#9ca3af;">#<?php echo (int) $order->get_id(); ?> · <?php echo esc_html( $order->get_date_created()->date_i18n( 'M j, Y' ) ); ?></div>
            </div>
            <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:9999px;white-space:nowrap;background:<?php echo esc_attr( $sl[1] ); ?>;color:<?php echo esc_attr( $sl[2] ); ?>;">
              <?php echo esc_html( $sl[0] ); ?>
            </span>
            <div style="font-size:14px;font-weight:700;color:#111827;white-space:nowrap;min-width:70px;text-align:right;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div>

    <!-- Tier card -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head <?php echo $is_b2b ? 'pcs-pcard-head-brand' : 'pcs-pcard-head-warning'; ?>">
        <h3 class="pcs-pcard-title"><?php echo $is_b2b ? '🏢 Your B2B tier' : '⭐ Membership'; ?></h3>
      </div>
      <div class="pcs-pcard-body" style="text-align:center;padding:20px 16px;">
        <?php echo PCS_DB::tier_pill_html( $tier, $tier_def['label'] ); ?>
        <?php if ( $tier_def['discount'] > 0 ) : ?>
          <div style="font-size:13px;color:#6b7280;margin-top:8px;"><?php echo (int) $tier_def['discount']; ?>% off all products</div>
        <?php else : ?>
          <!-- B2C: progress to next tier -->
          <?php
          $threshold = (float) get_option( 'pcs_tier_threshold_b2b_a', 1000 );
          $pct       = $threshold > 0 ? min( 100, round( $total_spent / $threshold * 100 ) ) : 0;
          ?>
          <div style="margin-top:12px;">
            <div style="font-size:12px;color:#6b7280;display:flex;justify-content:space-between;margin-bottom:4px;">
              <span>Progress to B2B Tier A</span>
              <span><?php echo $pct; ?>%</span>
            </div>
            <div style="background:#e5e7eb;height:6px;border-radius:9999px;overflow:hidden;">
              <div style="background:linear-gradient(90deg,#2563eb,#60a5fa);height:100%;width:<?php echo $pct; ?>%;border-radius:9999px;"></div>
            </div>
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;"><?php echo wp_kses_post( wc_price( max(0, $threshold - $total_spent) ) ); ?> more to unlock 10% off</div>
          </div>
        <?php endif; ?>

        <?php if ( $is_b2b ) : ?>
          <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'store' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary" style="width:100%;justify-content:center;margin-top:14px;display:flex;">My B2B Store →</a>
        <?php else : ?>
          <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'apply' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary" style="width:100%;justify-content:center;margin-top:14px;display:flex;">Apply for B2B →</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick links -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">🔗 Quick links</h3></div>
      <div style="padding:8px 0;">
        <?php
        $qlinks = array(
          array( PCS_B2B_Portal::url('orders'),  '→ My orders' ),
          array( PCS_B2B_Portal::url('profile'),  '→ Account details' ),
        );
        if ( $is_b2b ) $qlinks[] = array( PCS_B2B_Portal::url('team'), '→ Team management' );
        $qlinks[] = array( wc_get_page_permalink('shop'), '→ Shop' );
        foreach ( $qlinks as $ql ) : ?>
        <a href="<?php echo esc_url( $ql[0] ); ?>"
           style="display:block;padding:10px 18px;font-size:13px;color:#374151;text-decoration:none;transition:background .1s;"
           onmouseenter="this.style.background='#f8faff';this.style.color='#2563eb'" onmouseleave="this.style.background='transparent';this.style.color='#374151'">
          <?php echo esc_html( $ql[1] ); ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>
