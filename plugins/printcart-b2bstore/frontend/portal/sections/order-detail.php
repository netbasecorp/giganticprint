<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Portal section: Order detail
 * Route: ?section=order-detail&order_id=XXX
 */

$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
$order    = $order_id ? wc_get_order( $order_id ) : null;

/* Security: must own this order */
if ( ! $order || $order->get_customer_id() !== $user_id ) { ?>
  <div style="padding:60px 24px;text-align:center;">
    <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
    <div style="font-size:16px;font-weight:600;margin-bottom:8px;">Order not found</div>
    <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'orders' ) ); ?>" class="pcs-pbtn" style="background:#2563eb;color:#fff;display:inline-flex;margin-top:4px;">← Back to orders</a>
  </div>
<?php return; }

/* ---- Gather order data ---- */
$status       = $order->get_status();
$order_date   = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y · g:i A' ) : '';
$items        = $order->get_items();
$first_item   = reset( $items );
$product_name = $first_item ? $first_item->get_name() : 'Order #' . $order_id;
$extra        = count( $items ) > 1 ? ' × ' . count( $items ) . ' items' : '';

/* Status display */
$status_map = array(
    'completed'  => array( 'Delivered',    '#dcfce7', '#065f46' ),
    'processing' => array( 'In production','#fef3c7', '#92400e' ),
    'on-hold'    => array( 'On hold',      '#fef3c7', '#92400e' ),
    'shipped'    => array( 'Shipped',      '#dbeafe', '#1e40af' ),
    'pending'    => array( 'Pending',      '#f3f4f6', '#6b7280' ),
    'cancelled'  => array( 'Cancelled',    '#fee2e2', '#991b1b' ),
    'refunded'   => array( 'Refunded',     '#fee2e2', '#991b1b' ),
);
$sl = $status_map[ $status ] ?? array( ucfirst( $status ), '#f3f4f6', '#6b7280' );

/* Shipping */
$shipping = $order->get_address( 'shipping' );
if ( empty( array_filter( $shipping ) ) ) {
    $shipping = $order->get_address( 'billing' );
}
$ship_name    = trim( ( $shipping['first_name'] ?? '' ) . ' ' . ( $shipping['last_name'] ?? '' ) );
$ship_company = $shipping['company'] ?? '';
$ship_addr    = trim( implode( ', ', array_filter( array(
    $shipping['address_1'] ?? '',
    $shipping['city']      ?? '',
    $shipping['state']     ?? '',
    $shipping['postcode']  ?? '',
) ) ) );

/* Payment method */
$payment_method = $order->get_payment_method_title() ?: 'N/A';

/* Tracking timeline based on status */
$timeline_steps = array(
    array(
        'label'  => 'Order placed',
        'desc'   => 'Payment confirmed',
        'done'   => true,
        'active' => false,
        'date'   => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j · g:i A' ) : '',
    ),
    array(
        'label'  => 'Processing',
        'desc'   => 'Preparing your order',
        'done'   => in_array( $status, array( 'processing', 'on-hold', 'shipped', 'completed' ) ),
        'active' => in_array( $status, array( 'processing', 'on-hold' ) ),
        'date'   => '',
    ),
    array(
        'label'  => 'Shipped',
        'desc'   => 'On its way to you',
        'done'   => in_array( $status, array( 'shipped', 'completed' ) ),
        'active' => $status === 'shipped',
        'date'   => '',
    ),
    array(
        'label'  => 'Delivered',
        'desc'   => 'Order complete',
        'done'   => $status === 'completed',
        'active' => $status === 'completed',
        'date'   => $order->get_date_completed() ? $order->get_date_completed()->date_i18n( 'M j · g:i A' ) : '',
    ),
);
if ( $status === 'cancelled' ) {
    $timeline_steps = array( array( 'label' => 'Order cancelled', 'desc' => 'This order was cancelled', 'done' => false, 'active' => false, 'date' => '' ) );
}
?>

<!-- Back link -->
<div style="padding:16px 24px 0;display:flex;align-items:center;gap:8px;font-size:13px;">
  <a href="<?php echo esc_url( PCS_B2B_Portal::url( 'orders' ) ); ?>"
     style="display:inline-flex;align-items:center;gap:6px;color:#6b7280;text-decoration:none;padding:6px 12px;border-radius:6px;transition:all .12s;"
     onmouseover="this.style.background='#f3f4f6';this.style.color='#111827'"
     onmouseout="this.style.background='';this.style.color='#6b7280'">
    ← Back to orders
  </a>
  <span style="color:#d1d5db;">/</span>
  <span style="color:#374151;font-weight:600;">#<?php echo (int) $order_id; ?></span>
</div>

<!-- Order header card -->
<div style="padding:16px 24px 0;">
  <div class="pcs-pcard" style="padding:0;overflow:hidden;margin-bottom:0;">
    <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
          <div style="font-size:12px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">
            Order #<?php echo (int) $order_id; ?> · Placed <?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y' ) : '' ); ?>
          </div>
          <h2 style="font-size:22px;font-weight:800;margin:0 0 8px;color:#111827;">
            <?php echo esc_html( $product_name . $extra ); ?>
          </h2>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:9999px;font-size:12px;font-weight:600;background:<?php echo esc_attr($sl[1]); ?>;color:<?php echo esc_attr($sl[2]); ?>;">
              <span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span>
              <?php echo esc_html( $sl[0] ); ?>
            </span>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;align-items:flex-start;">
          <div style="display:inline-flex;flex-direction:column;align-items:center;gap:3px;">
            <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f9fafb;border:1px dashed #d1d5db;border-radius:8px;font-size:13px;font-weight:600;color:#9ca3af;cursor:not-allowed;">
              📄 Invoice PDF
            </span>
            <span style="font-size:10px;background:#f3f4f6;color:#9ca3af;padding:1px 8px;border-radius:9999px;letter-spacing:.3px;">Coming soon</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main 2-col layout -->
<div style="padding:16px 24px 32px;display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;">

  <!-- LEFT -->
  <div>

    <!-- Order tracking -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head pcs-pcard-head-brand" style="justify-content:space-between;">
        <h3 class="pcs-pcard-title">📍 Order tracking</h3>
      </div>
      <div style="padding:20px 24px;">
        <?php foreach ( $timeline_steps as $i => $step ) :
          $is_last = $i === count( $timeline_steps ) - 1;
        ?>
        <div style="display:grid;grid-template-columns:32px 1fr auto;gap:14px;<?php echo $is_last ? '' : 'padding-bottom:24px;position:relative;'; ?>">
          <?php if ( ! $is_last ) : ?>
          <div style="position:absolute;left:15px;top:32px;bottom:0;width:2px;background:<?php echo $step['done'] ? '#16a34a' : '#e5e7eb'; ?>;"></div>
          <?php endif; ?>
          <!-- Dot -->
          <div style="width:32px;height:32px;border-radius:50%;display:grid;place-items:center;flex-shrink:0;position:relative;z-index:1;
            <?php if ( $step['done'] ) echo 'background:#16a34a;color:#fff;';
                  elseif ( $step['active'] ) echo 'background:#2563eb;color:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.15);';
                  else echo 'background:#fff;border:2px solid #e5e7eb;color:#9ca3af;'; ?>">
            <?php if ( $step['done'] ) : ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?php elseif ( $step['active'] ) : ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4" fill="white"/></svg>
            <?php else : ?>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>
            <?php endif; ?>
          </div>
          <!-- Content -->
          <div style="padding-top:5px;">
            <div style="font-size:14px;font-weight:600;color:<?php echo $step['active'] ? '#2563eb' : ($step['done'] ? '#111827' : '#9ca3af'); ?>;">
              <?php echo esc_html( $step['label'] ); ?>
            </div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;"><?php echo esc_html( $step['desc'] ); ?></div>
          </div>
          <!-- Date -->
          <div style="font-size:11px;color:#9ca3af;padding-top:6px;white-space:nowrap;">
            <?php echo esc_html( $step['date'] ); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Items -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head">
        <h3 class="pcs-pcard-title">🛍️ Items (<?php echo (int) count( $items ); ?>)</h3>
      </div>
      <div style="padding:0;">
        <?php foreach ( $items as $item ) :
          $product      = $item->get_product();
          $item_name    = $item->get_name();
          $qty          = $item->get_quantity();
          $line_total   = $item->get_total();
          $unit_price   = $qty > 0 ? $line_total / $qty : 0;
          $thumb_text   = strtoupper( substr( $item_name, 0, 4 ) );
          /* Product image */
          $img_url = $product ? get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ) : '';
        ?>
        <div style="display:grid;grid-template-columns:64px 1fr auto;gap:14px;align-items:start;padding:16px 18px;border-bottom:1px solid #f3f4f6;">
          <!-- Thumb -->
          <div style="width:64px;height:64px;border-radius:8px;overflow:hidden;flex-shrink:0;">
            <?php if ( $img_url ) : ?>
              <img src="<?php echo esc_url( $img_url ); ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
            <?php else : ?>
              <div style="width:100%;height:100%;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:grid;place-items:center;color:#fbbf24;font-size:11px;font-weight:800;"><?php echo esc_html( $thumb_text ); ?></div>
            <?php endif; ?>
          </div>
          <!-- Info -->
          <div>
            <div style="font-size:14px;font-weight:600;color:#111827;margin-bottom:4px;"><?php echo esc_html( $item_name ); ?></div>
            <!-- Meta/variation -->
            <?php $meta = $item->get_formatted_meta_data(); ?>
            <?php if ( $meta ) : ?>
            <div style="font-size:12px;color:#9ca3af;line-height:1.5;">
              <?php foreach ( $meta as $entry ) :
                echo '<span style="margin-right:6px;">' . wp_kses_post( $entry->display_key ) . ': ' . wp_kses_post( $entry->display_value ) . '</span>';
              endforeach; ?>
            </div>
            <?php endif; ?>
            <div style="font-size:12px;color:#9ca3af;margin-top:4px;">
              Qty: <strong><?php echo (int) $qty; ?></strong>
              · Unit price: <strong><?php echo wp_kses_post( wc_price( $unit_price ) ); ?></strong>
            </div>
          </div>
          <!-- Price -->
          <div style="text-align:right;">
            <div style="font-size:15px;font-weight:700;color:#111827;"><?php echo wp_kses_post( wc_price( $line_total ) ); ?></div>
            <?php if ( $qty > 1 ) : ?>
            <div style="font-size:11px;color:#9ca3af;"><?php echo (int) $qty; ?> × <?php echo wp_kses_post( wc_price( $unit_price ) ); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /LEFT -->

  <!-- RIGHT -->
  <div>

    <!-- Payment summary -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">💳 Payment summary</h3></div>
      <div style="padding:16px 18px;">
        <?php
        $subtotal      = $order->get_subtotal();
        $discount      = $order->get_total_discount();
        $shipping_cost = $order->get_shipping_total();
        $tax           = $order->get_total_tax();
        $total         = $order->get_total();
        ?>
        <div class="pcs-od-row"><span>Subtotal</span><span><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></span></div>
        <?php if ( $discount > 0 ) : ?>
        <div class="pcs-od-row" style="color:#16a34a;"><span>Discount</span><span>−<?php echo wp_kses_post( wc_price( $discount ) ); ?></span></div>
        <?php endif; ?>
        <div class="pcs-od-row"><span>Shipping</span><span><?php echo $shipping_cost > 0 ? wp_kses_post( wc_price( $shipping_cost ) ) : 'Free'; ?></span></div>
        <?php if ( $tax > 0 ) : ?>
        <div class="pcs-od-row"><span>Tax</span><span><?php echo wp_kses_post( wc_price( $tax ) ); ?></span></div>
        <?php endif; ?>
        <div class="pcs-od-row pcs-od-total"><span>Total paid</span><span><?php echo wp_kses_post( wc_price( $total ) ); ?></span></div>
        <div style="font-size:12px;color:#9ca3af;margin-top:10px;padding-top:10px;border-top:1px solid #f3f4f6;">
          <span>Paid via <?php echo esc_html( $payment_method ); ?></span>
        </div>
      </div>
    </div>

    <!-- Shipping address -->
    <?php if ( $ship_name || $ship_addr ) : ?>
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">📍 Shipping to</h3></div>
      <div style="padding:16px 18px;font-size:13px;line-height:1.7;color:#374151;">
        <?php if ( $ship_name )    echo esc_html( $ship_name ) . '<br>'; ?>
        <?php if ( $ship_company ) echo esc_html( $ship_company ) . '<br>'; ?>
        <?php if ( $ship_addr )    echo esc_html( $ship_addr ); ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="pcs-pcard">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">⚡ Quick actions</h3></div>
      <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">
        <button onclick="pcsReorderFromDetail(<?php echo (int) $order_id; ?>, this)"
                style="width:100%;padding:10px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;">
          ↻ Reorder this
        </button>
        <?php if ( $order->is_editable() ) : ?>
        <a href="<?php echo esc_url( $order->get_cancel_order_url( PCS_B2B_Portal::url( 'orders' ) ) ); ?>"
           onclick="return confirm('Cancel this order?')"
           style="width:100%;padding:10px;background:#fff;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;box-sizing:border-box;">
          ✕ Cancel order
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /RIGHT -->
</div><!-- /2-col -->

<!-- Reorder modal (same as orders list) -->
<div id="pcs-reorder-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:28px;max-width:420px;width:90%;box-shadow:0 24px 60px rgba(0,0,0,.25);text-align:center;">
    <div style="font-size:32px;margin-bottom:12px;">↻</div>
    <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;">Reorder added to cart</h3>
    <p style="margin:0 0 20px;font-size:13px;color:#6b7280;" id="pcs-reorder-msg">Items added to your cart.</p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="document.getElementById('pcs-reorder-modal').style.display='none'" style="padding:10px 18px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Continue shopping</button>
      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" style="padding:10px 18px;background:#2563eb;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">View cart →</a>
    </div>
  </div>
</div>

<style>
.pcs-od-row { display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:13px;color:#374151;border-bottom:1px solid #f9fafb; }
.pcs-od-row:last-of-type { border-bottom:none; }
.pcs-od-total { font-size:16px;font-weight:800;color:#111827;padding-top:10px;margin-top:6px;border-top:2px solid #e5e7eb !important;border-bottom:none !important; }
@media (max-width:768px) {
  div[style*="grid-template-columns:1fr 300px"] { grid-template-columns:1fr !important; }
}
</style>

<script>
(function($) {
  window.pcsReorderFromDetail = function(orderId, btn) {
    var $btn = $(btn).prop('disabled', true).html('Adding…');
    $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
      action: 'pcs_reorder', order_id: orderId,
      _nonce: (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
    }, function(res) {
      if (res.success) {
        $('#pcs-reorder-msg').text(res.data.message || 'Items added to cart!');
        document.getElementById('pcs-reorder-modal').style.display = 'flex';
      } else {
        alert(res.data && res.data.message ? res.data.message : 'Could not add items.');
      }
      $btn.prop('disabled', false).html('↻ Reorder this');
    });
  };
})(jQuery);
</script>
