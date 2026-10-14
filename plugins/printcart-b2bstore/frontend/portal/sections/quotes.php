<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var int $user_id @var WP_User $user */

$filter    = isset( $_GET['qstatus'] ) ? sanitize_text_field( wp_unslash( $_GET['qstatus'] ) ) : '';
$portal_url = PCS_B2B_Portal::url( 'quotes' );

/* Handle accept/decline from email link */
$pcs_qa  = isset( $_GET['pcs_quote_action'] ) ? sanitize_text_field( $_GET['pcs_quote_action'] ) : '';
$pcs_qid = isset( $_GET['quote_id'] )         ? absint( $_GET['quote_id'] )                       : 0;
$action_msg  = '';
$action_type = '';
if ( $pcs_qa && $pcs_qid && is_user_logged_in() ) {
    $q = PCS_Quotes::get( $pcs_qid );
    if ( $q && (int) $q->user_id === $user_id && 'sent' === $q->status ) {
        $new_status = 'accept' === $pcs_qa ? 'accepted' : 'declined';
        PCS_Quotes::update( $pcs_qid, array( 'status' => $new_status, 'responded_at' => current_time( 'mysql' ) ) );
        $action_msg  = 'accept' === $pcs_qa ? '✅ Quote accepted! Our team will contact you shortly.' : 'Quote declined.';
        $action_type = 'accept' === $pcs_qa ? 'success' : 'info';
    }
}

/* Stats */
$total_all    = PCS_Quotes::count( array( 'user_id' => $user_id ) );
$awaiting     = PCS_Quotes::count( array( 'user_id' => $user_id, 'status' => 'sent' ) );
$accepted_cnt = PCS_Quotes::count( array( 'user_id' => $user_id, 'status' => 'accepted' ) );
$quoted_val   = 0;
$all_accepted = PCS_Quotes::get_list( array( 'user_id' => $user_id, 'status' => 'accepted', 'per_page' => 100 ) );
foreach ( $all_accepted as $aq ) $quoted_val += (float) $aq->total;

/* Quote list */
$my_quotes = PCS_Quotes::get_list( array( 'user_id' => $user_id, 'status' => $filter, 'per_page' => 50 ) );

$status_map = array(
    'new_request' => array( 'Submitted',              '#fef3c7', '#92400e', '#d97706' ),
    'in_review'   => array( 'In review',              '#dbeafe', '#1e40af', '#2563eb' ),
    'sent'        => array( 'Quote sent · awaiting your response', '#e0e7ff', '#3730a3', '#4f46e5' ),
    'accepted'    => array( 'Accepted',               '#dcfce7', '#065f46', '#059669' ),
    'declined'    => array( 'Declined',               '#fee2e2', '#991b1b', '#dc2626' ),
    'expired'     => array( 'Expired',                '#f3f4f6', '#6b7280', '#9ca3af' ),
);
?>

<!-- Page header -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">📋</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( get_bloginfo('name') ); ?> · Custom Quotes</div>
        <h1 class="pcs-ph-title">My Quotes</h1>
        <p class="pcs-ph-sub">Track all your custom quote requests — pending review, sent, accepted, or declined.</p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( PCS_B2B_Portal::url('quote-request') ); ?>" class="pcs-pbtn pcs-pbtn-primary">+ New request</a>
      </div>
    </div>
    <div class="pcs-ph-stats">
      <div><div class="pcs-ph-stat-val"><?php echo (int) $total_all; ?></div><div class="pcs-ph-stat-lbl">Total quotes</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo (int) $awaiting; ?></div><div class="pcs-ph-stat-lbl">Awaiting your action</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo $quoted_val > 0 ? '$' . number_format($quoted_val, 0) : '—'; ?></div><div class="pcs-ph-stat-lbl">Quoted value</div></div>
      <div><div class="pcs-ph-stat-val"><?php echo (int) $accepted_cnt; ?></div><div class="pcs-ph-stat-lbl">Accepted</div></div>
    </div>
  </div>
</div>

<div style="padding:20px 24px;">

  <?php if ( $action_msg ) : ?>
  <div style="background:<?php echo $action_type === 'success' ? '#dcfce7' : '#dbeafe'; ?>;color:<?php echo $action_type === 'success' ? '#065f46' : '#1e40af'; ?>;padding:14px 18px;border-radius:8px;font-size:14px;font-weight:600;margin-bottom:16px;">
    <?php echo esc_html( $action_msg ); ?>
  </div>
  <?php endif; ?>

  <!-- Filter tabs -->
  <div style="display:flex;gap:2px;margin-bottom:16px;border-bottom:2px solid #e5e7eb;flex-wrap:wrap;">
    <?php
    $qtabs = array(
        ''            => array( 'All',              $total_all ),
        'sent'        => array( 'Awaiting response', $awaiting ),
        'accepted'    => array( 'Accepted',          $accepted_cnt ),
        'declined'    => array( 'Declined',          PCS_Quotes::count( array( 'user_id' => $user_id, 'status' => 'declined' ) ) ),
    );
    foreach ( $qtabs as $qs => $qd ) :
      $active = $filter === $qs;
    ?>
    <a href="<?php echo esc_url( add_query_arg( 'qstatus', $qs, $portal_url ) ); ?>"
       style="font-size:13px;font-weight:<?php echo $active ? '700' : '500'; ?>;padding:8px 16px;text-decoration:none;
              color:<?php echo $active ? '#2563eb' : '#6b7280'; ?>;
              border-bottom:<?php echo $active ? '2px solid #2563eb' : '2px solid transparent'; ?>;
              margin-bottom:-2px;white-space:nowrap;">
      <?php echo esc_html( $qd[0] ); ?>
      <?php if ( $qd[1] > 0 ) : ?>
        <span style="font-size:11px;background:<?php echo $active ? '#dbeafe' : '#f3f4f6'; ?>;color:<?php echo $active ? '#1e40af' : '#6b7280'; ?>;padding:1px 7px;border-radius:9999px;font-weight:700;margin-left:4px;"><?php echo (int) $qd[1]; ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Table -->
  <?php if ( empty( $my_quotes ) ) : ?>
  <div class="pcs-pcard">
    <div class="pcs-pcard-body" style="text-align:center;padding:60px 20px;">
      <div style="font-size:40px;margin-bottom:12px;">📋</div>
      <div style="font-size:16px;font-weight:600;margin-bottom:6px;">No quotes found</div>
      <div style="font-size:13px;color:#6b7280;margin-bottom:16px;">Submit a quote request for large or custom orders.</div>
      <a href="<?php echo esc_url( PCS_B2B_Portal::url('quote-request') ); ?>" class="pcs-pbtn pcs-pbtn-primary">+ Request a quote</a>
    </div>
  </div>
  <?php else : ?>
  <div class="pcs-pcard" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#f8fafc;border-bottom:1px solid #e5e7eb;">
          <th style="padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-align:left;text-transform:uppercase;letter-spacing:.05em;width:14%;">Quote ID</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-align:left;text-transform:uppercase;letter-spacing:.05em;">Project</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-align:center;text-transform:uppercase;letter-spacing:.05em;width:8%;">Items</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-align:right;text-transform:uppercase;letter-spacing:.05em;width:12%;">Total</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-align:left;text-transform:uppercase;letter-spacing:.05em;width:22%;">Status</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;color:#9ca3af;text-align:right;text-transform:uppercase;letter-spacing:.05em;width:12%;">Last update</th>
          <th style="width:100px;"></th>
        </tr>
      </thead>
      <tbody>
      <style>
        .pcs-q-row { transition:background .12s; cursor:pointer; }
        .pcs-q-row:hover { background:#f8faff !important; }
      </style>
      <?php foreach ( $my_quotes as $q ) :
        $sl           = $status_map[ $q->status ] ?? array( ucfirst( $q->status ), '#f3f4f6', '#6b7280', '#6b7280' );
        $updated      = human_time_diff( strtotime( $q->updated_at ), time() ) . ' ago';
        $is_sent      = 'sent' === $q->status;
        $price_visible = in_array( $q->status, array( 'sent', 'accepted', 'declined' ), true );
        $items        = PCS_Quotes::get_items( $q->id );
        $item_count   = count( $items );
        $expires_days = $q->valid_until ? ceil( ( strtotime( $q->valid_until ) - time() ) / DAY_IN_SECONDS ) : null;
        $row_bg       = $is_sent ? 'background:#fafbff;' : '';
        $detail_url   = add_query_arg( array('section' => 'quote-view', 'quote_id' => $q->id), PCS_B2B_Portal::url('') );
      ?>
      <tr class="pcs-q-row" style="border-bottom:1px solid #f3f4f6;<?php echo $row_bg; ?>"
          onclick="location.href='<?php echo esc_url($detail_url); ?>'">
        <td style="padding:14px 16px;" onclick="event.stopPropagation()">
          <a href="<?php echo esc_url($detail_url); ?>" style="font-weight:700;color:#2563eb;font-size:13px;text-decoration:none;"><?php echo esc_html( $q->quote_number ); ?></a>
        </td>
        <td style="padding:14px 16px;">
          <div style="font-weight:600;color:#111827;font-size:14px;margin-bottom:2px;"><?php echo esc_html( $q->title ?: '—' ); ?></div>
          <?php if ( $item_count > 0 && $price_visible ) :
            $first = reset( $items );
            $extra = $item_count > 1 ? ' + ' . ($item_count - 1) . ' more' : '';
          ?>
          <div style="font-size:12px;color:#9ca3af;"><?php echo esc_html( $first->description . $extra ); ?></div>
          <?php endif; ?>
        </td>
        <td style="padding:14px 16px;text-align:center;">
          <?php if ( $price_visible && $item_count > 0 ) : ?>
            <span style="font-size:13px;font-weight:600;color:#374151;"><?php echo $item_count; ?> unit<?php echo $item_count !== 1 ? 's' : ''; ?></span>
          <?php else : ?>
            <span style="color:#d1d5db;">—</span>
          <?php endif; ?>
        </td>
        <td style="padding:14px 16px;text-align:right;">
          <?php if ( $price_visible && $q->total > 0 ) : ?>
            <span style="font-weight:700;font-size:14px;color:<?php echo 'accepted' === $q->status ? '#059669' : '#111827'; ?>;"><?php echo wp_kses_post( wc_price( $q->total ) ); ?></span>
          <?php else : ?>
            <span style="font-size:12px;color:#9ca3af;">TBD</span>
          <?php endif; ?>
        </td>
        <td style="padding:14px 16px;">
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:9999px;background:<?php echo esc_attr($sl[1]); ?>;color:<?php echo esc_attr($sl[2]); ?>;">
            <span style="width:6px;height:6px;border-radius:50%;background:<?php echo esc_attr($sl[3]); ?>;flex-shrink:0;"></span>
            <?php echo esc_html( $sl[0] ); ?>
          </span>
          <?php if ( $is_sent && $expires_days !== null && $expires_days > 0 ) : ?>
            <div style="font-size:11px;color:#6b7280;margin-top:3px;">Valid <?php echo (int) $expires_days; ?> more day<?php echo $expires_days !== 1 ? 's' : ''; ?></div>
          <?php endif; ?>
        </td>
        <td style="padding:14px 16px;text-align:right;font-size:12px;color:#9ca3af;">
          <?php echo esc_html( $updated ); ?>
        </td>
        <td style="padding:14px 16px;text-align:right;" id="pcs-q-actions-<?php echo (int) $q->id; ?>" onclick="event.stopPropagation()">
          <?php if ( $is_sent ) : ?>
            <a href="<?php echo esc_url($detail_url); ?>"
               style="padding:7px 16px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;text-decoration:none;display:inline-block;">
              Review →
            </a>
          <?php elseif ( 'accepted' === $q->status ) : ?>
            <span style="font-size:12px;color:#059669;font-weight:600;">✅ Accepted</span>
          <?php elseif ( 'declined' === $q->status ) : ?>
            <span style="font-size:12px;color:#9ca3af;">Declined</span>
          <?php else : ?>
            <span style="font-size:12px;color:#d1d5db;">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Bottom upsell banner -->
  <div style="margin-top:20px;background:#fafbff;border:1px solid #e0e7ff;border-radius:10px;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div style="font-size:13px;color:#374151;">
      💡 <strong>Need pricing for a non-standard order?</strong>
      <span style="color:#6b7280;"> Submit a quote request and our team responds within 24 business hours.</span>
    </div>
    <a href="<?php echo esc_url( PCS_B2B_Portal::url('quote-request') ); ?>" class="pcs-pbtn pcs-pbtn-primary" style="white-space:nowrap;">
      + New request
    </a>
  </div>

</div>

<!-- Accept / Decline modal (keep same) -->
<div id="pcs-respond-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:420px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.2);text-align:center;">
    <div id="pcs-rm-icon" style="font-size:40px;margin-bottom:12px;"></div>
    <h3 id="pcs-rm-title" style="margin:0 0 8px;font-size:18px;font-weight:700;color:#111827;"></h3>
    <p id="pcs-rm-desc" style="margin:0 0 22px;font-size:13px;color:#6b7280;"></p>
    <?php
    /* Show line items for the "sent" quote in modal */
    $sent_quotes = array_filter( $my_quotes, function($q) { return 'sent' === $q->status; } );
    foreach ( $sent_quotes as $sq ) :
        $sq_items = PCS_Quotes::get_items( $sq->id );
    ?>
    <div id="pcs-rm-items-<?php echo (int) $sq->id; ?>" style="display:none;text-align:left;background:#f9fafb;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;">
      <?php foreach ( $sq_items as $si ) : ?>
      <div style="display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid #f3f4f6;">
        <span><?php echo esc_html( $si->description ); ?> ×<?php echo (int) $si->quantity; ?></span>
        <span style="font-weight:600;"><?php echo wp_kses_post( wc_price( $si->total ) ); ?></span>
      </div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;padding-top:6px;font-weight:700;">
        <span>Total</span>
        <span style="color:#2563eb;"><?php echo wp_kses_post( wc_price( $sq->total ) ); ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="display:flex;gap:10px;">
      <button id="pcs-rm-cancel" style="flex:1;padding:11px;background:#f3f4f6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button id="pcs-rm-confirm" style="flex:2;padding:11px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;"></button>
    </div>
  </div>
</div>

<script>
(function($) {
    var _pendingQuoteId  = null;
    var _pendingResponse = null;

    window.pcsRespondQuote = function(quoteId, response) {
        _pendingQuoteId  = quoteId;
        _pendingResponse = response;
        var isAccept = response === 'accept';

        /* Show line items if available */
        $('.pcs-rm-items-inner').hide();
        $('#pcs-rm-items-' + quoteId).show();

        $('#pcs-rm-icon').text(isAccept ? '✅' : '✕');
        $('#pcs-rm-title').text(isAccept ? 'Accept this quote?' : 'Decline this quote?');
        $('#pcs-rm-desc').text(isAccept
            ? 'Our team will be notified and will begin processing your order.'
            : 'This quote will be marked as declined. You can submit a new request anytime.');
        $('#pcs-rm-confirm').text(isAccept ? '✓ Accept quote' : '✕ Decline quote')
            .css({ background: isAccept ? '#059669' : '#374151', color: '#fff' });
        document.getElementById('pcs-respond-modal').style.display = 'flex';
    };

    function pcsDoRespond() {
        var quoteId  = _pendingQuoteId;
        var response = _pendingResponse;
        document.getElementById('pcs-respond-modal').style.display = 'none';
        if (!quoteId) return;

        var $actions = $('#pcs-q-actions-' + quoteId);
        $actions.find('button').prop('disabled', true);

        $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
            action:   'pcs_quote_respond',
            _nonce:   (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
            quote_id: quoteId,
            response: response,
        }, function(res) {
            if (res.success) {
                var color = response === 'accept' ? '#059669' : '#9ca3af';
                var msg   = response === 'accept' ? '✅ Accepted' : 'Declined';
                $actions.html('<span style="font-size:12px;color:' + color + ';font-weight:600;">' + msg + '</span>');
                /* Refresh page after short delay */
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                $actions.find('button').prop('disabled', false);
            }
        });
    }

    $(document).on('click', '#pcs-rm-confirm', pcsDoRespond);
    $(document).on('click', '#pcs-rm-cancel', function() {
        document.getElementById('pcs-respond-modal').style.display = 'none';
    });
    $(document).on('click', '#pcs-respond-modal', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
})(jQuery);
</script>
