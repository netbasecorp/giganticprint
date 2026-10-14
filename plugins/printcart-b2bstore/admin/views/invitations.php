<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$paged         = isset( $_GET['paged'] )  ? max( 1, absint( $_GET['paged'] ) )                   : 1;
$per_page      = 20;

$invitations = PCS_DB::get_invitations( array( 'status' => $filter_status, 'per_page' => $per_page, 'paged' => $paged ) );
$total       = PCS_DB::count_invitations( array( 'status' => $filter_status ) );
$pages       = ceil( $total / $per_page );
$stats       = PCS_DB::get_invitation_stats();
$tiers       = PCS_DB::get_tiers_from_rules();
$base_url    = admin_url( 'admin.php?page=printcart-stores-invitations' );

$acceptance_rate = $stats['total'] > 0 ? round( $stats['accepted'] / $stats['total'] * 100 ) : 0;

$inv_url = function( $extra = array() ) use ( $filter_status, $paged, $base_url ) {
    $params = array_filter( array_merge( array( 'status' => $filter_status, 'paged' => $paged > 1 ? $paged : '' ), $extra ) );
    return $base_url . ( $params ? '&' . http_build_query( $params ) : '' );
};
?>
<div class="pcs-wrap">

  <!-- Header -->
  <div class="pcs-page-head">
    <div class="pcs-page-title-block">
      <h1>Invitations</h1>
      <p class="pcs-page-sub">Send personalized invitations to qualified businesses. Track acceptance and manage pending invites.</p>
    </div>
    <div class="pcs-page-actions">
      <a href="#pcs-send-invite-form" class="pcs-btn pcs-btn-primary">+ Send new invitation</a>
    </div>
  </div>

  <div class="pcs-layout-2col">

    <!-- Left: Send form + sent table -->
    <div>

      <!-- Send invitation form -->
      <div class="pcs-card" id="pcs-send-invite-form">
        <div class="pcs-card-head is-brand">
          <span class="pcs-card-title">📨 Send new invitation</span>
        </div>
        <div class="pcs-card-body">
          <form id="pcs-form-invitation">
            <?php wp_nonce_field( 'pcs_ajax', '_nonce' ); ?>
            <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
              <label class="pcs-form-label">Business email *</label>
              <input type="email" id="pcs-inv-email" name="email" class="pcs-input" placeholder="contact@business.com" required style="max-width:100%;">
            </div>
            <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
              <label class="pcs-form-label">Company name</label>
              <input type="text" id="pcs-inv-company" name="company" class="pcs-input" placeholder="ACME Corp" style="max-width:100%;">
            </div>
            <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
              <label class="pcs-form-label">Contact name</label>
              <input type="text" id="pcs-inv-contact" name="contact" class="pcs-input" placeholder="Jane Smith" style="max-width:100%;">
            </div>
            <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
              <label class="pcs-form-label">Proposed tier</label>
              <select id="pcs-inv-tier" name="tier" class="pcs-select" style="max-width:100%;">
                <?php foreach ( $tiers as $tid => $tdef ) : ?>
                  <option value="<?php echo esc_attr( $tid ); ?>" <?php selected( $tid, 'b2b_b' ); ?>>
                    <?php echo esc_html( $tdef['label'] ); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;">
              <label class="pcs-form-label">Expires in</label>
              <select id="pcs-inv-expires" name="expires_days" class="pcs-select" style="max-width:200px;">
                <option value="7">7 days</option>
                <option value="14" selected>14 days</option>
                <option value="30">30 days</option>
              </select>
            </div>
            <div class="pcs-form-row" style="grid-template-columns:140px 1fr;gap:12px;padding:10px 0;border:none;">
              <label class="pcs-form-label">Personal message</label>
              <textarea id="pcs-inv-message" name="message" class="pcs-textarea" placeholder="Hi {name}, we'd love to offer your team B2B pricing…" style="max-width:100%;width:100%;"></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
              <button type="submit" class="pcs-btn pcs-btn-primary">📨 Send invitation</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Sent invitations table -->
      <div class="pcs-card">
        <div class="pcs-card-head">
          <span class="pcs-card-title">📋 Sent invitations</span>
          <div class="pcs-tabs" style="margin:0;border:none;">
            <?php
            $tab_statuses = array( '' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'expired' => 'Expired' );
            foreach ( $tab_statuses as $ts => $tl ) : ?>
              <a href="<?php echo esc_url( $inv_url( array( 'status' => $ts, 'paged' => '' ) ) ); ?>"
                 class="pcs-tab <?php echo $filter_status === $ts ? 'active' : ''; ?>" style="padding:4px 10px;font-size:12px;">
                <?php echo esc_html( $tl ); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="pcs-card-body p0">
          <table class="pcs-table">
            <thead>
              <tr>
                <th>Sent to</th>
                <th>Tier</th>
                <th>Sent</th>
                <th>Status</th>
                <th>Expires</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="pcs-invitations-tbody">
              <?php if ( empty( $invitations ) ) : ?>
              <tr id="pcs-inv-empty-row">
                <td colspan="6" style="text-align:center;padding:32px;color:var(--pcs-text-soft);">No invitations yet. Send your first one!</td>
              </tr>
              <?php else : ?>
              <?php foreach ( $invitations as $inv ) :
                $tier_def = isset( $tiers[ $inv->proposed_tier ] ) ? $tiers[ $inv->proposed_tier ] : array( 'label' => $inv->proposed_tier );
                $exp_str  = $inv->expires_at ? date_i18n( 'M j, Y', strtotime( $inv->expires_at ) ) : '—';
                $sent_str = human_time_diff( strtotime( $inv->created_at ), time() ) . ' ago';
                $status   = $inv->status;
                if ( 'pending' === $status && $inv->expires_at && strtotime( $inv->expires_at ) < time() ) {
                    $status = 'expired';
                }
              ?>
              <tr>
                <td>
                  <div style="font-weight:600;"><?php echo esc_html( $inv->company_name ?: $inv->email ); ?></div>
                  <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo esc_html( $inv->email ); ?></div>
                </td>
                <td><span class="pcs-tier-pill" data-tier="<?php echo esc_attr( $inv->proposed_tier ); ?>"><?php echo esc_html( $tier_def['label'] ); ?></span></td>
                <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( $sent_str ); ?></td>
                <td>
                  <span class="pcs-status-pill" data-status="<?php echo 'accepted' === $status ? 'active' : ( 'expired' === $status ? 'disabled' : 'pending' ); ?>">
                    <span class="dot"></span><?php echo esc_html( ucfirst( $status ) ); ?>
                  </span>
                </td>
                <td style="font-size:12px;color:var(--pcs-text-soft);"><?php echo esc_html( $exp_str ); ?></td>
                <td>
                  <?php if ( 'pending' === $inv->status ) : ?>
                    <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm pcs-resend-btn"
                            data-inv-id="<?php echo (int) $inv->id; ?>"
                            data-email="<?php echo esc_attr( $inv->email ); ?>">Resend</button>
                  <?php elseif ( 'accepted' === $inv->status ) : ?>
                    <span style="font-size:12px;color:var(--pcs-success);">✅ Accepted</span>
                  <?php else : ?>
                    <button class="pcs-btn pcs-btn-tertiary pcs-btn-sm pcs-resend-btn"
                            data-inv-id="<?php echo (int) $inv->id; ?>"
                            data-email="<?php echo esc_attr( $inv->email ); ?>">Re-invite</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ( $pages > 1 ) : ?>
        <div class="pcs-card-foot">
          <span style="font-size:12px;color:var(--pcs-text-soft);"><?php printf( '%d of %d', $total, $stats['total'] ); ?></span>
          <div class="pcs-pagination">
            <button class="pcs-page-btn" <?php echo $paged <= 1 ? 'disabled' : ''; ?> onclick="location.href='<?php echo esc_url( $inv_url( array( 'paged' => max( 1, $paged - 1 ) ) ) ); ?>'">‹</button>
            <span style="font-size:12px;padding:0 6px;">Page <?php echo (int) $paged; ?> of <?php echo (int) $pages; ?></span>
            <button class="pcs-page-btn" <?php echo $paged >= $pages ? 'disabled' : ''; ?> onclick="location.href='<?php echo esc_url( $inv_url( array( 'paged' => $paged + 1 ) ) ); ?>'">›</button>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /left -->

    <!-- Right: stats + tips -->
    <div>
      <div class="pcs-card">
        <div class="pcs-card-head is-success">
          <span class="pcs-card-title">📊 Invitation Stats</span>
        </div>
        <div class="pcs-card-body">
          <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
              <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:2px;">Sent (all time)</div>
              <div style="font-size:26px;font-weight:700;"><?php echo (int) $stats['total']; ?></div>
            </div>
            <div>
              <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:2px;">Acceptance rate</div>
              <div style="font-size:26px;font-weight:700;color:var(--pcs-success);"><?php echo (int) $acceptance_rate; ?>%</div>
              <div style="font-size:11px;color:var(--pcs-text-soft);"><?php echo (int) $stats['accepted']; ?> accepted of <?php echo (int) $stats['total']; ?></div>
            </div>
            <div>
              <div style="font-size:11px;color:var(--pcs-text-soft);text-transform:uppercase;font-weight:600;margin-bottom:2px;">Pending</div>
              <div style="font-size:26px;font-weight:700;"><?php echo (int) $stats['pending']; ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="pcs-card">
        <div class="pcs-card-head"><span class="pcs-card-title">💡 Tips</span></div>
        <div class="pcs-card-body" style="font-size:12px;line-height:1.6;">
          <p style="margin:0 0 8px;"><strong>Higher acceptance:</strong> Pre-assign a tier so the business sees concrete value upfront.</p>
          <p style="margin:0 0 8px;"><strong>Personal touch:</strong> Use the contact's name in the message.</p>
          <p style="margin:0;"><strong>Timing:</strong> Tuesday–Thursday 10–11am has higher open rates.</p>
        </div>
      </div>
    </div>

  </div><!-- /layout -->

</div>

<script>
(function($) {
    $(document).on('click', '.pcs-resend-btn', function() {
        var $btn  = $(this);
        var invId = $btn.data('inv-id');
        var email = $btn.data('email');

        $btn.prop('disabled', true).text('Sending…');

        $.post(ajaxurl, {
            action:  'pcs_resend_invitation',
            _nonce:  $('#pcs-form-invitation [name="_nonce"]').val(),
            inv_id:  invId,
        }, function(res) {
            if (res.success) {
                pcsToast('Invitation re-sent to ' + email, 'success');
                $btn.text('Sent ✓').css('color', 'var(--pcs-success)');
                setTimeout(function() { $btn.prop('disabled', false).text('Resend').css('color', ''); }, 4000);
            } else {
                pcsToast(res.data && res.data.message ? res.data.message : 'Failed to send.', 'error');
                $btn.prop('disabled', false).text('Resend');
            }
        }).fail(function() {
            pcsToast('Network error. Please try again.', 'error');
            $btn.prop('disabled', false).text('Resend');
        });
    });
})(jQuery);
</script>
