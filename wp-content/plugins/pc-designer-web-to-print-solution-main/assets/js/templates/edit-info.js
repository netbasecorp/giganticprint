'use strict';

jQuery(function () {
  var NBD_Settings = {

    init: function () {
      jQuery('a.nbd-banner-drag').on('click', this.imageUpload);
      jQuery('a.nbd-remove-banner-image').on('click', this.removeBanner);
    },

    imageUpload: function (e) {
      e.preventDefault();

      var file_frame,
        self = jQuery(this);

      if (file_frame) {
        file_frame.open();
        return;
      }

      // Create the media frame.
      file_frame = wp.media.frames.file_frame = wp.media({
        title: jQuery(this).data('uploader_title'),
        button: {
          text: jQuery(this).data('uploader_button_text')
        },
        multiple: false
      });

      file_frame.on('select', function () {
        var attachment = file_frame.state().get('selection').first().toJSON();

        var wrap = self.closest('.nbd-banner');
        wrap.find('input.nbd-file-field').val(attachment.id);
        wrap.find('img.nbd-banner-img').attr('src', attachment.url);
        jQuery('.image-wrap', wrap).removeClass('nbd-hide');

        jQuery('.button-area').addClass('nbd-hide');
      });

      file_frame.open();

    },

    removeBanner: function (e) {
      e.preventDefault();

      var self = jQuery(this);
      var wrap = self.closest('.image-wrap');
      var instruction = wrap.siblings('.button-area');

      wrap.find('input.nbd-file-field').val('0');
      wrap.addClass('nbd-hide');
      instruction.removeClass('nbd-hide');
    },
  };

  NBD_Settings.init();
  jQuery('#nbd-artist-form').on( 'submit', function (ev) {
    ev.preventDefault();
    var formdata = jQuery('#nbd-artist-form').find('input, textarea, select').serialize();
    formdata = formdata + '&action=nbd_update_artist_info';
    jQuery('img.nbd-loading').removeClass('loaded');
    jQuery('#nbd-artist-form').addClass('loading');
    jQuery.post(nbds_frontend.url, formdata, function (res) {
      jQuery('img.nbd-loading').addClass('loaded');
      jQuery('#nbd-artist-form').removeClass('loading');
      if (res['result'] == 1) alert('Update successful!'); else alert('Oop! try again later!');
    }, 'json');
  });
});