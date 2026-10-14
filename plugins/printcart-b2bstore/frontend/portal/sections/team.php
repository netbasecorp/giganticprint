<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var object|null $store @var int $user_id @var string $tier @var array $tiers @var bool $is_b2b */
if ( ! $store ) { echo '<p>No B2B store found.</p>'; return; }

$all_members  = PCS_DB::get_members( $store->id );
$owner_id     = (int) $store->user_id;
/* Exclude store owner from member list — owner shown separately as "YOU" */
$members      = array_values( array_filter( $all_members, function( $m ) use ( $owner_id ) {
    return (int) $m->user_id !== $owner_id;
} ) );
$member_count = count( $members ) + 1; // +1 for owner
$max_seats    = (int) $store->team_size ?: 5;
$seats_left   = max( 0, $max_seats - $member_count );
$filter_role  = isset( $_GET['team_role'] ) ? sanitize_text_field( wp_unslash( $_GET['team_role'] ) ) : '';
$portal_team  = PCS_B2B_Portal::url( 'team' );

$roles = array(
    'admin'    => array( 'label' => 'Admin',    'bg' => '#dbeafe', 'color' => '#1e40af' ),
    'approver' => array( 'label' => 'Approver', 'bg' => '#fef3c7', 'color' => '#92400e' ),
    'member'   => array( 'label' => 'Member',   'bg' => '#f3f4f6', 'color' => '#374151' ),
);
?>

<!-- ===== PAGE HEADER ===== -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">👥</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( $store->company_name ); ?> · Teams</div>
        <h1 class="pcs-ph-title">Teams</h1>
        <p class="pcs-ph-sub">Manage your team members, roles, and access permissions for your B2B workspace.</p>
      </div>
      <div class="pcs-ph-actions">
        <?php if ( $seats_left > 0 ) : ?>
          <button class="pcs-pbtn pcs-pbtn-primary" onclick="pcsOpenInviteModal()">
            👤+ Invite member
          </button>
        <?php else : ?>
          <span class="pcs-pbtn pcs-pbtn-ghost" style="cursor:default;opacity:.6;" title="No seats available">Team full</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="pcs-ph-stats">
      <div>
        <div class="pcs-ph-stat-val" id="pcs-team-count-display"><?php echo $member_count . ' / ' . $max_seats; ?></div>
        <div class="pcs-ph-stat-lbl">Members</div>
      </div>
      <div>
        <div class="pcs-ph-stat-val" id="pcs-team-slots-display"><?php echo $seats_left; ?></div>
        <div class="pcs-ph-stat-lbl">Slots available</div>
      </div>
    </div>
  </div>
</div>

<div style="padding:20px 24px;">

  <!-- Stats cards -->
  <div class="pcs-pgrid pcs-pgrid-3" style="margin-bottom:20px;">
    <div class="pcs-pcard pcs-pcard-stat">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
          <div class="pcs-pstat-label">MEMBERS</div>
          <div class="pcs-pstat-value" style="font-size:28px;" id="pcs-team-card-count"><?php echo $member_count . ' / ' . $max_seats; ?></div>
          <div class="pcs-pstat-delta" id="pcs-team-card-slots"><?php echo $seats_left; ?> slot<?php echo $seats_left !== 1 ? 's' : ''; ?> available</div>
        </div>
        <span style="font-size:22px;opacity:.5;">👤</span>
      </div>
    </div>
    <div class="pcs-pcard pcs-pcard-stat">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
          <div class="pcs-pstat-label">ROLES</div>
          <div class="pcs-pstat-value" style="font-size:28px;">
            <?php
            $role_counts = array_count_values( array_column( $members, 'role' ) );
            echo implode(' · ', array_map( function($k, $v) { return $v . ' ' . ucfirst($k); }, array_keys($role_counts), $role_counts ) ) ?: '—';
            ?>
          </div>
          <div class="pcs-pstat-delta">Across your team</div>
        </div>
        <span style="font-size:22px;opacity:.5;">🏷️</span>
      </div>
    </div>
  </div>

  <!-- Filter + member count -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
    <div style="display:flex;gap:8px;align-items:center;">
      <span style="font-size:12px;color:#6b7280;font-weight:500;">FILTER BY:</span>
      <?php foreach ( array( '' => 'All roles', 'admin' => 'Admin', 'approver' => 'Approver', 'member' => 'Member' ) as $k => $lbl ) :
        $active = $filter_role === $k;
        $href   = $k ? add_query_arg( 'team_role', $k, $portal_team ) : $portal_team;
      ?>
      <a href="<?php echo esc_url( $href ); ?>"
         style="font-size:12px;padding:5px 12px;border-radius:20px;text-decoration:none;font-weight:<?php echo $active ? '700' : '400'; ?>;
                background:<?php echo $active ? '#2563eb' : '#f3f4f6'; ?>;
                color:<?php echo $active ? '#fff' : '#374151'; ?>;">
        <?php echo esc_html( $lbl ); ?>
      </a>
      <?php endforeach; ?>
    </div>
    <span style="font-size:12px;color:#6b7280;" id="pcs-team-filter-count"><?php echo $member_count; ?> member<?php echo $member_count !== 1 ? 's' : ''; ?></span>
  </div>

  <!-- Team members list -->
  <div class="pcs-pcard" style="overflow:hidden;">
    <div class="pcs-pcard-head">
      <h3 class="pcs-pcard-title">Team members</h3>
    </div>

    <!-- Owner row -->
    <?php
    $cu = wp_get_current_user();
    if ( ! $filter_role || $filter_role === 'admin' ) :
    ?>
    <div class="pcs-pmember-row" style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #f3f4f6;">
      <div class="pcs-pmember-avatar" style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">
        <?php echo esc_html( PCS_Customers::get_initials( $cu->display_name ) ); ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:14px;font-weight:600;color:#111827;">
          <?php echo esc_html( $cu->display_name ); ?>
          <span style="font-size:11px;background:#e0e7ff;color:#3730a3;padding:1px 7px;border-radius:9999px;margin-left:6px;font-weight:600;">YOU</span>
        </div>
        <div style="font-size:12px;color:#6b7280;"><?php echo esc_html( $cu->user_email ); ?></div>
      </div>
      <span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:9999px;background:#dbeafe;color:#1e40af;white-space:nowrap;">Admin</span>
      <span style="font-size:12px;color:#9ca3af;min-width:90px;text-align:right;">Account owner</span>
      <div style="width:32px;"></div>
    </div>
    <?php endif; ?>

    <!-- Member rows -->
    <?php
    $shown = 0;
    foreach ( $members as $m ) :
      if ( $filter_role && $m->role !== $filter_role ) continue;
      $mu = get_userdata( $m->user_id );
      if ( ! $mu ) continue;
      $r = $roles[ $m->role ] ?? array( 'label' => ucfirst( $m->role ), 'bg' => '#f3f4f6', 'color' => '#374151' );
      $shown++;
    ?>
    <div class="pcs-pmember-row pcs-pmember-row-<?php echo (int) $m->id; ?>"
         style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #f3f4f6;">
      <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#374151,#6b7280);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">
        <?php echo esc_html( PCS_Customers::get_initials( $mu->display_name ) ); ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:14px;font-weight:600;color:#111827;"><?php echo esc_html( $mu->display_name ); ?></div>
        <div style="font-size:12px;color:#6b7280;"><?php echo esc_html( $mu->user_email ); ?></div>
      </div>
      <span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:9999px;background:<?php echo esc_attr($r['bg']); ?>;color:<?php echo esc_attr($r['color']); ?>;white-space:nowrap;">
        <?php echo esc_html( $r['label'] ); ?>
      </span>
      <span style="font-size:12px;color:#9ca3af;min-width:90px;text-align:right;">
        <?php echo $m->spending_limit > 0 ? 'Limit: ' . strip_tags( wc_price( $m->spending_limit ) ) : 'No limit'; ?>
      </span>
      <button class="pcs-remove-member-btn"
              data-member-id="<?php echo (int) $m->id; ?>"
              data-name="<?php echo esc_attr( $mu->display_name ); ?>"
              style="width:32px;height:32px;border:1px solid #fecaca;background:#fff5f5;color:#ef4444;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
              title="Remove member">
        ✕
      </button>
    </div>
    <?php endforeach; ?>

    <?php if ( $shown === 0 && ( $filter_role || empty( $members ) ) ) : ?>
    <div style="padding:40px;text-align:center;color:#6b7280;">
      <?php echo $filter_role ? 'No members with role "' . esc_html( $filter_role ) . '".' : 'No additional team members yet.'; ?>
      <?php if ( $seats_left > 0 ) : ?>
        <br><br><button class="pcs-pbtn pcs-pbtn-primary" onclick="pcsOpenInviteModal()" style="margin-top:8px;">👤+ Invite first member</button>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Pending Invitations -->
  <?php $pending_invites = PCS_DB::get_team_invites_by_store( $store->id, 'pending' ); ?>
  <?php if ( ! empty( $pending_invites ) ) : ?>
  <div class="pcs-pcard" style="overflow:hidden;margin-top:20px;">
    <div class="pcs-pcard-head" style="background:#fefce8;border-bottom:1px solid #fde68a;">
      <h3 class="pcs-pcard-title" style="color:#92400e;">📬 Pending invitations (<?php echo count( $pending_invites ); ?>)</h3>
    </div>
    <?php foreach ( $pending_invites as $inv ) :
      $r      = $roles[ $inv->role ] ?? array( 'label' => ucfirst( $inv->role ), 'bg' => '#f3f4f6', 'color' => '#374151' );
      $sent   = human_time_diff( strtotime( $inv->created_at ), current_time( 'timestamp' ) ) . ' ago';
      $expiry = strtotime( $inv->expires_at );
      $days_left = ceil( ( $expiry - time() ) / DAY_IN_SECONDS );
    ?>
    <div class="pcs-pinvite-row-<?php echo (int) $inv->id; ?>"
         style="display:flex;align-items:center;gap:14px;padding:13px 18px;border-bottom:1px solid #fef9c3;">
      <div style="width:40px;height:40px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📧</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:14px;font-weight:600;color:#111827;"><?php echo esc_html( $inv->invited_email ); ?></div>
        <div style="font-size:12px;color:#92400e;">Sent <?php echo esc_html( $sent ); ?> · expires in <?php echo (int) $days_left; ?> day<?php echo $days_left !== 1 ? 's' : ''; ?></div>
      </div>
      <span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:9999px;background:<?php echo esc_attr($r['bg']); ?>;color:<?php echo esc_attr($r['color']); ?>;white-space:nowrap;"><?php echo esc_html( $r['label'] ); ?></span>
      <span style="font-size:11px;background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:9999px;font-weight:600;">Pending</span>
      <button class="pcs-cancel-invite-btn"
              data-invite-id="<?php echo (int) $inv->id; ?>"
              data-email="<?php echo esc_attr( $inv->invited_email ); ?>"
              style="padding:6px 12px;border:1px solid #fca5a5;background:#fff5f5;color:#dc2626;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;white-space:nowrap;flex-shrink:0;">
        Cancel
      </button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- ===== INVITE MEMBER MODAL ===== -->
<div id="pcs-invite-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:440px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.25);">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <div>
        <h3 style="margin:0 0 4px;font-size:18px;font-weight:700;">Invite team member</h3>
        <p style="margin:0;font-size:13px;color:#6b7280;"><?php echo $seats_left; ?> slot<?php echo $seats_left !== 1 ? 's' : ''; ?> remaining</p>
      </div>
      <button onclick="pcsCloseInviteModal()" style="width:32px;height:32px;border:none;background:#f3f4f6;border-radius:8px;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
    </div>

    <div id="pcs-invite-error" style="display:none;background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"></div>
    <div id="pcs-invite-success" style="display:none;background:#dcfce7;color:#065f46;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"></div>

    <div style="margin-bottom:14px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Email address *</label>
      <input type="email" id="pcs-invite-email" placeholder="colleague@company.com"
             style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;">
      <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">The person must already have an account on the site.</p>
    </div>

    <div style="margin-bottom:14px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Role</label>
      <select id="pcs-invite-role" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;box-sizing:border-box;">
        <option value="member">Member — can browse and order</option>
        <option value="approver">Approver — can approve orders</option>
        <option value="admin">Admin — full store access</option>
      </select>
    </div>

    <div style="margin-bottom:14px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Spending limit (optional)</label>
      <input type="number" id="pcs-invite-limit" placeholder="0 = no limit" min="0" step="0.01"
             style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
      <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">Max order value per transaction. Leave 0 for no limit.</p>
    </div>

    <div style="margin-bottom:20px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Personal message (optional)</label>
      <textarea id="pcs-invite-message" placeholder="Add a personal note to the invitation email…" rows="2"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
    </div>

    <div style="display:flex;gap:10px;">
      <button onclick="pcsCloseInviteModal()" style="flex:1;padding:11px;background:#f3f4f6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button id="pcs-invite-submit" onclick="pcsSubmitInvite()" style="flex:2;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Send invitation →</button>
    </div>
  </div>
</div>

<!-- ===== REMOVE CONFIRM MODAL ===== -->
<div id="pcs-remove-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.25);text-align:center;">
    <div style="font-size:36px;margin-bottom:12px;">👤</div>
    <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;">Remove team member?</h3>
    <p style="margin:0 0 20px;font-size:14px;color:#6b7280;" id="pcs-remove-name-msg">This member will lose access to your B2B store.</p>
    <div style="display:flex;gap:10px;">
      <button onclick="pcsCloseRemoveModal()" style="flex:1;padding:11px;background:#f3f4f6;color:#374151;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button id="pcs-remove-confirm-btn" style="flex:1;padding:11px;background:#ef4444;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Remove</button>
    </div>
  </div>
</div>

<script>
(function($) {
  /* ---- Invite modal ---- */
  window.pcsOpenInviteModal = function() {
    $('#pcs-invite-email').val('');
    $('#pcs-invite-role').val('member');
    $('#pcs-invite-limit').val('');
    $('#pcs-invite-message').val('');
    $('#pcs-invite-error, #pcs-invite-success').hide();
    document.getElementById('pcs-invite-modal').style.display = 'flex';
    setTimeout(function(){ $('#pcs-invite-email').focus(); }, 50);
  };
  window.pcsCloseInviteModal = function() {
    document.getElementById('pcs-invite-modal').style.display = 'none';
  };

  window.pcsSubmitInvite = function() {
    var email   = $('#pcs-invite-email').val().trim();
    var role    = $('#pcs-invite-role').val();
    var limit   = $('#pcs-invite-limit').val();
    var message = $('#pcs-invite-message').val().trim();
    var $btn    = $('#pcs-invite-submit');

    if ( ! email ) { $('#pcs-invite-error').text('Email is required.').show(); return; }
    $('#pcs-invite-error, #pcs-invite-success').hide();
    $btn.prop('disabled', true).text('Sending…');

    $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
      action:         'pcs_invite_member_fe',
      _nonce:         (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
      email:          email,
      role:           role,
      spending_limit: limit || 0,
      message:        message,
    }, function(res) {
      $btn.prop('disabled', false).text('Send invitation →');
      if ( res.success ) {
        $('#pcs-invite-success').text(res.data.message).show();
        /* Update slot counters */
        var maxSeats = <?php echo $max_seats; ?>;
        var curSlots = parseInt($('#pcs-team-slots-display').text()) || 0;
        var newSlots = Math.max(0, curSlots - 1);
        $('#pcs-team-slots-display').text(newSlots);
        $('#pcs-team-card-slots').text(newSlots + ' slot' + (newSlots !== 1 ? 's' : '') + ' available');
        if ( newSlots <= 0 ) { $('[onclick="pcsOpenInviteModal()"]').hide(); }
        /* Append pending invite row dynamically */
        var inv = res.data.invite;
        var roleBg    = { Admin:'#fef3c7', Approver:'#fef3c7', Member:'#f3f4f6' };
        var roleColor = { Admin:'#1e40af', Approver:'#92400e', Member:'#374151' };
        var bg  = roleBg[inv.role]  || '#f3f4f6';
        var cl  = roleColor[inv.role] || '#374151';
        var row = '<div class="pcs-pinvite-row-' + inv.id + '" '
                + 'style="display:flex;align-items:center;gap:14px;padding:13px 18px;border-bottom:1px solid #fef9c3;">'
                + '<div style="width:40px;height:40px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📧</div>'
                + '<div style="flex:1;min-width:0;">'
                +   '<div style="font-size:14px;font-weight:600;color:#111827;">' + $('<span>').text(inv.email).html() + '</div>'
                +   '<div style="font-size:12px;color:#92400e;">Just sent · expires in 7 days</div>'
                + '</div>'
                + '<span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:9999px;background:' + bg + ';color:' + cl + ';white-space:nowrap;">' + inv.role + '</span>'
                + '<span style="font-size:11px;background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:9999px;font-weight:600;">Pending</span>'
                + '<button class="pcs-cancel-invite-btn" data-invite-id="' + inv.id + '" data-email="' + $('<span>').text(inv.email).html() + '" '
                +   'style="padding:6px 12px;border:1px solid #fca5a5;background:#fff5f5;color:#dc2626;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;white-space:nowrap;flex-shrink:0;">Cancel</button>'
                + '</div>';
        /* Show/create pending section */
        if ( ! $('.pcs-pcard-pending-invites').length ) {
          var section = '<div class="pcs-pcard pcs-pcard-pending-invites" style="overflow:hidden;margin-top:20px;">'
                      + '<div class="pcs-pcard-head" style="background:#fefce8;border-bottom:1px solid #fde68a;">'
                      +   '<h3 class="pcs-pcard-title" style="color:#92400e;">📬 Pending invitations (<span class="pcs-pending-count">0</span>)</h3>'
                      + '</div>'
                      + '</div>';
          $('#pcs-portal-app .pcs-portal-main > div:last').append(section);
        }
        $('.pcs-pcard-pending-invites').append(row);
        var pc = parseInt($('.pcs-pending-count').first().text()) || 0;
        $('.pcs-pending-count').text(pc + 1);
        setTimeout(pcsCloseInviteModal, 1800);
      } else {
        $('#pcs-invite-error').text(res.data && res.data.message ? res.data.message : 'Error sending invitation.').show();
      }
    }).fail(function() {
      $btn.prop('disabled', false).text('Send invitation →');
      $('#pcs-invite-error').text('Network error. Please try again.').show();
    });
  };

  /* Enter key submits */
  $(document).on('keydown', '#pcs-invite-email, #pcs-invite-limit', function(e) {
    if (e.key === 'Enter') pcsSubmitInvite();
  });

  /* ---- Remove modal ---- */
  var _removingMemberId = null;
  window.pcsCloseRemoveModal = function() {
    document.getElementById('pcs-remove-modal').style.display = 'none';
    _removingMemberId = null;
  };

  $(document).on('click', '.pcs-remove-member-btn', function() {
    _removingMemberId = $(this).data('member-id');
    var name = $(this).data('name');
    $('#pcs-remove-name-msg').text(name + ' will lose access to your B2B store.');
    document.getElementById('pcs-remove-modal').style.display = 'flex';
  });

  $('#pcs-remove-confirm-btn').on('click', function() {
    if ( ! _removingMemberId ) return;
    var $btn = $(this).prop('disabled', true).text('Removing…');
    $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
      action:    'pcs_remove_member_fe',
      _nonce:    (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
      member_id: _removingMemberId,
    }, function(res) {
      $btn.prop('disabled', false).text('Remove');
      if ( res.success ) {
        $('.pcs-pmember-row-' + _removingMemberId).remove();
        pcsCloseRemoveModal();
      } else {
        alert(res.data && res.data.message ? res.data.message : 'Error removing member.');
        pcsCloseRemoveModal();
      }
    });
  });

  /* Cancel pending invitation */
  $(document).on('click', '.pcs-cancel-invite-btn', function() {
    var $btn      = $(this).prop('disabled', true).text('…');
    var inviteId  = $(this).data('invite-id');
    var email     = $(this).data('email');
    if ( ! confirm('Cancel invitation to ' + email + '?') ) {
      $btn.prop('disabled', false).text('Cancel');
      return;
    }
    $.post((typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
      action:    'pcs_cancel_team_invite',
      _nonce:    (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
      invite_id: inviteId,
    }, function(res) {
      if ( res.success ) {
        $('.pcs-pinvite-row-' + inviteId).remove();
        var pc = Math.max(0, parseInt($('.pcs-pending-count').first().text()) - 1);
        $('.pcs-pending-count').text(pc);
        /* Restore slot counter */
        var curSlots = parseInt($('#pcs-team-slots-display').text()) || 0;
        var maxSeats = <?php echo $max_seats; ?>;
        var newSlots = curSlots + 1;
        $('#pcs-team-slots-display').text(newSlots);
        $('#pcs-team-card-slots').text(newSlots + ' slot' + (newSlots !== 1 ? 's' : '') + ' available');
        if ( newSlots > 0 ) { $('[onclick="pcsOpenInviteModal()"]').show(); }
      } else {
        $btn.prop('disabled', false).text('Cancel');
        alert(res.data && res.data.message ? res.data.message : 'Error cancelling invitation.');
      }
    });
  });

  /* Close modals on backdrop click */
  $('#pcs-invite-modal, #pcs-remove-modal').on('click', function(e) {
    if (e.target === this) {
      this.style.display = 'none';
      _removingMemberId = null;
    }
  });

})(jQuery);
</script>
