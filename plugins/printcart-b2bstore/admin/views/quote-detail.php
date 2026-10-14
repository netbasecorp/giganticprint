<?php if ( ! defined( 'ABSPATH' ) ) exit;

$quote_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$is_new    = 'new' === ( $_GET['id'] ?? '' );
$quote     = $is_new ? null : ( $quote_id ? PCS_Quotes::get( $quote_id ) : null );
$items     = $quote ? PCS_Quotes::get_items( $quote_id ) : array();
$back_url  = admin_url( 'admin.php?page=printcart-quotes' );
$tiers     = PCS_DB::get_tiers_from_rules();

if ( ! $is_new && ! $quote ) {
    echo '<div class="pcs-wrap"><p>Quote not found. <a href="' . esc_url( $back_url ) . '">← Back</a></p></div>';
    return;
}

$status_labels = array(
    'new_request' => 'New request', 'in_review' => 'In review',
    'sent'        => 'Sent',        'accepted'  => 'Accepted',
    'declined'    => 'Declined',    'expired'   => 'Expired',
);
$is_sent     = $quote && in_array( $quote->status, array( 'sent', 'accepted', 'declined', 'expired' ), true );
$can_send    = $quote && in_array( $quote->status, array( 'in_review', 'new_request' ), true );

/* Customer list for new quote */
$customers = get_users( array( 'role__in' => array( 'customer', 'subscriber' ), 'number' => 100 ) );
?>
<div class="pcs-wrap">
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <a href="<?php echo esc_url( $back_url ); ?>" style="font-size:13px;color:#6b7280;text-decoration:none;display:block;margin-bottom:4px;">← Back to quotes</a>
      <h1><?php echo $is_new ? 'New Quote' : esc_html( $quote->quote_number ); ?></h1>
      <?php if ( $quote ) : ?>
        <p class="pcs-page-sub">
          Customer: <strong><?php echo esc_html( $quote->display_name ); ?></strong> ·
          Created <?php echo esc_html( human_time_diff( strtotime( $quote->created_at ), time() ) ); ?> ago
        </p>
      <?php endif; ?>
    </div>
    <div class="pcs-page-actions">
      <?php if ( $quote && ! $is_sent ) : ?>
        <button id="pcs-btn-send-quote" class="pcs-btn pcs-btn-primary" onclick="pcsSendQuote(<?php echo (int) $quote_id; ?>)" <?php echo ! $can_send ? 'disabled title="Save and set to In review first"' : ''; ?>>
          📤 Send to customer
        </button>
      <?php elseif ( $quote && 'sent' === $quote->status ) : ?>
        <button class="pcs-btn pcs-btn-secondary" onclick="pcsSendQuote(<?php echo (int) $quote_id; ?>)">↺ Resend</button>
      <?php endif; ?>
      <?php if ( $quote ) : ?>
        <button class="pcs-btn pcs-btn-danger pcs-btn-sm" onclick="pcsConfirmDelete(<?php echo (int) $quote_id; ?>)">Delete</button>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    <!-- LEFT: form -->
    <div>
      <div class="pcs-card">
        <div class="pcs-card-head is-brand"><span class="pcs-card-title">Quote details</span></div>
        <div class="pcs-card-body">
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Status</label>
            <select id="pcs-q-status" class="pcs-select" style="max-width:180px;" <?php echo $is_sent ? 'disabled' : ''; ?>>
              <?php foreach ( $status_labels as $v => $l ) : ?>
                <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $quote ? $quote->status : 'in_review', $v ); ?>><?php echo esc_html( $l ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ( $is_new ) : ?>
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Customer *</label>
            <select id="pcs-q-user" class="pcs-select" style="max-width:280px;">
              <option value="">— Select customer —</option>
              <?php foreach ( $customers as $cu ) : ?>
                <option value="<?php echo (int) $cu->ID; ?>"><?php echo esc_html( $cu->display_name . ' (' . $cu->user_email . ')' ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Title *</label>
            <input type="text" id="pcs-q-title" class="pcs-input" placeholder="e.g. 50 step & repeat backdrops" value="<?php echo esc_attr( $quote->title ?? '' ); ?>" <?php echo $is_sent ? 'readonly' : ''; ?>>
          </div>
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;">
            <label class="pcs-form-label">Valid until</label>
            <input type="date" id="pcs-q-valid" class="pcs-input" style="max-width:160px;" value="<?php echo esc_attr( $quote->valid_until ?? '' ); ?>" <?php echo $is_sent ? 'readonly' : ''; ?>>
          </div>
          <div class="pcs-form-row" style="grid-template-columns:120px 1fr;gap:12px;padding:8px 0;border:none;">
            <label class="pcs-form-label">Notes to customer</label>
            <textarea id="pcs-q-notes" class="pcs-textarea" rows="3" placeholder="Any additional notes included in the quote email…" <?php echo $is_sent ? 'readonly' : ''; ?>><?php echo esc_textarea( $quote->admin_notes ?? '' ); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Line items -->
      <div class="pcs-card" style="margin-top:16px;">
        <div class="pcs-card-head">
          <span class="pcs-card-title">📦 Quote line items</span>
          <?php if ( ! $is_sent ) : ?>
            <button type="button" onclick="pcsAddQuoteItem()" class="pcs-btn pcs-btn-secondary pcs-btn-sm">+ Add line item</button>
          <?php endif; ?>
        </div>
        <div class="pcs-card-body p0">
          <table class="pcs-table" id="pcs-quote-items-table" style="table-layout:fixed;">
            <thead>
              <tr style="background:#f8fafc;">
                <th style="width:50%;">Description</th>
                <th style="width:12%;">Qty</th>
                <th style="width:16%;">Unit price</th>
                <th style="width:14%;text-align:right;padding-right:12px;">Total</th>
                <?php if ( ! $is_sent ) : ?><th style="width:8%;"></th><?php endif; ?>
              </tr>
            </thead>
            <tbody id="pcs-quote-items-body">
              <?php if ( empty( $items ) && ! $is_new ) : ?>
              <tr id="pcs-qi-empty"><td colspan="5" style="text-align:center;padding:28px;color:var(--pcs-text-soft);">No items yet — click + Add line item.</td></tr>
              <?php endif; ?>
              <?php foreach ( $items as $item ) : ?>
              <tr class="pcs-qi-row" style="vertical-align:top;">
                <td style="padding:10px 12px;">
                  <input type="text" class="pcs-input pcs-qi-desc" placeholder="Item description" value="<?php echo esc_attr( $item->description ); ?>" style="margin-bottom:4px;" <?php echo $is_sent ? 'readonly' : ''; ?>>
                  <input type="text" class="pcs-input pcs-qi-notes" placeholder="Optional notes / specs" value="<?php echo esc_attr( $item->notes ?? '' ); ?>" style="font-size:11px;color:#6b7280;padding:4px 8px;" <?php echo $is_sent ? 'readonly' : ''; ?>>
                </td>
                <td style="padding:10px 8px;">
                  <input type="number" class="pcs-input pcs-qi-qty" value="<?php echo (int) $item->quantity; ?>" min="1" style="width:100%;" onchange="pcsRecalcRow(this)" <?php echo $is_sent ? 'readonly' : ''; ?>>
                </td>
                <td style="padding:10px 8px;">
                  <div style="display:flex;align-items:center;gap:4px;">
                    <span style="color:#6b7280;font-weight:600;">$</span>
                    <input type="number" class="pcs-input pcs-qi-price" value="<?php echo (float) $item->unit_price; ?>" min="0" step="0.01" style="width:100%;" onchange="pcsRecalcRow(this)" <?php echo $is_sent ? 'readonly' : ''; ?>>
                  </div>
                </td>
                <td class="pcs-qi-total" style="padding:10px 12px;font-weight:700;text-align:right;font-size:14px;"><?php echo '$' . number_format( $item->total, 2 ); ?></td>
                <?php if ( ! $is_sent ) : ?>
                <td style="padding:10px 8px;text-align:center;">
                  <button type="button" onclick="this.closest('tr').remove();pcsRecalcTotals()" style="background:none;border:none;color:#d1d5db;cursor:pointer;font-size:18px;line-height:1;" title="Remove">&times;</button>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div style="padding:16px 20px;background:#f8fafc;border-top:1px solid #eef2f6;border-radius:0 0 8px 8px;">
          <div style="max-width:340px;margin-left:auto;">
            <!-- Subtotal -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:13px;">
              <span style="color:#6b7280;">Subtotal</span>
              <span id="pcs-q-subtotal-display" style="font-weight:600;">$<?php echo $quote ? number_format( $quote->subtotal, 2 ) : '0.00'; ?></span>
            </div>
            <!-- Discount -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:13px;border-bottom:1px solid #e5e7eb;">
              <span style="color:#6b7280;">Volume discount ($)</span>
              <div style="display:flex;align-items:center;gap:6px;">
                <?php if ( ! $is_sent ) : ?>
                <input type="number" id="pcs-q-discount" class="pcs-input" value="<?php echo $quote ? (float) $quote->discount : 0; ?>" min="0" step="0.01" style="width:80px;text-align:right;padding:4px 8px;" onchange="pcsRecalcTotals()">
                <?php else : ?>
                <span><?php echo $quote && $quote->discount > 0 ? '$' . number_format($quote->discount, 2) : '—'; ?></span>
                <?php endif; ?>
                <span id="pcs-q-discount-display" style="color:#059669;font-weight:600;min-width:60px;text-align:right;">
                  <?php echo $quote && $quote->discount > 0 ? '-$' . number_format($quote->discount, 2) : ''; ?>
                </span>
              </div>
            </div>
            <!-- Total -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0 4px;font-size:16px;font-weight:700;">
              <span style="color:#111827;">Quote total</span>
              <span id="pcs-q-total-display" style="color:#2563eb;font-size:18px;">$<?php echo $quote ? number_format( $quote->total, 2 ) : '0.00'; ?></span>
            </div>
            <?php if ( $quote && $quote->total > 0 && count( $items ) > 0 ) :
              $total_qty = array_sum( array_column( $items, 'quantity' ) );
              $per_unit  = $total_qty > 0 ? $quote->total / $total_qty : 0;
            ?>
            <div style="font-size:11px;color:#9ca3af;text-align:right;margin-top:2px;">
              Per unit avg: ~$<?php echo number_format( $per_unit, 2 ); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ( ! $is_sent ) : ?>
      <div style="margin-top:12px;display:flex;gap:8px;">
        <button id="pcs-btn-save-quote" onclick="pcsSaveQuote(<?php echo $quote_id ?: 0; ?>)" class="pcs-btn pcs-btn-primary">💾 Save quote</button>
        <a href="<?php echo esc_url( $back_url ); ?>" class="pcs-btn pcs-btn-tertiary">Cancel</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: sidebar -->
    <div>
      <?php if ( $quote ) : ?>
      <!-- Customer info -->
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head"><span class="pcs-card-title">Customer</span></div>
        <div class="pcs-card-body" style="font-size:13px;line-height:1.8;">
          <div style="font-weight:600;"><?php echo esc_html( $quote->display_name ); ?></div>
          <div style="color:var(--pcs-text-soft);"><?php echo esc_html( $quote->user_email ); ?></div>
          <?php
          $store = PCS_DB::get_store_by_user( $quote->user_id );
          if ( $store ) :
          ?>
          <div style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;">
            <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:2px;">B2B Store</div>
            <div><?php echo esc_html( $store->company_name ); ?></div>
            <?php echo PCS_DB::tier_pill_html( $store->tier ); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Original request -->
      <?php if ( $quote && $quote->message ) : ?>
      <div class="pcs-card" style="margin-bottom:16px;">
        <div class="pcs-card-head"><span class="pcs-card-title">📋 Request message</span></div>
        <div class="pcs-card-body" style="font-size:13px;color:#374151;line-height:1.6;">
          <?php echo nl2br( esc_html( $quote->message ) ); ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Activity -->
      <?php if ( $quote ) : ?>
      <div class="pcs-card">
        <div class="pcs-card-head"><span class="pcs-card-title">Activity</span></div>
        <div class="pcs-card-body" style="font-size:12px;color:var(--pcs-text-soft);line-height:2;">
          <div>Created: <?php echo esc_html( date_i18n( 'M j, Y g:i A', strtotime( $quote->created_at ) ) ); ?></div>
          <?php if ( $quote->sent_at ) : ?><div>Sent: <?php echo esc_html( date_i18n( 'M j, Y g:i A', strtotime( $quote->sent_at ) ) ); ?></div><?php endif; ?>
          <?php if ( $quote->responded_at ) : ?><div>Responded: <?php echo esc_html( date_i18n( 'M j, Y g:i A', strtotime( $quote->responded_at ) ) ); ?></div><?php endif; ?>
          <?php if ( $quote->valid_until ) : ?><div>Valid until: <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $quote->valid_until ) ) ); ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Delete confirmation modal -->
<div id="pcs-delete-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:99999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:28px;width:380px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.2);text-align:center;">
    <div style="font-size:36px;margin-bottom:12px;">🗑️</div>
    <h3 style="margin:0 0 8px;font-size:17px;font-weight:700;color:#111827;">Delete this quote?</h3>
    <p style="margin:0 0 22px;font-size:13px;color:#6b7280;">
      Quote <strong><?php echo esc_html( $quote ? $quote->quote_number : '' ); ?></strong> will be permanently deleted. This cannot be undone.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button id="pcs-delete-cancel" style="padding:10px 22px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button id="pcs-delete-confirm" style="padding:10px 22px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">🗑️ Delete</button>
    </div>
  </div>
</div>

<!-- Send confirmation modal -->
<div id="pcs-send-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:99999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:28px;width:400px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.2);text-align:center;">
    <div style="font-size:36px;margin-bottom:12px;">📤</div>
    <h3 style="margin:0 0 8px;font-size:17px;font-weight:700;color:#111827;">Send quote to customer?</h3>
    <p style="margin:0 0 22px;font-size:13px;color:#6b7280;">
      <strong><?php echo esc_html( $quote ? $quote->display_name : '' ); ?></strong> will receive an email with the quote details and Accept / Decline buttons.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button id="pcs-send-cancel" style="padding:10px 22px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button id="pcs-send-confirm" style="padding:10px 22px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">📤 Send quote</button>
    </div>
  </div>
</div>

<script>
(function($) {
    var quoteId   = <?php echo $quote_id ?: 0; ?>;
    var backUrl   = <?php echo wp_json_encode( $back_url ); ?>;
    var ajaxUrl   = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce     = <?php echo wp_json_encode( wp_create_nonce( 'pcs_ajax' ) ); ?>;

    function pcsGetItems() {
        var rows = [];
        $('#pcs-quote-items-body .pcs-qi-row').each(function() {
            rows.push({
                description: $(this).find('.pcs-qi-desc').val(),
                notes:       $(this).find('.pcs-qi-notes').val(),
                quantity:    $(this).find('.pcs-qi-qty').val() || 1,
                unit_price:  $(this).find('.pcs-qi-price').val() || 0,
                product_id:  0,
            });
        });
        return rows;
    }

    window.pcsRecalcRow = function(el) {
        var $row  = $(el).closest('tr');
        var qty   = parseFloat($row.find('.pcs-qi-qty').val()) || 0;
        var price = parseFloat($row.find('.pcs-qi-price').val()) || 0;
        $row.find('.pcs-qi-total').text('$' + (qty * price).toFixed(2));
        pcsRecalcTotals();
    };

    window.pcsRecalcTotals = function() {
        var sub = 0;
        $('#pcs-quote-items-body .pcs-qi-row').each(function() {
            var t = $(this).find('.pcs-qi-total').text().replace(/[$,]/g,'');
            sub += parseFloat(t) || 0;
        });
        var disc  = parseFloat($('#pcs-q-discount').val()) || 0;
        var total = Math.max(0, sub - disc);
        $('#pcs-q-subtotal-display').text('$' + sub.toFixed(2));
        $('#pcs-q-total-display').text('$' + total.toFixed(2));
        $('#pcs-q-discount-display').text(disc > 0 ? '-$' + disc.toFixed(2) : '');
    };

    window.pcsAddQuoteItem = function() {
        $('#pcs-qi-empty').remove();
        var row = '<tr class="pcs-qi-row" style="vertical-align:top;">'
                + '<td style="padding:10px 12px;">'
                +   '<input type="text" class="pcs-input pcs-qi-desc" placeholder="Item description" style="margin-bottom:4px;">'
                +   '<input type="text" class="pcs-input pcs-qi-notes" placeholder="Optional notes / specs" style="font-size:11px;color:#6b7280;padding:4px 8px;">'
                + '</td>'
                + '<td style="padding:10px 8px;"><input type="number" class="pcs-input pcs-qi-qty" value="1" min="1" style="width:100%;" onchange="pcsRecalcRow(this)"></td>'
                + '<td style="padding:10px 8px;"><div style="display:flex;align-items:center;gap:4px;"><span style="color:#6b7280;font-weight:600;">$</span><input type="number" class="pcs-input pcs-qi-price" value="0" min="0" step="0.01" style="width:100%;" onchange="pcsRecalcRow(this)"></div></td>'
                + '<td class="pcs-qi-total" style="padding:10px 12px;font-weight:700;text-align:right;font-size:14px;">$0.00</td>'
                + '<td style="padding:10px 8px;text-align:center;"><button type="button" onclick="this.closest(\'tr\').remove();pcsRecalcTotals()" style="background:none;border:none;color:#d1d5db;cursor:pointer;font-size:18px;line-height:1;">&times;</button></td>'
                + '</tr>';
        $('#pcs-quote-items-body').append(row);
        $('#pcs-quote-items-body .pcs-qi-row:last .pcs-qi-desc').focus();
    };

    window.pcsSaveQuote = function(id) {
        var $btn = $('#pcs-btn-save-quote').prop('disabled', true).text('Saving…');
        $.post(ajaxUrl, {
            action:      'pcs_save_quote',
            _nonce:      nonce,
            quote_id:    id,
            user_id:     $('#pcs-q-user').val() || 0,
            status:      $('#pcs-q-status').val(),
            title:       $('#pcs-q-title').val(),
            admin_notes: $('#pcs-q-notes').val(),
            discount:    $('#pcs-q-discount').val(),
            valid_until: $('#pcs-q-valid').val(),
            items:       pcsGetItems(),
        }, function(res) {
            $btn.prop('disabled', false).html('💾 Save quote');
            if (res.success) {
                pcsToast(res.data.message, 'success');
                setTimeout(function() {
                    if (!id && res.data.id) {
                        location.href = backUrl + '&action=view&id=' + res.data.id;
                    } else {
                        location.reload();
                    }
                }, 800);
            } else {
                pcsToast(res.data && res.data.message ? res.data.message : 'Error saving.', 'error');
            }
        });
    };

    /* Delete modal */
    var _pendingDeleteId = null;
    window.pcsConfirmDelete = function(id) {
        _pendingDeleteId = id;
        document.getElementById('pcs-delete-modal').style.display = 'flex';
    };
    document.getElementById('pcs-delete-confirm').addEventListener('click', function() {
        document.getElementById('pcs-delete-modal').style.display = 'none';
        if (_pendingDeleteId) pcsDeleteQuote(_pendingDeleteId);
    });
    document.getElementById('pcs-delete-cancel').addEventListener('click', function() {
        document.getElementById('pcs-delete-modal').style.display = 'none';
    });
    document.getElementById('pcs-delete-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });

    var _pendingSendId = null;
    window.pcsSendQuote = function(id) {
        _pendingSendId = id;
        document.getElementById('pcs-send-modal').style.display = 'flex';
    };
    document.getElementById('pcs-send-confirm').addEventListener('click', function() {
        var id  = _pendingSendId;
        document.getElementById('pcs-send-modal').style.display = 'none';
        if (!id) return;
        var $btn = $('#pcs-btn-send-quote').prop('disabled', true).text('Sending…');
        $.post(ajaxUrl, { action: 'pcs_send_quote', _nonce: nonce, quote_id: id }, function(res) {
            $btn.prop('disabled', false).text('📤 Send to customer');
            if (res.success) { pcsToast(res.data.message, 'success'); setTimeout(function(){ location.reload(); }, 1000); }
            else pcsToast(res.data && res.data.message ? res.data.message : 'Error sending.', 'error');
        });
    });
    document.getElementById('pcs-send-cancel').addEventListener('click', function() {
        document.getElementById('pcs-send-modal').style.display = 'none';
    });
    document.getElementById('pcs-send-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });

    window.pcsDeleteQuote = function(id) {
        $.post(ajaxUrl, { action: 'pcs_delete_quote', _nonce: nonce, quote_id: id }, function(res) {
            if (res.success) { pcsToast('Quote deleted.', 'success'); setTimeout(function(){ location.href = backUrl; }, 800); }
        });
    };
})(jQuery);
</script>
