<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var int $user_id @var WP_User $user */

$quote_id = isset( $_GET['quote_id'] ) ? absint( $_GET['quote_id'] ) : 0;
$quote    = $quote_id ? PCS_Quotes::get( $quote_id ) : null;

/* Security: must own this quote */
if ( ! $quote || ! PCS_Quotes::user_can_access( $quote, $user_id ) ) { ?>
  <div style="padding:60px 24px;text-align:center;">
    <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
    <p style="font-size:15px;color:#374151;">Quote not found.</p>
    <a href="<?php echo esc_url( PCS_B2B_Portal::url('quotes') ); ?>" class="pcs-pbtn pcs-pbtn-primary" style="display:inline-flex;margin-top:12px;">← Back to my quotes</a>
  </div>
<?php return; }

$items    = PCS_Quotes::get_items( $quote_id );
$is_sent  = 'sent' === $quote->status;
$is_responded = in_array( $quote->status, array('accepted','declined'), true );

/* Expiry info */
$expires_days = null;
if ( $quote->valid_until ) {
    $expires_days = ceil( ( strtotime( $quote->valid_until ) - time() ) / DAY_IN_SECONDS );
}

/* Status timeline */
$timeline = array(
    array( 'done' => true,  'label' => 'Request received',     'sub' => $quote->created_at  ? date_i18n( 'M j, Y · g:i A', strtotime( $quote->created_at ) )   : '' ),
    array( 'done' => ! in_array( $quote->status, array('new_request'), true ),
                            'label' => 'Quote prepared',        'sub' => $quote->sent_at     ? date_i18n( 'M j, Y', strtotime( $quote->sent_at ) )                : ( 'in_review' === $quote->status ? 'In progress…' : '' ) ),
    array( 'done' => $is_sent || $is_responded,
                            'label' => 'Awaiting your response','sub' => $is_sent ? 'Accept, decline, or ask questions' : ( $is_responded ? ucfirst( $quote->status ) : '' ),
           'current' => $is_sent ),
    array( 'done' => 'accepted' === $quote->status,
                            'label' => 'Production starts',     'sub' => 'After you accept' ),
);

$status_colors = array(
    'new_request' => array('#fef3c7','#92400e'),
    'in_review'   => array('#dbeafe','#1e40af'),
    'sent'        => array('#e0e7ff','#3730a3'),
    'accepted'    => array('#dcfce7','#065f46'),
    'declined'    => array('#fee2e2','#991b1b'),
);
$sc = $status_colors[$quote->status] ?? array('#f3f4f6','#374151');
?>

<!-- Back link -->
<div style="padding:14px 24px 0;"><a href="<?php echo esc_url( PCS_B2B_Portal::url('quotes') ); ?>" style="font-size:13px;color:#6b7280;text-decoration:none;">← Back to my quotes</a></div>

<!-- Page header -->
<div style="padding:16px 24px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
  <div>
    <div style="font-size:12px;color:#9ca3af;margin-bottom:4px;"><?php echo esc_html( $quote->quote_number ); ?> · Submitted <?php echo esc_html( human_time_diff( strtotime( $quote->created_at ), time() ) ); ?> ago</div>
    <h2 style="margin:0;font-size:22px;font-weight:800;color:#111827;"><?php echo esc_html( $quote->title ?: 'Custom Quote' ); ?></h2>
  </div>
  <span style="font-size:12px;font-weight:600;padding:5px 14px;border-radius:9999px;background:<?php echo esc_attr($sc[0]); ?>;color:<?php echo esc_attr($sc[1]); ?>;">
    <?php echo esc_html( ucfirst( str_replace('_',' ', $quote->status) ) ); ?>
  </span>
</div>

<div style="padding:16px 24px;display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

  <!-- LEFT -->
  <div>

    <!-- Original request -->
    <?php if ( $quote->message ) : ?>
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head">
        <h3 class="pcs-pcard-title">📄 Your original request</h3>
      </div>
      <div style="padding:16px 18px;font-size:13px;color:#374151;line-height:1.7;">
        <?php echo nl2br( esc_html( $quote->message ) ); ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- What's included -->
    <?php if ( ! empty( $items ) ) : ?>
    <div class="pcs-pcard" style="margin-bottom:16px;overflow:hidden;">
      <div class="pcs-pcard-head" style="background:#f0fdf4;">
        <h3 class="pcs-pcard-title" style="color:#065f46;">☑️ What's included</h3>
      </div>
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid #f3f4f6;">
            <th style="padding:10px 16px;font-size:11px;color:#9ca3af;text-align:left;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">Item</th>
            <th style="padding:10px 16px;font-size:11px;color:#9ca3af;text-align:center;font-weight:700;text-transform:uppercase;width:60px;">Qty</th>
            <th style="padding:10px 16px;font-size:11px;color:#9ca3af;text-align:right;font-weight:700;text-transform:uppercase;width:100px;">Unit</th>
            <th style="padding:10px 16px;font-size:11px;color:#9ca3af;text-align:right;font-weight:700;text-transform:uppercase;width:110px;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $items as $item ) : ?>
          <tr style="border-bottom:1px solid #f9fafb;">
            <td style="padding:12px 16px;">
              <div style="font-weight:700;font-size:14px;color:#111827;"><?php echo esc_html( $item->description ); ?></div>
              <?php if ( ! empty( $item->notes ) ) : ?>
                <div style="font-size:12px;color:#6b7280;margin-top:2px;"><?php echo esc_html( $item->notes ); ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;text-align:center;font-size:13px;color:#374151;"><?php echo (int) $item->quantity; ?></td>
            <td style="padding:12px 16px;text-align:right;font-size:13px;color:#374151;"><?php echo wp_kses_post( wc_price( $item->unit_price ) ); ?></td>
            <td style="padding:12px 16px;text-align:right;font-size:14px;font-weight:700;color:#111827;"><?php echo wp_kses_post( wc_price( $item->total ) ); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Summary -->
      <div style="padding:14px 16px;border-top:1px solid #f3f4f6;">
        <div style="max-width:300px;margin-left:auto;">
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:6px;">
            <span>Subtotal</span><span><?php echo wp_kses_post( wc_price( $quote->subtotal ) ); ?></span>
          </div>
          <?php if ( $quote->discount > 0 ) : ?>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#059669;margin-bottom:6px;">
            <span>Volume discount</span><span>-<?php echo wp_kses_post( wc_price( $quote->discount ) ); ?></span>
          </div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:800;color:#2563eb;padding-top:10px;border-top:2px solid #e5e7eb;margin-top:6px;">
            <span>Total</span><span><?php echo wp_kses_post( wc_price( $quote->total ) ); ?></span>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quote terms -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head">
        <h3 class="pcs-pcard-title">🕐 Quote terms</h3>
      </div>
      <div style="padding:16px 18px;">
        <?php
        $terms_rows = array();
        if ( $quote->valid_until ) {
            $terms_rows[] = array( 'Validity', date_i18n( 'M j, Y', strtotime( $quote->valid_until ) ) . ( $expires_days > 0 ? ' · <span style="color:#d97706;">' . (int)$expires_days . ' days remaining</span>' : '' ) );
        }
        if ( $quote->admin_notes ) {
            $terms_rows[] = array( 'Notes', esc_html( $quote->admin_notes ) );
        }
        foreach ( $terms_rows as $row ) : ?>
        <div style="display:grid;grid-template-columns:130px 1fr;gap:12px;padding:8px 0;border-bottom:1px solid #f9fafb;font-size:13px;">
          <span style="color:#9ca3af;font-weight:500;"><?php echo esc_html( $row[0] ); ?></span>
          <span style="color:#111827;"><?php echo $row[1]; ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ( empty( $terms_rows ) ) : ?>
          <p style="font-size:13px;color:#9ca3af;margin:0;">No additional terms specified.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Accept / Decline -->
    <?php if ( $is_sent ) : ?>
    <div style="display:flex;gap:12px;" id="pcs-qv-actions">
      <button onclick="pcsQvRespond('accept')"
              style="flex:2;padding:14px;background:#059669;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;">
        ✓ Accept quote
      </button>
      <button onclick="pcsQvRespond('decline')"
              style="flex:1;padding:14px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">
        ✕ Decline
      </button>
    </div>
    <?php elseif ( 'accepted' === $quote->status ) : ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;font-size:14px;font-weight:600;color:#065f46;">
      ✅ You accepted this quote on <?php echo esc_html( $quote->responded_at ? date_i18n( 'M j, Y', strtotime( $quote->responded_at ) ) : '—' ); ?>. Our team will be in touch.
    </div>
    <?php elseif ( 'declined' === $quote->status ) : ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;font-size:14px;color:#991b1b;">
      ✕ You declined this quote. <a href="<?php echo esc_url( PCS_B2B_Portal::url('quote-request') ); ?>" style="color:#2563eb;">Submit a new request →</a>
    </div>
    <?php endif; ?>

  </div><!-- /left -->

  <!-- RIGHT sidebar -->
  <div>

    <!-- Quote status timeline -->
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">🕐 Quote status</h3></div>
      <div style="padding:16px 18px;">
        <?php foreach ( $timeline as $i => $step ) : ?>
        <div style="display:flex;gap:12px;align-items:flex-start;<?php echo $i < count($timeline)-1 ? 'margin-bottom:16px;' : ''; ?>">
          <div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
            <?php if ( ! empty($step['current']) ) echo 'background:#2563eb;color:#fff;box-shadow:0 0 0 3px rgba(37,99,235,.2);';
            elseif ( $step['done'] )                 echo 'background:#059669;color:#fff;';
            else                                      echo 'background:#f3f4f6;color:#9ca3af;border:2px solid #e5e7eb;'; ?>">
            <?php echo $step['done'] && empty($step['current']) ? '✓' : ($i+1); ?>
          </div>
          <div>
            <div style="font-size:13px;font-weight:<?php echo ! empty($step['current']) ? '700' : '600'; ?>;color:<?php echo ! empty($step['current']) ? '#2563eb' : ( $step['done'] ? '#111827' : '#9ca3af' ); ?>;">
              <?php echo esc_html( $step['label'] ); ?>
            </div>
            <?php if ( $step['sub'] ) : ?>
            <div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?php echo esc_html( $step['sub'] ); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Expires soon warning -->
    <?php if ( $is_sent && $expires_days !== null && $expires_days <= 7 && $expires_days > 0 ) : ?>
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;margin-bottom:16px;">
      <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px;">⏰ Expires soon</div>
      <p style="margin:0;font-size:12px;color:#92400e;line-height:1.5;">
        This quote is valid until <strong><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $quote->valid_until ) ) ); ?></strong>.<br>
        <span style="color:#d97706;"><?php echo (int) $expires_days; ?> day<?php echo $expires_days !== 1 ? 's' : ''; ?> remaining</span> · accept to lock in pricing.
      </p>
    </div>
    <?php elseif ( $is_sent && $quote->valid_until ) : ?>
    <div class="pcs-pcard" style="margin-bottom:16px;">
      <div class="pcs-pcard-body" style="font-size:12px;color:#6b7280;">
        Valid until <strong><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $quote->valid_until ) ) ); ?></strong>
        <?php if ( $expires_days > 0 ) echo ' · ' . (int)$expires_days . ' days left'; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /right -->

</div><!-- /grid -->

<!-- Respond modal -->
<div id="pcs-qv-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:420px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.2);text-align:center;">
    <div id="pcs-qvm-icon" style="font-size:40px;margin-bottom:12px;"></div>
    <h3 id="pcs-qvm-title" style="margin:0 0 8px;font-size:18px;font-weight:700;color:#111827;"></h3>
    <p id="pcs-qvm-desc" style="margin:0 0 22px;font-size:13px;color:#6b7280;"></p>
    <div style="display:flex;gap:10px;">
      <button onclick="document.getElementById('pcs-qv-modal').style.display='none'"
              style="flex:1;padding:11px;background:#f3f4f6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button id="pcs-qvm-confirm"
              style="flex:2;padding:11px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;"></button>
    </div>
  </div>
</div>

<script>
(function($) {
    var _response = null;

    window.pcsQvRespond = function(r) {
        _response = r;
        var isAccept = r === 'accept';
        $('#pcs-qvm-icon').text(isAccept ? '✅' : '✕');
        $('#pcs-qvm-title').text(isAccept ? 'Accept this quote?' : 'Decline this quote?');
        $('#pcs-qvm-desc').text(isAccept
            ? 'Our team will be notified and will begin processing your order.'
            : 'This quote will be marked as declined. You can submit a new request anytime.');
        $('#pcs-qvm-confirm').text(isAccept ? '✓ Accept quote' : '✕ Decline quote')
            .css({ background: isAccept ? '#059669' : '#374151', color: '#fff' });
        document.getElementById('pcs-qv-modal').style.display = 'flex';
    };

    $('#pcs-qvm-confirm').on('click', function() {
        var $btn = $(this);
        document.getElementById('pcs-qv-modal').style.display = 'none';
        var $actions = $('#pcs-qv-actions');
        $actions.find('button').prop('disabled', true);
        $btn.text('Processing…');

        $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
            action:   'pcs_quote_respond',
            _nonce:   (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
            quote_id: <?php echo (int) $quote_id; ?>,
            response: _response,
        }, function(res) {
            if (res.success) {
                if (_response === 'accept' && res.data && res.data.order_url) {
                    /* Show success banner then redirect to the new order */
                    $actions.replaceWith(
                        '<div id="pcs-accept-banner" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:18px 22px;text-align:center;">' +
                        '<div style="font-size:26px;margin-bottom:8px;">🎉</div>' +
                        '<div style="font-size:15px;font-weight:700;color:#065f46;margin-bottom:4px;">' + res.data.message + '</div>' +
                        '<div style="font-size:13px;color:#6b7280;">Redirecting to your order…</div>' +
                        '</div>'
                    );
                    setTimeout(function() { window.location.href = res.data.order_url; }, 1800);
                } else {
                    setTimeout(function() { location.reload(); }, 800);
                }
            } else {
                $actions.find('button').prop('disabled', false);
                alert(res.data && res.data.message ? res.data.message : 'Error.');
            }
        });
    });

    $('#pcs-qv-modal').on('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
})(jQuery);
</script>
