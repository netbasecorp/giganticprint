<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
/* ---- Query args ---- */
$current_status = isset( $_GET['status'] )   ? sanitize_text_field( wp_unslash( $_GET['status'] ) )   : '';
$current_tier   = isset( $_GET['tier'] )     ? sanitize_text_field( wp_unslash( $_GET['tier'] ) )     : '';
$search         = isset( $_GET['s'] )        ? sanitize_text_field( wp_unslash( $_GET['s'] ) )        : '';
$paged          = isset( $_GET['paged'] )    ? max( 1, absint( $_GET['paged'] ) )                     : 1;
$per_page       = 20;

$args = array(
    'status'   => $current_status,
    'tier'     => $current_tier,
    'search'   => $search,
    'per_page' => $per_page,
    'paged'    => $paged,
);

$stores   = PCS_DB::get_stores( $args );
$total    = PCS_DB::count_stores( $args );
$stats    = PCS_DB::get_store_stats();
$inv_stat = PCS_DB::get_invitation_stats();
$tiers    = PCS_DB::get_tiers_from_rules();
$pages    = ceil( $total / $per_page );

$base_url = admin_url( 'admin.php?page=printcart-stores-b2b' );

// closure — captures local vars (file is include'd inside a function, không phải global scope)
$build_url = function( $extra = array() ) use ( $current_status, $current_tier, $search, $paged, $base_url ) {
    $params = array_filter( array_merge( array(
        'status' => $current_status,
        'tier'   => $current_tier,
        's'      => $search,
        'paged'  => $paged > 1 ? $paged : '',
    ), $extra ) );
    return $base_url . ( $params ? '&' . http_build_query( $params ) : '' );
};
?>
<div class="pcs-wrap">

  <!-- Page header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <h1>B2B Stores</h1>
      <span class="pcs-status-pill" data-status="active" style="background:rgba(255,255,255,.2);color:#fff;">
        <span class="dot"></span><?php echo (int) $stats['active']; ?> active
      </span>
      <?php if ( $stats['pending'] > 0 ) : ?>
      <span class="pcs-status-pill" data-status="pending" style="background:rgba(255,255,255,.2);color:#fff;">
        <span class="dot"></span><?php echo (int) $stats['pending']; ?> pending
      </span>
      <?php endif; ?>
      <p class="pcs-page-sub">Manage B2B Corporate store accounts, review applications, send invitations, and update store profiles.</p>
    </div>
    <div class="pcs-page-actions">
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-invitations' ) ); ?>" class="pcs-btn pcs-btn-primary">+ Invite store</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="pcs-stats-grid">
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Active stores</span><span class="pcs-stat-icon">🏢</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['active']; ?></div>
      <div class="pcs-stat-delta">Approved &amp; active</div>
    </div>
    <div class="pcs-stat-card <?php echo $stats['pending'] > 0 ? 'warning' : ''; ?>">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Pending approval</span><span class="pcs-stat-icon <?php echo $stats['pending'] > 0 ? 'warning' : ''; ?>">⏳</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['pending']; ?></div>
      <div class="pcs-stat-delta">Action needed</div>
    </div>
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Invitations sent</span><span class="pcs-stat-icon success">📨</span></div>
      <div class="pcs-stat-value"><?php echo (int) $inv_stat['pending']; ?></div>
      <div class="pcs-stat-delta">Awaiting acceptance</div>
    </div>
    <div class="pcs-stat-card">
      <div class="pcs-stat-card-head"><span class="pcs-stat-label">Total</span><span class="pcs-stat-icon">📊</span></div>
      <div class="pcs-stat-value"><?php echo (int) $stats['total']; ?></div>
      <div class="pcs-stat-delta">All time</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="pcs-tabs">
    <a href="<?php echo esc_url( $build_url( array( 'status' => '', 'paged' => '' ) ) ); ?>" class="pcs-tab <?php echo '' === $current_status ? 'active' : ''; ?>">
      All stores <span class="count"><?php echo (int) $stats['total']; ?></span>
    </a>
    <a href="<?php echo esc_url( $build_url( array( 'status' => 'active', 'paged' => '' ) ) ); ?>" class="pcs-tab <?php echo 'active' === $current_status ? 'active' : ''; ?>">
      Active <span class="count"><?php echo (int) $stats['active']; ?></span>
    </a>
    <a href="<?php echo esc_url( $build_url( array( 'status' => 'pending', 'paged' => '' ) ) ); ?>" class="pcs-tab <?php echo 'pending' === $current_status ? 'active' : ''; ?>">
      Pending applications <span class="count"><?php echo (int) $stats['pending']; ?></span>
    </a>
    <a href="<?php echo esc_url( $build_url( array( 'status' => 'rejected', 'paged' => '' ) ) ); ?>" class="pcs-tab <?php echo 'rejected' === $current_status ? 'active' : ''; ?>">
      Rejected <span class="count"><?php echo (int) $stats['rejected']; ?></span>
    </a>
  </div>

  <!-- Filter row -->
  <form method="get" action="<?php echo esc_url( $base_url ); ?>">
    <input type="hidden" name="page" value="printcart-stores-b2b">
    <?php if ( $current_status ) : ?>
      <input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>">
    <?php endif; ?>
    <div class="pcs-filter-row">
      <select name="tier" class="pcs-filter-select">
        <option value="">All tiers</option>
        <?php foreach ( $tiers as $tid => $tdef ) : ?>
          <option value="<?php echo esc_attr( $tid ); ?>" <?php selected( $current_tier, $tid ); ?>>
            <?php echo esc_html( $tdef['label'] ); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="s" class="pcs-filter-input" placeholder="Search by company, email, contact…" value="<?php echo esc_attr( $search ); ?>">
      <button type="submit" class="pcs-btn pcs-btn-secondary">Filter</button>
      <?php if ( $search || $current_tier ) : ?>
        <a href="<?php echo esc_url( $build_url( array( 'tier' => '', 's' => '', 'paged' => '' ) ) ); ?>" class="pcs-btn pcs-btn-tertiary">Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Pending applications highlight block -->
  <?php
  $pending_list = PCS_DB::get_stores( array( 'status' => 'pending', 'per_page' => 5 ) );
  if ( ! empty( $pending_list ) && '' === $current_status ) :
  ?>
  <div class="pcs-card">
    <div class="pcs-card-head is-warning">
      <span class="pcs-card-title">⚠️ Pending Applications — review &amp; approve</span>
      <span style="background:var(--pcs-warning);color:#1d2327;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:700;">
        <?php echo (int) $stats['pending']; ?> awaiting
      </span>
    </div>
    <div class="pcs-card-body p0">
      <table class="pcs-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Contact</th>
            <th>Tier requested</th>
            <th>Est. volume</th>
            <th>Applied</th>
            <th class="col-actions"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $pending_list as $s ) :
            $initials = PCS_Customers::get_initials( $s->company_name );
            $t_req    = isset( $tiers[ $s->tier ] ) ? $tiers[ $s->tier ]['label'] : $s->tier;
          ?>
          <tr class="highlight-yellow">
            <td>
              <div class="pcs-col-title">
                <div class="pcs-avatar"><?php echo esc_html( $initials ); ?></div>
                <div class="pcs-col-info">
                  <div class="main"><?php echo esc_html( $s->company_name ); ?></div>
                  <div class="sub"><?php echo esc_html( $s->industry ); ?></div>
                </div>
              </div>
            </td>
            <td>
              <div><?php echo esc_html( $s->contact_name ); ?></div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $s->contact_email ); ?></div>
            </td>
            <td><?php echo PCS_DB::tier_pill_html( $s->tier, $t_req ); ?></td>
            <td><?php echo esc_html( $s->monthly_volume ?: '—' ); ?></td>
            <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( human_time_diff( strtotime( $s->created_at ), time() ) . ' ago' ); ?></td>
            <td style="display:flex;gap:4px;">
              <button class="pcs-btn pcs-btn-primary pcs-btn-sm pcs-btn-approve-store" data-store-id="<?php echo (int) $s->id; ?>" data-store-tier="<?php echo esc_attr( $s->tier ); ?>" data-store-payment="<?php echo esc_attr( $s->payment_terms ); ?>" data-store-credit="<?php echo esc_attr( $s->credit_limit ); ?>" data-store-team="<?php echo esc_attr( $s->team_size ); ?>">Approve</button>
              <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm pcs-btn-reject-store" data-store-id="<?php echo (int) $s->id; ?>">Reject</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ( $stats['pending'] > 5 ) : ?>
    <div class="pcs-card-foot">
      <span></span>
      <a href="<?php echo esc_url( $build_url( array( 'status' => 'pending', 'paged' => '' ) ) ); ?>" class="pcs-btn pcs-btn-tertiary pcs-btn-sm">View all <?php echo (int) $stats['pending']; ?> pending &rarr;</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Main stores table -->
  <div class="pcs-list-wrap">
    <div class="pcs-tablenav">
      <div class="pcs-row">
        <span style="font-size:12px;color:var(--pcs-text-soft);">
          <?php printf( '%d–%d of %d', min( ( $paged - 1 ) * $per_page + 1, $total ), min( $paged * $per_page, $total ), $total ); ?>
        </span>
      </div>
      <!-- Pagination right -->
      <div class="pcs-pagination">
        <span class="pcs-pagination-info">Page <?php echo (int) $paged; ?> of <?php echo (int) max( 1, $pages ); ?></span>
        <button class="pcs-page-btn" <?php echo $paged <= 1 ? 'disabled' : ''; ?> onclick="location.href='<?php echo esc_url( $build_url( array( 'paged' => max( 1, $paged - 1 ) ) ) ); ?>'">&#8249;</button>
        <button class="pcs-page-btn" <?php echo $paged >= $pages ? 'disabled' : ''; ?> onclick="location.href='<?php echo esc_url( $build_url( array( 'paged' => $paged + 1 ) ) ); ?>'">&#8250;</button>
      </div>
    </div>

    <table class="pcs-table">
      <thead>
        <tr>
          <th>Store</th>
          <th>Tier</th>
          <th>Team</th>
          <th>Orders (90d)</th>
          <th>Status</th>
          <th>Since</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if ( empty( $stores ) ) : ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--pcs-text-soft);">
            No stores found.
            <?php if ( '' === $current_status ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=printcart-stores-invitations' ) ); ?>">Send your first invitation</a>.
            <?php endif; ?>
          </td>
        </tr>
        <?php else : ?>
        <?php foreach ( $stores as $s ) :
          $initials  = PCS_Customers::get_initials( $s->company_name );
          $tier_def  = isset( $tiers[ $s->tier ] ) ? $tiers[ $s->tier ] : array( 'label' => $s->tier );
          $color_map = array( 'b2b_a' => '#2563eb', 'b2b_b' => '#d97706', 'b2b_c' => '#7c3aed', 'vip' => '#d97706', 'b2c' => '#6b7280' );
          $av_color  = $color_map[ $s->tier ] ?? '#2563eb';
          /* WC order count for this user last 90d */
          $order_count = 0;
          if ( $s->user_id ) {
              $o90 = wc_get_orders( array(
                  'customer' => (int) $s->user_id,
                  'date_after'=> date( 'Y-m-d', strtotime( '-90 days' ) ),
                  'limit'    => -1,
                  'return'   => 'ids',
              ) );
              $order_count = count( $o90 );
          }
          $members = PCS_DB::get_members( $s->id );
        ?>
        <?php $detail_url = admin_url( 'admin.php?page=printcart-stores-b2b&action=view&id=' . $s->id ); ?>
        <tr class="<?php echo 'pending' === $s->status ? 'highlight-yellow' : ''; ?>"
            style="cursor:pointer;"
            onclick="if(!event.target.closest('button,a,.pcs-btn'))location.href='<?php echo esc_js( $detail_url ); ?>'">
          <td>
            <div class="pcs-col-title">
              <div class="pcs-avatar" style="background:linear-gradient(135deg,<?php echo esc_attr( $av_color ); ?>,<?php echo esc_attr( $av_color ); ?>cc);">
                <?php echo esc_html( $initials ); ?>
              </div>
              <div class="pcs-col-info">
                <div class="main"><?php echo esc_html( $s->company_name ); ?></div>
                <div class="sub"><?php echo esc_html( implode( ' · ', array_filter( array( $s->industry, $s->contact_email ) ) ) ); ?></div>
              </div>
            </div>
          </td>
          <td><?php echo PCS_DB::tier_pill_html( $s->tier ); ?></td>
          <td><?php echo count( $members ) + 1; ?> members</td>
          <td><?php echo (int) $order_count; ?></td>
          <td>
            <span class="pcs-status-pill" data-status="<?php echo esc_attr( $s->status ); ?>">
              <span class="dot"></span><?php echo esc_html( ucfirst( $s->status ) ); ?>
            </span>
            <?php if ( 'pending' === $s->status ) : ?>
              <div style="margin-top:4px;display:flex;gap:4px;">
                <button class="pcs-btn pcs-btn-primary pcs-btn-sm pcs-btn-approve-store" data-store-id="<?php echo (int) $s->id; ?>" data-store-tier="<?php echo esc_attr( $s->tier ); ?>" data-store-payment="<?php echo esc_attr( $s->payment_terms ); ?>" data-store-credit="<?php echo esc_attr( $s->credit_limit ); ?>" data-store-team="<?php echo esc_attr( $s->team_size ); ?>">Approve</button>
                <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm pcs-btn-reject-store" data-store-id="<?php echo (int) $s->id; ?>">Reject</button>
              </div>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $s->created_at ) ) ); ?></td>
          <td>
            <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm pcs-btn-approve-store"
                    data-store-id="<?php echo (int) $s->id; ?>"
                    data-store-tier="<?php echo esc_attr( $s->tier ); ?>"
                    data-store-payment="<?php echo esc_attr( $s->payment_terms ); ?>"
                    data-store-credit="<?php echo esc_attr( $s->credit_limit ); ?>"
                    data-store-team="<?php echo esc_attr( $s->team_size ); ?>"
                    style="<?php echo 'pending' !== $s->status ? '' : 'display:none'; ?>">
              Edit
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- ===== Approve Modal ===== -->
<div class="pcs-modal-backdrop" id="pcs-modal-approve">
  <div class="pcs-modal">
    <div class="pcs-modal-head">
      <h3 class="pcs-modal-title">✅ Approve B2B Application</h3>
      <button class="pcs-modal-close">×</button>
    </div>
    <div class="pcs-modal-body">
      <input type="hidden" id="pcs-approve-store-id">
      <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
        <label class="pcs-form-label">Approve as tier</label>
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
        <label class="pcs-form-label">Initial team seats</label>
        <input type="number" id="pcs-approve-team-size" class="pcs-input" value="5" style="max-width:80px;">
      </div>
    </div>
    <div class="pcs-modal-foot">
      <button class="pcs-btn pcs-btn-tertiary pcs-modal-close">Cancel</button>
      <button id="pcs-confirm-approve" class="pcs-btn pcs-btn-primary">Approve &amp; activate →</button>
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
