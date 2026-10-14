/* Printcart B2B Store — Admin JS */
(function($) {
  'use strict';

  /* ------------------------------------------------------------------ */
  /* Toast notifications                                                  */
  /* ------------------------------------------------------------------ */
  function pcsToast(msg, type) {
    type = type || 'success';
    var $container = $('#pcs-toast-container');
    if (!$container.length) {
      $container = $('<div id="pcs-toast-container"></div>').appendTo('body');
    }
    var $toast = $('<div class="pcs-toast ' + type + '">' + msg + '</div>');
    $container.append($toast);
    setTimeout(function() {
      $toast.addClass('fading');
      setTimeout(function() { $toast.remove(); }, 300);
    }, 2800);
  }
  window.pcsToast = pcsToast;

  /* ------------------------------------------------------------------ */
  /* Modal helpers                                                         */
  /* ------------------------------------------------------------------ */
  function pcsOpenModal(id) {
    $('#' + id).addClass('open');
    $('body').css('overflow', 'hidden');
  }
  function pcsCloseModal(id) {
    $('#' + id).removeClass('open');
    $('body').css('overflow', '');
  }
  window.pcsOpenModal = pcsOpenModal;
  window.pcsCloseModal = pcsCloseModal;

  /* Backdrop click closes modal */
  $(document).on('click', '.pcs-modal-backdrop', function(e) {
    if ($(e.target).hasClass('pcs-modal-backdrop')) {
      pcsCloseModal($(this).attr('id'));
    }
  });
  /* Esc key */
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
      $('.pcs-modal-backdrop.open').each(function() {
        pcsCloseModal($(this).attr('id'));
      });
    }
  });
  /* Close button */
  $(document).on('click', '.pcs-modal-close', function() {
    pcsCloseModal($(this).closest('.pcs-modal-backdrop').attr('id'));
  });

  /* ------------------------------------------------------------------ */
  /* Tab switching                                                         */
  /* ------------------------------------------------------------------ */
  $(document).on('click', '.pcs-tab', function() {
    var target = $(this).data('tab');
    $(this).closest('.pcs-tabs').find('.pcs-tab').removeClass('active');
    $(this).addClass('active');
    if (target) {
      var $section = $(this).closest('.pcs-wrap');
      $section.find('.pcs-tab-panel').removeClass('active');
      $section.find('.pcs-tab-panel[data-panel="' + target + '"]').addClass('active');
    }
  });

  /* ------------------------------------------------------------------ */
  /* AJAX helper                                                           */
  /* ------------------------------------------------------------------ */
  function pcsAjax(action, data, successCb, errorCb) {
    data._nonce = (typeof PCS !== 'undefined') ? PCS.nonce : '';
    data.action = action;
    $.post(
      (typeof PCS !== 'undefined') ? PCS.ajax_url : ajaxurl,
      data,
      function(res) {
        if (res.success) {
          if (typeof successCb === 'function') successCb(res.data);
        } else {
          var msg = (res.data && res.data.message) ? res.data.message :
                    (typeof PCS !== 'undefined' ? PCS.strings.error : 'An error occurred.');
          pcsToast(msg, 'error');
          if (typeof errorCb === 'function') errorCb(res.data);
        }
      }
    ).fail(function() {
      pcsToast((typeof PCS !== 'undefined') ? PCS.strings.error : 'Request failed.', 'error');
    });
  }

  /* ------------------------------------------------------------------ */
  /* Approve store                                                         */
  /* ------------------------------------------------------------------ */
  $(document).on('click', '.pcs-btn-approve-store', function() {
    var $btn    = $(this);
    var storeId = $btn.data('store-id');

    // Pre-fill modal with current store values
    $('#pcs-approve-store-id').val(storeId);

    var currentTier    = $btn.data('store-tier');
    var currentPayment = $btn.data('store-payment');
    var currentCredit  = $btn.data('store-credit');
    var currentTeam    = $btn.data('store-team');

    // Set tier — if store already has a B2B tier, pre-select it; otherwise default to b2b_a
    if (currentTier && currentTier !== 'b2c' && currentTier !== '') {
      $('#pcs-approve-tier').val(currentTier);
    } else {
      // New application — default to first B2B tier (b2b_a)
      var firstB2b = $('#pcs-approve-tier option').filter(function() {
        return $(this).val() !== 'b2c' && $(this).val() !== '';
      }).first().val();
      $('#pcs-approve-tier').val(firstB2b || 'b2b_a');
    }

    if (currentPayment) $('#pcs-approve-payment-terms').val(currentPayment);
    if (currentCredit)  $('#pcs-approve-credit-limit').val(currentCredit);
    if (currentTeam)    $('#pcs-approve-team-size').val(currentTeam);

    // Update modal title based on context
    var isEdit = $btn.text().trim() === 'Edit';
    $('.pcs-modal-backdrop#pcs-modal-approve .pcs-modal-title').text(
      isEdit ? '✏️ Edit B2B Store' : '✅ Approve B2B Application'
    );

    pcsOpenModal('pcs-modal-approve');
  });

  $(document).on('click', '#pcs-confirm-approve', function() {
    var $btn  = $(this).prop('disabled', true).text('Approving…');
    var data  = {
      store_id:      $('#pcs-approve-store-id').val(),
      tier:          $('#pcs-approve-tier').val(),
      payment_terms: $('#pcs-approve-payment-terms').val(),
      credit_limit:  $('#pcs-approve-credit-limit').val(),
      team_size:     $('#pcs-approve-team-size').val(),
    };
    pcsAjax('pcs_approve_store', data, function(d) {
      pcsToast(d.message || 'Store approved!', 'success');
      pcsCloseModal('pcs-modal-approve');
      setTimeout(function() { location.reload(); }, 800);
    }, function() {
      $btn.prop('disabled', false).text('Approve');
    });
  });

  /* ------------------------------------------------------------------ */
  /* Reject store                                                          */
  /* ------------------------------------------------------------------ */
  $(document).on('click', '.pcs-btn-reject-store', function() {
    var storeId = $(this).data('store-id');
    $('#pcs-reject-store-id').val(storeId);
    pcsOpenModal('pcs-modal-reject');
  });

  $(document).on('click', '#pcs-confirm-reject', function() {
    var $btn = $(this).prop('disabled', true).text('Rejecting…');
    pcsAjax('pcs_reject_store', {
      store_id: $('#pcs-reject-store-id').val(),
      reason:   $('#pcs-reject-reason').val(),
    }, function(d) {
      pcsToast(d.message || 'Store rejected.', 'warning');
      pcsCloseModal('pcs-modal-reject');
      setTimeout(function() { location.reload(); }, 800);
    }, function() {
      $btn.prop('disabled', false).text('Reject');
    });
  });

  /* ------------------------------------------------------------------ */
  /* Send invitation                                                       */
  /* ------------------------------------------------------------------ */
  $(document).on('submit', '#pcs-form-invitation', function(e) {
    e.preventDefault();
    var $btn = $(this).find('[type=submit]').prop('disabled', true).text('Sending…');
    var email   = $('#pcs-inv-email').val();
    var company = $('#pcs-inv-company').val();
    var tier    = $('#pcs-inv-tier').val();
    var tierLabel = $('#pcs-inv-tier option:selected').text().trim();
    pcsAjax('pcs_send_invitation', {
      email:        email,
      company:      company,
      contact:      $('#pcs-inv-contact').val(),
      tier:         tier,
      message:      $('#pcs-inv-message').val(),
      expires_days: $('#pcs-inv-expires').val(),
    }, function(d) {
      pcsToast(d.message || 'Invitation sent!', 'success');
      $('#pcs-form-invitation')[0].reset();
      $btn.prop('disabled', false).text('📨 Send invitation');
      /* Append new row to table immediately — no reload */
      var now     = new Date();
      var months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var expires = new Date(now.getTime() + ($('#pcs-inv-expires').val() || 14) * 24*60*60*1000);
      var expStr  = months[expires.getMonth()] + ' ' + expires.getDate() + ', ' + expires.getFullYear();
      var displayName = company || email;
      var row = '<tr id="pcs-inv-new-' + (Date.now()) + '">'
              + '<td><div style="font-weight:600;">' + $('<span>').text(displayName).html() + '</div>'
              + '<div style="font-size:11px;color:var(--pcs-text-soft);">' + $('<span>').text(email).html() + '</div></td>'
              + '<td><span class="pcs-tier-pill" data-tier="' + tier + '">' + $('<span>').text(tierLabel).html() + '</span></td>'
              + '<td style="font-size:12px;color:var(--pcs-text-soft);">Just now</td>'
              + '<td><span class="pcs-status-pill" data-status="pending"><span class="dot"></span>Pending</span></td>'
              + '<td style="font-size:12px;color:var(--pcs-text-soft);">' + expStr + '</td>'
              + '<td><button class="pcs-btn pcs-btn-tertiary pcs-btn-sm">Resend</button></td>'
              + '</tr>';
      $('#pcs-inv-empty-row').remove();
      $('#pcs-invitations-tbody').prepend(row);
    }, function() {
      $btn.prop('disabled', false).text('📨 Send invitation');
    });
  });

  /* ------------------------------------------------------------------ */
  /* Update customer tier (inline select)                                  */
  /* ------------------------------------------------------------------ */
  $(document).on('change', '.pcs-tier-select', function() {
    var $sel    = $(this);
    var userId  = $sel.data('user-id');
    var newTier = $sel.val();
    pcsAjax('pcs_update_customer_tier', {
      user_id: userId,
      tier:    newTier,
      note:    'Changed via admin panel',
    }, function(d) {
      pcsToast(d.message || 'Tier updated!', 'success');
      /* Refresh tier pill next to the select */
      var $pill = $sel.closest('tr').find('.pcs-tier-pill');
      if ($pill.length) {
        $pill.attr('data-tier', newTier).text(pcs_tier_label(newTier));
      }
    });
  });

  function pcs_tier_label(tier) {
    var map = {
      b2c: 'B2C', b2b_a: 'B2B · Tier A', b2b_b: 'B2B · Tier B',
      b2b_c: 'B2B · Tier C', vip: '★ VIP',
    };
    return map[tier] || tier;
  }

  /* ------------------------------------------------------------------ */
  /* Delete pricing rule                                                   */
  /* ------------------------------------------------------------------ */
  $(document).on('click', '.pcs-btn-delete-rule', function() {
    var id       = $(this).data('rule-id');
    var ruleName = $(this).data('rule-name') || 'this rule';
    var $row     = $(this).closest('tr');
    pcsConfirm('🗑️ Delete pricing rule?', 'Are you sure you want to delete <strong>' + $('<span>').text(ruleName).html() + '</strong>? This cannot be undone.', 'Delete', '#ef4444', function() {
      pcsAjax('pcs_delete_pricing_rule', { id: id }, function(d) {
        pcsToast(d.message || 'Rule deleted.', 'success');
        $row.fadeOut(300, function() { $row.remove(); });
      });
    });
  });

  /* ------------------------------------------------------------------ */
  /* Save settings form                                                    */
  /* ------------------------------------------------------------------ */
  $(document).on('submit', '#pcs-settings-form', function(e) {
    e.preventDefault();
    var $btn  = $(this).find('[type=submit]').prop('disabled', true).text('Saving…');
    var data  = $(this).serializeArray().reduce(function(obj, item) {
      obj[item.name] = item.value; return obj;
    }, {});
    pcsAjax('pcs_save_store_settings', data, function(d) {
      pcsToast(d.message || 'Settings saved!', 'success');
      $btn.prop('disabled', false).text('Save changes');
    }, function() {
      $btn.prop('disabled', false).text('Save changes');
    });
  });

  /* ------------------------------------------------------------------ */
  /* Reusable confirm modal                                                */
  /* ------------------------------------------------------------------ */
  window.pcsConfirm = function(title, body, confirmLabel, confirmColor, onConfirm) {
    var modal = document.getElementById('pcs-global-confirm-modal');
    if (!modal) {
      $('body').append(
        '<div id="pcs-global-confirm-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:999999;align-items:center;justify-content:center;">'
        + '<div style="background:#fff;border-radius:14px;padding:28px;width:400px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.2);text-align:center;">'
        + '<h3 id="pcs-gcm-title" style="margin:0 0 10px;font-size:17px;font-weight:700;color:#111827;"></h3>'
        + '<p id="pcs-gcm-body" style="margin:0 0 22px;font-size:13px;color:#6b7280;line-height:1.6;"></p>'
        + '<div style="display:flex;gap:10px;justify-content:center;">'
        + '<button id="pcs-gcm-cancel" style="padding:10px 22px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>'
        + '<button id="pcs-gcm-confirm" style="padding:10px 22px;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;"></button>'
        + '</div></div></div>'
      );
      modal = document.getElementById('pcs-global-confirm-modal');
      $(modal).on('click', function(e) { if (e.target === this) $(this).hide(); });
      $('#pcs-gcm-cancel').on('click', function() { $(modal).hide(); });
    }
    $('#pcs-gcm-title').text(title);
    $('#pcs-gcm-body').html(body);
    $('#pcs-gcm-confirm').text(confirmLabel || 'Confirm').css('background', confirmColor || '#ef4444');
    $(modal).css('display','flex');
    $('#pcs-gcm-confirm').off('click').on('click', function() {
      $(modal).hide();
      if (typeof onConfirm === 'function') onConfirm();
    });
  };

  /* Confirm any [data-confirm] button via modal instead of browser confirm */
  $(document).on('click', '[data-confirm]', function(e) {
    var $btn = $(this);
    var msg  = $btn.data('confirm');
    e.preventDefault(); e.stopImmediatePropagation();
    pcsConfirm('⚠️ Confirm action', msg, 'Confirm', '#ef4444', function() {
      $btn.removeAttr('data-confirm').trigger('click');
    });
  });

  /* ------------------------------------------------------------------ */
  /* Inline row edit toggle                                               */
  /* ------------------------------------------------------------------ */
  $(document).on('click', '.pcs-row-toggle-edit', function() {
    $(this).closest('tr').next('.pcs-edit-row').toggle();
  });

})(jQuery);
