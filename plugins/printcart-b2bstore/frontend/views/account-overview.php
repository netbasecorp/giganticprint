<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$tier_label    = isset( $tier_def['label'] )    ? $tier_def['label']    : 'B2C';
$tier_discount = isset( $tier_def['discount'] ) ? (int) $tier_def['discount'] : 0;
$tier_bg       = esc_attr( $style['bg'] );
$tier_color    = esc_attr( $style['color'] );
$initials      = PCS_Customers::get_initials( $user->display_name );
?>
<div class="pcs-account">

  <!-- Page header -->
  <div class="pcs-page-header">
    <div class="pcs-page-header-inner">
      <div class="pcs-page-header-top">
        <div class="pcs-page-header-logo"><?php echo esc_html( $initials ); ?></div>
        <div class="pcs-page-header-info">
          <div class="pcs-page-header-eyebrow">My Account · <?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
          <h1 class="pcs-page-header-title">Welcome back, <?php echo esc_html( $user->first_name ?: $user->display_name ); ?> 👋</h1>
          <p class="pcs-page-header-sub">
            Here's an overview of your account — recent orders, your current tier, and quick actions.
          </p>
        </div>
        <div class="pcs-page-header-actions">
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-btn">Shop</a>
          <?php if ( $is_b2b ) : ?>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-b2b-store' ) ); ?>" class="pcs-btn pcs-btn-primary">B2B Store →</a>
          <?php else : ?>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-apply-b2b' ) ); ?>" class="pcs-btn pcs-btn-primary">Apply for B2B →</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="pcs-page-header-stats">
        <div>
          <div class="pcs-page-header-stat-value"><?php echo (int) $order_count; ?></div>
          <div class="pcs-page-header-stat-label">Total orders</div>
        </div>
        <div>
          <div class="pcs-page-header-stat-value"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></div>
          <div class="pcs-page-header-stat-label">Total spent</div>
        </div>
        <div>
          <div class="pcs-page-header-stat-value"
               style="background:<?php echo $tier_bg; ?>;color:<?php echo $tier_color; ?>;padding:3px 12px;border-radius:9999px;font-size:15px;">
            <?php echo esc_html( $tier_label ); ?>
          </div>
          <div class="pcs-page-header-stat-label">Your tier<?php echo $tier_discount > 0 ? ' · ' . $tier_discount . '% off' : ''; ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats row -->
  <div class="pcs-stats-grid">
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head">
        <span class="pcs-stat-label">Total orders</span>
        <span class="pcs-stat-icon">📦</span>
      </div>
      <div class="pcs-stat-value"><?php echo (int) $order_count; ?></div>
      <div class="pcs-stat-delta">All time</div>
    </div>

    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head">
        <span class="pcs-stat-label">Total spent</span>
        <span class="pcs-stat-icon">💰</span>
      </div>
      <div class="pcs-stat-value"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></div>
      <div class="pcs-stat-delta">Cumulative</div>
    </div>

    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head">
        <span class="pcs-stat-label">Your discount</span>
        <span class="pcs-stat-icon">🏷️</span>
      </div>
      <div class="pcs-stat-value"><?php echo $tier_discount > 0 ? $tier_discount . '%' : '—'; ?></div>
      <div class="pcs-stat-delta"><?php echo esc_html( $tier_label ); ?></div>
    </div>

    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head">
        <span class="pcs-stat-label">Savings YTD</span>
        <span class="pcs-stat-icon">⭐</span>
      </div>
      <?php
      $savings = $tier_discount > 0 ? ( $total_spent / ( 1 - $tier_discount / 100 ) - $total_spent ) : 0;
      ?>
      <div class="pcs-stat-value"><?php echo wp_kses_post( wc_price( $savings ) ); ?></div>
      <div class="pcs-stat-delta"><?php echo $tier_discount > 0 ? $tier_discount . '% tier benefit' : 'Upgrade for savings'; ?></div>
    </div>
  </div>

  <!-- Two column: recent orders + sidebar -->
  <div class="pcs-layout-2col">

    <!-- Left: Recent orders -->
    <div>
      <div class="pcs-card">
        <div class="pcs-card-head brand">
          <h3 class="pcs-card-title">Recent orders</h3>
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="pcs-card-action">View all →</a>
        </div>
        <div class="pcs-card-body flush">
          <?php if ( empty( $recent_orders ) ) : ?>
            <div style="padding:32px;text-align:center;color:var(--pfe-text-soft);">
              No orders yet.
              <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Start shopping →</a>
            </div>
          <?php else : ?>
            <?php foreach ( $recent_orders as $order ) :
              $status   = $order->get_status();
              $status_class = in_array( $status, array( 'completed' ) ) ? 'delivered' : $status;
              $name     = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
              $items    = $order->get_items();
              $first_item = reset( $items );
              $product_name = $first_item ? $first_item->get_name() : '#' . $order->get_id();
              $extra = count( $items ) > 1 ? ' + ' . ( count( $items ) - 1 ) . ' more' : '';
            ?>
            <div class="pcs-order-row" onclick="location.href='<?php echo esc_url( $order->get_view_order_url() ); ?>'">
              <div class="pcs-order-thumb" style="background:linear-gradient(135deg,#2b3a8a,#1a1547);">
                <?php echo esc_html( strtoupper( substr( $product_name, 0, 4 ) ) ); ?>
              </div>
              <div>
                <div class="pcs-order-title"><?php echo esc_html( $product_name . $extra ); ?></div>
                <div class="pcs-order-meta">
                  #<?php echo (int) $order->get_id(); ?> ·
                  <?php echo esc_html( $order->get_date_created()->date_i18n( 'M j, Y' ) ); ?>
                </div>
              </div>
              <span class="pcs-order-status <?php echo esc_attr( $status_class ); ?>">
                <span class="pcs-status-dot"></span>
                <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
              </span>
              <div class="pcs-order-price" style="font-size:13px;font-weight:600;">
                <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right sidebar -->
    <div>

      <!-- Tier card -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head <?php echo $is_b2b ? 'brand' : 'warning'; ?>">
          <h3 class="pcs-card-title"><?php echo $is_b2b ? 'Your B2B tier' : 'Membership'; ?></h3>
        </div>
        <div class="pcs-card-body">
          <div style="text-align:center;padding:8px 0 14px;">
            <div style="font-size:13px;font-weight:600;color:var(--pfe-text-soft);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Current tier</div>
            <span style="display:inline-block;padding:5px 16px;border-radius:9999px;font-size:14px;font-weight:700;background:<?php echo esc_attr($tier_bg); ?>;color:<?php echo esc_attr($tier_color); ?>;">
              <?php echo esc_html( $tier_label ); ?>
            </span>
            <?php if ( $tier_discount > 0 ) : ?>
            <div style="font-size:13px;color:var(--pfe-text-soft);margin-top:8px;"><?php echo $tier_discount; ?>% off all products</div>
            <?php endif; ?>
          </div>

          <?php if ( ! $is_b2b ) :
            /* Progress to first B2B tier */
            $tiers_arr    = $tiers;
            $b2b_a_thresh = isset( $tiers_arr['b2b_a'] ) ? (float) ( get_user_meta( $user->ID, '_pcs_tier_threshold_b2b_a', true ) ?: 1000 ) : 1000;
            $pct          = $b2b_a_thresh > 0 ? min( 100, round( $total_spent / $b2b_a_thresh * 100 ) ) : 0;
          ?>
          <div class="pcs-progress-wrap">
            <div class="pcs-progress-info">
              <span style="font-size:12px;color:var(--pfe-text-soft);">Progress to B2B Tier A</span>
              <span style="font-size:12px;font-weight:600;"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?> / <?php echo wp_kses_post( wc_price( $b2b_a_thresh ) ); ?></span>
            </div>
            <div class="pcs-progress-track">
              <div class="pcs-progress-fill" style="width:<?php echo (int) $pct; ?>%;"></div>
            </div>
            <div style="font-size:11px;color:var(--pfe-text-soft);margin-top:4px;"><?php echo $pct; ?>% · <?php echo wp_kses_post( wc_price( max( 0, $b2b_a_thresh - $total_spent ) ) ); ?> more to unlock 10% off</div>
          </div>
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-apply-b2b' ) ); ?>" class="pcs-btn pcs-btn-primary" style="width:100%;justify-content:center;margin-top:10px;">
            Apply for B2B now →
          </a>
          <?php else : ?>
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-b2b-store' ) ); ?>" class="pcs-btn pcs-btn-primary" style="width:100%;justify-content:center;margin-top:4px;">
            My B2B Store →
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Benefits -->
      <div class="pcs-card">
        <div class="pcs-card-head">
          <h3 class="pcs-card-title"><?php echo $is_b2b ? 'Your benefits' : 'B2B benefits'; ?></h3>
        </div>
        <div class="pcs-card-body">
          <ul class="pcs-benefits">
            <?php if ( $is_b2b ) : ?>
              <li><?php echo $tier_discount; ?>% off all products</li>
              <li>Company portal: orders, team members and quotes</li>
              <li>Company Image Zone for your logos and artwork</li>
              <li>Private company templates</li>
              <li>Products reserved for your company</li>
              <li>One-click reorder with your original art</li>
            <?php else : ?>
              <li>Free file pre-press review</li>
              <li>Saved templates & design history</li>
              <li>Reorder past prints in 1 click</li>
              <li class="locked">Tier discount on every order (B2B 10%, Reseller 20%)</li>
              <li class="locked">Company portal: orders, team members and quotes</li>
              <li class="locked">Company Image Zone for your logos and artwork</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="pcs-card">
        <div class="pcs-card-head"><h3 class="pcs-card-title">Quick links</h3></div>
        <div class="pcs-card-body" style="font-size:13px;line-height:1.9;">
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" style="display:block;">→ My orders</a>
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" style="display:block;">→ My addresses</a>
          <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" style="display:block;">→ Account details</a>
          <?php if ( $is_b2b ) : ?>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-team' ) ); ?>" style="display:block;">→ My team</a>
          <?php else : ?>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'pcs-apply-b2b' ) ); ?>" style="display:block;">→ Apply for B2B</a>
          <?php endif; ?>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" style="display:block;">→ Shop</a>
        </div>
      </div>

    </div>
  </div>

</div>
