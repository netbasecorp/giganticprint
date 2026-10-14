<?php if ( ! defined( 'ABSPATH' ) ) exit;

$filter  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$search  = isset( $_GET['s'] )      ? sanitize_text_field( wp_unslash( $_GET['s'] ) )      : '';
$paged   = isset( $_GET['paged'] )  ? max( 1, absint( $_GET['paged'] ) )                   : 1;
$per_page = 15;

$stats  = PCS_Quotes::get_stats();
$quotes = PCS_Quotes::get_list( array( 'status' => $filter, 'search' => $search, 'per_page' => $per_page, 'paged' => $paged ) );
$total  = PCS_Quotes::count( array( 'status' => $filter, 'search' => $search ) );
$pages  = ceil( $total / $per_page );
$base   = admin_url( 'admin.php?page=printcart-quotes' );

$q_url = function( $extra = array() ) use ( $filter, $search, $paged, $base ) {
    $p = array_filter( array_merge( array( 'status' => $filter, 's' => $search, 'paged' => $paged > 1 ? $paged : '' ), $extra ) );
    return $base . ( $p ? '&' . http_build_query( $p ) : '' );
};

$status_labels = array(
    'new_request' => array( 'New request',  '#fef3c7', '#92400e' ),
    'in_review'   => array( 'In review',    '#dbeafe', '#1e40af' ),
    'sent'        => array( 'Sent',         '#e0e7ff', '#3730a3' ),
    'accepted'    => array( 'Accepted',     '#dcfce7', '#065f46' ),
    'declined'    => array( 'Declined',     '#fee2e2', '#991b1b' ),
    'expired'     => array( 'Expired',      '#f3f4f6', '#6b7280' ),
);
?>
<div class="pcs-wrap">

  <!-- Header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <h1>Custom Quotes</h1>
      <p class="pcs-page-sub">Handle custom requests outside the standard catalog — large volume orders, special materials, multi-product packages.</p>
    </div>
    <div class="pcs-page-actions">
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-quotes&action=view&id=new' ) ); ?>" class="pcs-btn pcs-btn-primary">+ Create quote</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="pcs-stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="pcs-stat-card <?php echo $stats['new_request'] > 0 ? 'warning' : ''; ?>">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">New Requests</span><span class="pcs-stat-icon <?php echo $stats['new_request'] > 0 ? 'warning' : ''; ?>">🕐</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['new_request']; ?></div>
      <div class="pcs-stat-delta"><?php echo $stats['new_request'] > 0 ? 'Action needed' : 'All clear'; ?></div>
    </div>
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">In Review</span><span class="pcs-stat-icon">🔍</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['in_review']; ?></div>
      <div class="pcs-stat-delta">Drafting quotes</div>
    </div>
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Sent · Awaiting</span><span class="pcs-stat-icon">📤</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['sent']; ?></div>
      <div class="pcs-stat-delta">Awaiting customer response</div>
    </div>
    <div class="pcs-stat-card success">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Accepted (30d)</span><span class="pcs-stat-icon success">✅</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['accepted']; ?></div>
      <div class="pcs-stat-delta"><?php echo $stats['accepted_value'] > 0 ? wp_kses_post( wc_price( $stats['accepted_value'] ) ) . ' value' : 'No accepted quotes yet'; ?></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="pcs-tabs">
    <?php
    $tabs = array(
        ''            => 'All',
        'new_request' => 'New requests',
        'in_review'   => 'In review',
        'sent'        => 'Sent',
        'accepted'    => 'Accepted',
        'declined'    => 'Declined',
        'expired'     => 'Expired',
    );
    foreach ( $tabs as $ts => $tl ) : ?>
    <a href="<?php echo esc_url( $q_url( array( 'status' => $ts, 'paged' => '' ) ) ); ?>"
       class="pcs-tab <?php echo $filter === $ts ? 'active' : ''; ?>">
      <?php echo esc_html( $tl ); ?>
      <?php if ( $ts && isset( $stats[ $ts ] ) && $stats[ $ts ] > 0 ) : ?>
        <span class="count"><?php echo (int) $stats[ $ts ]; ?></span>
      <?php elseif ( ! $ts ) : ?>
        <span class="count"><?php echo (int) $stats['total']; ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <form method="get" action="<?php echo esc_url( $base ); ?>">
    <input type="hidden" name="page" value="printcart-quotes">
    <?php if ( $filter ) : ?><input type="hidden" name="status" value="<?php echo esc_attr( $filter ); ?>"><?php endif; ?>
    <div class="pcs-filter-row">
      <input type="search" name="s" class="pcs-filter-input" placeholder="Search quote # / customer / title…" value="<?php echo esc_attr( $search ); ?>">
      <button type="submit" class="pcs-btn pcs-btn-secondary">Search</button>
      <?php if ( $search ) : ?><a href="<?php echo esc_url( $q_url( array( 's' => '', 'paged' => '' ) ) ); ?>" class="pcs-btn pcs-btn-tertiary">Clear</a><?php endif; ?>
    </div>
  </form>

  <!-- Table -->
  <div class="pcs-list-wrap">
    <div class="pcs-tablenav">
      <span style="font-size:12px;color:var(--pcs-text-soft);"><?php echo $total; ?> quote<?php echo $total !== 1 ? 's' : ''; ?></span>
    </div>
    <table class="pcs-table">
      <thead>
        <tr>
          <th>Quote #</th>
          <th>Customer</th>
          <th>Title / Request</th>
          <th>Est. value</th>
          <th>Status</th>
          <th>Updated</th>
          <th>Expires</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if ( empty( $quotes ) ) : ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--pcs-text-soft);">
          No quotes found.<?php if ( ! $filter && ! $search ) echo ' Quotes from B2B customers will appear here.'; ?>
        </td></tr>
        <?php else : ?>
        <?php foreach ( $quotes as $q ) :
          $sl       = $status_labels[ $q->status ] ?? array( ucfirst( $q->status ), '#f3f4f6', '#6b7280' );
          $updated  = human_time_diff( strtotime( $q->updated_at ), time() ) . ' ago';
          $expires  = $q->valid_until ? date_i18n( 'M j, Y', strtotime( $q->valid_until ) ) : '—';
          $is_new   = 'new_request' === $q->status;
          $view_url = admin_url( 'admin.php?page=printcart-quotes&action=view&id=' . $q->id );
        ?>
        <tr style="<?php echo $is_new ? 'background:#fffbeb;' : ''; ?>cursor:pointer;" onclick="location.href='<?php echo esc_url( $view_url ); ?>'">
          <td>
            <a href="<?php echo esc_url( $view_url ); ?>" style="font-weight:700;color:var(--pcs-brand);" onclick="event.stopPropagation();">
              <?php echo esc_html( $q->quote_number ); ?>
            </a>
            <?php if ( $is_new ) : ?>
              <div style="font-size:10px;color:#d97706;font-weight:600;margin-top:2px;">⚠ New · respond in 24h</div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;"><?php echo esc_html( $q->display_name ); ?></div>
            <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $q->user_email ); ?></div>
          </td>
          <td style="max-width:280px;"><div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo esc_html( $q->title ?: '—' ); ?></div></td>
          <td style="font-weight:600;"><?php echo $q->total > 0 ? wp_kses_post( wc_price( $q->total ) ) : '<span style="color:var(--pcs-text-soft);">TBD</span>'; ?></td>
          <td>
            <span class="pcs-status-pill" style="background:<?php echo esc_attr( $sl[1] ); ?>;color:<?php echo esc_attr( $sl[2] ); ?>;padding:3px 10px;border-radius:9999px;font-size:12px;font-weight:600;">
              <?php echo esc_html( $sl[0] ); ?>
            </span>
          </td>
          <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( $updated ); ?></td>
          <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( $expires ); ?></td>
          <td onclick="event.stopPropagation();">
            <?php if ( in_array( $q->status, array( 'new_request', 'in_review' ), true ) ) : ?>
              <a href="<?php echo esc_url( $view_url ); ?>" class="pcs-btn pcs-btn-primary pcs-btn-sm">Review</a>
            <?php elseif ( 'sent' === $q->status ) : ?>
              <a href="<?php echo esc_url( $view_url ); ?>" class="pcs-btn pcs-btn-tertiary pcs-btn-sm">View</a>
            <?php elseif ( 'accepted' === $q->status ) : ?>
              <span style="font-size:12px;color:var(--pcs-success);font-weight:600;">✅ Accepted</span>
            <?php else : ?>
              <a href="<?php echo esc_url( $view_url ); ?>" class="pcs-btn pcs-btn-tertiary pcs-btn-sm">View</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ( $pages > 1 ) : ?>
    <div class="pcs-card-foot">
      <span style="font-size:12px;color:var(--pcs-text-soft);">Page <?php echo (int) $paged; ?> of <?php echo (int) $pages; ?></span>
      <div class="pcs-pagination">
        <?php if ( $paged > 1 ) : ?>
          <a href="<?php echo esc_url( $q_url( array( 'paged' => $paged - 1 ) ) ); ?>" class="pcs-page-btn">‹</a>
        <?php endif; ?>
        <?php if ( $paged < $pages ) : ?>
          <a href="<?php echo esc_url( $q_url( array( 'paged' => $paged + 1 ) ) ); ?>" class="pcs-page-btn">›</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>
