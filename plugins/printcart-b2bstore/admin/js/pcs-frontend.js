/* Printcart B2B — Frontend JS */
(function($) {
  'use strict';

  /* Animate progress bars on load */
  $(function() {
    $('.pcs-progress-fill[data-width]').each(function() {
      var w = $(this).data('width');
      setTimeout(function() { $(this).css('width', w + '%'); }.bind(this), 100);
    });
  });

  /* Tier select AJAX in account overview */
  $(document).on('change', '.pcs-tier-select-fe', function() {
    var $sel  = $(this);
    var uid   = $sel.data('user-id');
    var tier  = $sel.val();
    if ( ! uid || ! tier ) return;

    $.post( (typeof PCS_FE !== 'undefined' ? PCS_FE.ajax_url : '/wp-admin/admin-ajax.php'), {
      action:   'pcs_update_customer_tier',
      _nonce:   (typeof PCS_FE !== 'undefined' ? PCS_FE.nonce : ''),
      user_id:  uid,
      tier:     tier,
      note:     'Changed from My Account',
    }, function(res) {
      if ( res.success ) {
        var msg = document.createElement('div');
        msg.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#16a34a;color:#fff;padding:10px 18px;border-radius:8px;font-weight:600;z-index:9999;font-size:13px;';
        msg.textContent = res.data.message || 'Tier updated!';
        document.body.appendChild(msg);
        setTimeout(function() { msg.remove(); }, 2500);
        location.reload();
      }
    });
  });

})(jQuery);
