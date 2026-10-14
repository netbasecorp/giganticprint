<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$filter_tier = isset( $_GET['tier'] )  ? sanitize_text_field( wp_unslash( $_GET['tier'] ) )  : '';
$search      = isset( $_GET['s'] )     ? sanitize_text_field( wp_unslash( $_GET['s'] ) )     : '';
$paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) )                   : 1;
$per_page    = 20;

$data       = PCS_Customers::get_customers( array(
    'tier'     => $filter_tier,
    'search'   => $search,
    'per_page' => $per_page,
    'paged'    => $paged,
) );
$customers  = $data['customers'];
$total      = $data['total'];
$pages      = ceil( $total / $per_page );

$stats      = PCS_Customers::get_stats();
$tiers      = PCS_DB::get_tiers_from_rules();
$base_url   = admin_url( 'admin.php?page=printcart-stores-customers' );

// closure — captures local vars correctly (không dùng global vì file được include trong function scope)
$cust_url = function( $extra = array() ) use ( $filter_tier, $search, $paged, $base_url ) {
    $params = array_filter( array_merge( array(
        'tier'  => $filter_tier,
        's'     => $search,
        'paged' => $paged > 1 ? $paged : '',
    ), $extra ) );
    return $base_url . ( $params ? '&' . http_build_query( $params ) : '' );
};
?>
<div class="pcs-wrap">

  <!-- Header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <h1>Customers</h1>
      <p class="pcs-page-sub">Manage customer tiers (B2B / B2C), view order history, and promote customers to B2B stores.</p>
    </div>
    <div class="pcs-page-actions">
      <a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="pcs-btn pcs-btn-secondary">+ Add new</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="pcs-stats-grid">
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Total customers</span><span class="pcs-stat-icon">👥</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['total']; ?></div>
      <div class="pcs-stat-delta">All registered users</div>
    </div>
    <div class="pcs-stat-card success">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">B2B accounts</span><span class="pcs-stat-icon success">🏢</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['b2b_count']; ?></div>
      <div class="pcs-stat-delta">Tiers A · B · C · VIP</div>
    </div>
    <div class="pcs-stat-card <?php echo $stats['pending_approval'] > 0 ? 'warning' : ''; ?>">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Pending approval</span><span class="pcs-stat-icon <?php echo $stats['pending_approval'] > 0 ? 'warning' : ''; ?>">⏳</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['pending_approval']; ?></div>
      <div class="pcs-stat-delta">B2B store applications</div>
    </div>
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">B2C customers</span><span class="pcs-stat-icon">🛒</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['b2c_count']; ?></div>
      <div class="pcs-stat-delta">Regular shoppers</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="pcs-tabs">
    <a href="<?php echo esc_url( $cust_url( array( 'tier' => '', 'paged' => '' ) ) ); ?>" class="pcs-tab <?php echo '' === $filter_tier ? 'active' : ''; ?>">
      All <span class="count"><?php echo (int) $stats['total']; ?></span>
    </a>
    <a href="<?php echo esc_url( $cust_url( array( 'tier' => 'b2c', 'paged' => '' ) ) ); ?>" class="pcs-tab <?php echo 'b2c' === $filter_tier ? 'active' : ''; ?>">
      B2C <span class="count"><?php echo (int) $stats['b2c_count']; ?></span>
    </a>
    <?php foreach ( $tiers as $tid => $tdef ) :
      if ( 'b2c' === $tid ) continue; // B2C already shown above
      $tab_count = (int) ( $stats['tier_counts'][ $tid ] ?? 0 );
    ?>
    <a href="<?php echo esc_url( $cust_url( array( 'tier' => $tid, 'paged' => '' ) ) ); ?>"
       class="pcs-tab <?php echo $tid === $filter_tier ? 'active' : ''; ?>">
      <?php echo esc_html( $tdef['label'] ); ?> <span class="count"><?php echo $tab_count; ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Filter -->
  <form method="get" action="<?php echo esc_url( $base_url ); ?>">
    <input type="hidden" name="page" value="printcart-stores-customers">
    <?php if ( $filter_tier ) : ?>
      <input type="hidden" name="tier" value="<?php echo esc_attr( $filter_tier ); ?>">
    <?php endif; ?>
    <div class="pcs-filter-row">
      <input type="search" name="s" class="pcs-filter-input" placeholder="Search by name, email, company…" value="<?php echo esc_attr( $search ); ?>">
      <button type="submit" class="pcs-btn pcs-btn-secondary">Search</button>
      <?php if ( $search ) : ?>
        <a href="<?php echo esc_url( $cust_url( array( 's' => '', 'paged' => '' ) ) ); ?>" class="pcs-btn pcs-btn-tertiary">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Table -->
  <div class="pcs-list-wrap">
    <div class="pcs-tablenav">
      <span style="font-size:12px;color:var(--pcs-text-soft);">
        <?php printf( '%d–%d of %d customers', min( ( $paged - 1 ) * $per_page + 1, max( 1, $total ) ), min( $paged * $per_page, $total ), $total ); ?>
      </span>
      <div class="pcs-pagination">
        <span class="pcs-pagination-info">Page <?php echo (int) $paged; ?> of <?php echo (int) max( 1, $pages ); ?></span>
        <button class="pcs-page-btn" <?php echo $paged <= 1 ? 'disabled' : ''; ?> onclick="location.href='<?php echo esc_url( $cust_url( array( 'paged' => max( 1, $paged - 1 ) ) ) ); ?>'">‹</button>
        <button class="pcs-page-btn" <?php echo $paged >= $pages ? 'disabled' : ''; ?> onclick="location.href='<?php echo esc_url( $cust_url( array( 'paged' => $paged + 1 ) ) ); ?>'">›</button>
      </div>
    </div>

    <table class="pcs-table">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Tier</th>
          <th>Orders</th>
          <th>Total Spent</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ( empty( $customers ) ) : ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:40px;color:var(--pcs-text-soft);">No customers found.</td>
        </tr>
        <?php else : ?>
        <?php foreach ( $customers as $c ) :
          $initials = PCS_Customers::get_initials( $c['display_name'] );
          $color_map = array( 'b2b_a' => '#2563eb', 'b2b_b' => '#d97706', 'b2b_c' => '#7c3aed', 'vip' => '#d97706', 'b2c' => '#6b7280' );
          $av_color  = $color_map[ $c['tier'] ] ?? '#6b7280';
        ?>
        <?php $cust_detail_url = admin_url( 'admin.php?page=printcart-stores-customers&action=view&id=' . $c['id'] ); ?>
        <tr style="cursor:pointer;" onclick="if(!event.target.closest('button,a,.pcs-btn'))location.href='<?php echo esc_js($cust_detail_url); ?>'">
          <td>
            <div class="pcs-col-title">
              <div class="pcs-avatar" style="background:linear-gradient(135deg,<?php echo esc_attr( $av_color ); ?>,<?php echo esc_attr( $av_color ); ?>cc);">
                <?php echo esc_html( $initials ); ?>
              </div>
              <div class="pcs-col-info">
                <div class="main">
                  <a href="<?php echo esc_url( $cust_detail_url ); ?>"><?php echo esc_html( $c['display_name'] ); ?></a>
                </div>
                <div class="sub"><?php echo esc_html( $c['email'] ); ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php echo PCS_DB::tier_pill_html( $c['tier'] ); ?>
            <?php
            /* Show pending B2B store badge if they have an application */
            $cstore = PCS_DB::get_store_by_user( $c['id'] );
            if ( $cstore && $cstore->status === 'pending' ) : ?>
              <div style="margin-top:4px;">
                <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-weight:600;">⏳ B2B pending</span>
              </div>
            <?php elseif ( $cstore && PCS_DB::store_is_approved( $cstore->status ) ) : ?>
              <div style="margin-top:4px;font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $cstore->company_name ); ?></div>
            <?php endif; ?>
          </td>
          <td><?php echo (int) $c['orders']; ?></td>
          <td><?php echo wp_kses_post( wc_price( $c['spent'] ) ); ?></td>
          <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $c['registered'] ) ) ); ?></td>
          <td>
            <a href="<?php echo esc_url( $cust_detail_url ); ?>" class="pcs-btn pcs-btn-tertiary pcs-btn-sm">View</a>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order&_customer_user=' . $c['id'] ) ); ?>" class="pcs-btn pcs-btn-tertiary pcs-btn-sm">Orders</a>
            <?php if ( $cstore ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-b2b&action=view&id=' . $cstore->id ) ); ?>" class="pcs-btn pcs-btn-secondary pcs-btn-sm">B2B →</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
