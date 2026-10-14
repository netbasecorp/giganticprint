<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Portal section: Orders & Invoices
 * Variables: $user_id, $user, $tier, $tiers, $is_b2b, $store
 */

/* ---- Query args ---- */
$filter_status = isset( $_GET['order_status'] ) ? sanitize_text_field( wp_unslash( $_GET['order_status'] ) ) : '';
$paged         = isset( $_GET['order_page'] )  ? max( 1, absint( $_GET['order_page'] ) )                   : 1;
$per_page      = 10;

/* Map filter to WC statuses */
$status_map = array(
    'production' => array( 'wc-processing', 'wc-on-hold' ),
    'shipped'    => array( 'wc-shipped' ),
    'delivered'  => array( 'wc-completed' ),
    'pending'    => array( 'wc-pending' ),
);
$wc_statuses = $filter_status && isset( $status_map[ $filter_status ] )
               ? $status_map[ $filter_status ]
               : array_keys( wc_get_order_statuses() );

/* All orders for counts */
$all_orders = wc_get_orders( array( 'customer' => $user_id, 'limit' => -1, 'return' => 'ids' ) );
$total_all  = count( $all_orders );

/* Counts per status group */
$count_production = count( wc_get_orders( array( 'customer' => $user_id, 'status' => array( 'processing', 'on-hold' ), 'limit' => -1, 'return' => 'ids' ) ) );
$count_shipped    = count( wc_get_orders( array( 'customer' => $user_id, 'status' => array( 'shipped' ),    'limit' => -1, 'return' => 'ids' ) ) );
$count_delivered  = count( wc_get_orders( array( 'customer' => $user_id, 'status' => array( 'completed' ),  'limit' => -1, 'return' => 'ids' ) ) );

/* Paginated orders */
$wc_status_args = $filter_status && isset( $status_map[ $filter_status ] )
                  ? array_map( function($s) { return str_replace( 'wc-', '', $s ); }, $status_map[ $filter_status ] )
                  : array();
$orders_args = array(
    'customer'  => $user_id,
    'limit'     => $per_page,
    'offset'    => ( $paged - 1 ) * $per_page,
    'orderby'   => 'date',
    'order'     => 'DESC',
);
if ( $wc_status_args ) $orders_args['status'] = $wc_status_args;

$orders      = wc_get_orders( $orders_args );
$total_count = $filter_status ? count( wc_get_orders( array_merge( $orders_args, array( 'limit' => -1, 'offset' => 0, 'return' => 'ids' ) ) ) ) : $total_all;
$pages       = max( 1, ceil( $total_count / $per_page ) );

/* Total spend */
$total_spent = (float) wc_get_customer_total_spent( $user_id );

/* Portal base URL */
$portal_url = PCS_B2B_Portal::url( 'orders' );
$tab_url = function( $status ) use ( $portal_url ) {
    return $status ? add_query_arg( 'order_status', $status, $portal_url ) : $portal_url;
};
$page_url = function( $p ) use ( $portal_url, $filter_status ) {
    $url = $portal_url;
    if ( $filter_status ) $url = add_query_arg( 'order_status', $filter_status, $url );
    return $p > 1 ? add_query_arg( 'order_page', $p, $url ) : $url;
};

/* Status label/style map */
$status_styles = array(
    'completed'  => array( 'Delivered',    '#dcfce7', '#065f46' ),
    'processing' => array( 'In production','#fef3c7', '#92400e' ),
    'on-hold'    => array( 'On hold',      '#fef3c7', '#92400e' ),
    'shipped'    => array( 'Shipped',      '#dbeafe', '#1e40af' ),
    'pending'    => array( 'Pending',      '#f3f4f6', '#6b7280' ),
    'cancelled'  => array( 'Cancelled',    '#fee2e2', '#991b1b' ),
    'refunded'   => array( 'Refunded',     '#fee2e2', '#991b1b' ),
    'failed'     => array( 'Failed',       '#fee2e2', '#991b1b' ),
);
?>

<!-- ===== PAGE HEADER ===== -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">📦</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> · Orders</div>
        <h1 class="pcs-ph-title">Orders & Invoices</h1>
        <p class="pcs-ph-sub">Track all your orders from production to delivery. Download invoices and reorder past prints.</p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-pbtn pcs-pbtn-ghost">Shop</a>
      </div>
    </div>
    <div class="pcs-ph-stats">
      <div><div class="pcs-ph-stat-val"><?php echo (int) $total_all; ?></div><div class="pcs-ph-stat-lbl">Total orders</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></div><div class="pcs-ph-stat-lbl">Total spent</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo (int) $count_production; ?></div><div class="pcs-ph-stat-lbl">In progress</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo (int) $count_delivered; ?></div><div class="pcs-ph-stat-lbl">Delivered</div></div>
    </div>
  </div>
</div>

<div style="padding:20px 24px;">

  <!-- Filter tabs -->
  <div class="pcs-orders-tabs">
    <a href="<?php echo esc_url( $tab_url( '' ) ); ?>" class="pcs-orders-tab <?php echo '' === $filter_status ? 'active' : ''; ?>">
      All <span class="pcs-orders-tab-count"><?php echo (int) $total_all; ?></span>
    </a>
    <a href="<?php echo esc_url( $tab_url( 'production' ) ); ?>" class="pcs-orders-tab <?php echo 'production' === $filter_status ? 'active' : ''; ?>">
      In production <span class="pcs-orders-tab-count"><?php echo (int) $count_production; ?></span>
    </a>
    <a href="<?php echo esc_url( $tab_url( 'shipped' ) ); ?>" class="pcs-orders-tab <?php echo 'shipped' === $filter_status ? 'active' : ''; ?>">
      Shipped <span class="pcs-orders-tab-count"><?php echo (int) $count_shipped; ?></span>
    </a>
    <a href="<?php echo esc_url( $tab_url( 'delivered' ) ); ?>" class="pcs-orders-tab <?php echo 'delivered' === $filter_status ? 'active' : ''; ?>">
      Delivered <span class="pcs-orders-tab-count"><?php echo (int) $count_delivered; ?></span>
    </a>
  </div>

  <!-- Orders table -->
  <div class="pcs-pcard" style="overflow:hidden;">
    <?php if ( empty( $orders ) ) : ?>
      <div style="text-align:center;padding:60px 20px;">
        <div style="font-size:40px;margin-bottom:12px;">📦</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px;">No orders found</div>
        <div style="font-size:13px;color:#6b7280;margin-bottom:16px;">Start designing and place your first order.</div>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-pbtn" style="background:#2563eb;color:#fff;">Browse products →</a>
      </div>
    <?php else : ?>
    <div class="pcs-orders-table-wrap">
      <table class="pcs-orders-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Product</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
            <th>Invoice</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $orders as $order ) :
            $status      = $order->get_status();
            $sl          = $status_styles[ $status ] ?? array( ucfirst( $status ), '#f3f4f6', '#6b7280' );
            $items       = $order->get_items();
            $first_item  = reset( $items );
            $product_name = $first_item ? $first_item->get_name() : '—';
            $extra_count  = count( $items ) > 1 ? ' +' . ( count( $items ) - 1 ) . ' more' : '';
            $order_date  = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y' ) : '';
            $thumb_text  = strtoupper( substr( $product_name, 0, 4 ) );
          ?>
          <?php
          $detail_url = add_query_arg( array( 'section' => 'order-detail', 'order_id' => $order->get_id() ), PCS_B2B_Portal::url( '' ) );
          ?>
          <tr class="pcs-orders-row" onclick="location.href='<?php echo esc_url( $detail_url ); ?>'">
            <td>
              <span class="pcs-orders-num">#<?php echo (int) $order->get_id(); ?></span>
            </td>
            <td>
              <div class="pcs-orders-product">
                <div class="pcs-orders-thumb"><?php echo esc_html( $thumb_text ); ?></div>
                <div>
                  <div class="pcs-orders-product-name"><?php echo esc_html( $product_name . $extra_count ); ?></div>
                  <div class="pcs-orders-product-meta"><?php echo (int) count( $items ); ?> item<?php echo count( $items ) > 1 ? 's' : ''; ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:13px;color:#6b7280;white-space:nowrap;"><?php echo esc_html( $order_date ); ?></td>
            <td style="font-size:14px;font-weight:600;white-space:nowrap;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
            <td>
              <span class="pcs-orders-status" style="background:<?php echo esc_attr( $sl[1] ); ?>;color:<?php echo esc_attr( $sl[2] ); ?>;">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;margin-right:4px;flex-shrink:0;"></span>
                <?php echo esc_html( $sl[0] ); ?>
              </span>
            </td>
            <td>
              <a href="<?php echo esc_url( $detail_url ); ?>" class="pcs-orders-invoice-btn" onclick="event.stopPropagation();" title="View order details">
                📄 View
              </a>
            </td>
            <td onclick="event.stopPropagation();">
              <button class="pcs-orders-reorder-btn"
                      data-order-id="<?php echo (int) $order->get_id(); ?>"
                      onclick="pcsReorder(<?php echo (int) $order->get_id(); ?>, this)"
                      title="Reorder this">
                ↻ Reorder
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ( $pages > 1 ) : ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid #f3f4f6;background:#f9fafb;">
      <span style="font-size:12px;color:#6b7280;">
        Showing <?php echo ( ( $paged - 1 ) * $per_page + 1 ); ?>–<?php echo min( $paged * $per_page, $total_count ); ?> of <?php echo (int) $total_count; ?> orders
      </span>
      <div style="display:flex;gap:4px;">
        <?php if ( $paged > 1 ) : ?>
          <a href="<?php echo esc_url( $page_url( $paged - 1 ) ); ?>" class="pcs-page-nav-btn">‹ Prev</a>
        <?php endif; ?>
        <span style="padding:6px 12px;background:#2563eb;color:#fff;border-radius:6px;font-size:12px;font-weight:600;"><?php echo (int) $paged; ?></span>
        <?php if ( $paged < $pages ) : ?>
          <a href="<?php echo esc_url( $page_url( $paged + 1 ) ); ?>" class="pcs-page-nav-btn">Next ›</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; /* empty orders */ ?>
  </div>

</div>

<!-- Reorder modal -->
<div id="pcs-reorder-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:28px;max-width:420px;width:90%;box-shadow:0 24px 60px rgba(0,0,0,.25);text-align:center;">
    <div style="font-size:32px;margin-bottom:12px;">↻</div>
    <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;">Reorder added to cart</h3>
    <p style="margin:0 0 20px;font-size:13px;color:#6b7280;" id="pcs-reorder-msg">Items from this order have been added to your cart.</p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="document.getElementById('pcs-reorder-modal').style.display='none'" style="padding:10px 18px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Continue shopping</button>
      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" style="padding:10px 18px;background:#2563eb;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">View cart →</a>
    </div>
  </div>
</div>

<script>
(function($) {
  window.pcsReorder = function(orderId, btn) {
    var $btn = $(btn).prop('disabled', true).text('Adding…');
    $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
      action:   'pcs_reorder',
      order_id: orderId,
      _nonce:   (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
    }, function(res) {
      if (res.success) {
        $('#pcs-reorder-msg').text(res.data.message || 'Items added to cart!');
        document.getElementById('pcs-reorder-modal').style.display = 'flex';
      } else {
        alert(res.data && res.data.message ? res.data.message : 'Could not add items to cart.');
      }
      $btn.prop('disabled', false).text('↻ Reorder');
    });
  };
})(jQuery);
</script>
