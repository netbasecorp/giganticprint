"use strict";

jQuery(document).ready(function ($) {
  var ajaxUrl = typeof admin_nbds !== "undefined" ? admin_nbds.url : ajax_url;
  jQuery("#printcart-connection-width-dashboard").on("click", function (e) {
    var isHttps = jQuery(
      '.printcart-auto-connected input[name="printcart_is_https"]'
    ).val();
    var shopUrl = jQuery(
      '.printcart-auto-connected input[name="printcart_home_url"]'
    ).val();
    var email = jQuery(
      '.printcart-auto-connected input[name="printcart_email"]'
    ).val();
    var storeName = jQuery(
      '.printcart-auto-connected input[name="printcart_store_name"]'
    ).val();
    var userName = jQuery(
      '.printcart-auto-connected input[name="printcart_user_name"]'
    ).val();

    var text = jQuery(this).html();
    var result = jQuery(".printcart-results");
    result.html("");

    if (!isHttps) {
      return;
    }
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxUrl,
      data: {
        action: "printcart_generate_key_api",
        shopUrl: shopUrl,
        email: email,
        storeName: storeName,
        userName: userName,
      },
      context: this,
      beforeSend: function () {
        jQuery(this).html("Generating key...");
        jQuery(this).attr("disabled", "disabled");
      },
      success: function (response) {
        jQuery(this).attr("disabled", false);
        jQuery(this).html(text);
        if (response.success && response.data) {
          if (
            response.data.flag &&
            response.data.secret &&
            response.data.sid &&
            response.data.unauth_token
          ) {
            jQuery(".nbd-setup-actions button.button-next").prop(
              "disabled",
              false
            );
            jQuery("#nbdesigner_printcart_api_sid").val(response.data.sid);
            jQuery("#nbdesigner_printcart_api_secret").val(
              response.data.secret
            );
            jQuery("#nbdesigner_printcart_api_unauth_token").val(
              response.data.unauth_token
            );
            result.html("<b>Connect successfully!</b>");
            result.css("color", "#0f631e");
          } else {
            jQuery(this).attr("disabled", false);
            result.html(response.data.message);
            result.css("color", "#f11");
          }
        }
      },
      error: function (error) {
        jQuery(this).attr("disabled", false);
        jQuery(this).html("Try again");
      },
    });
  });
  jQuery(".pc-check-connection-dashboard").on("click", function () {
    var text = jQuery(this).html();
    var sid = jQuery('.printcart-form input[name="printcart_sid"]').val();
    var secret = jQuery('.printcart-form input[name="printcart_secret"]').val();
    var unauth_token = jQuery(
      '.printcart-form input[name="printcart_unauth_token"]'
    ).val();
    var result_check = jQuery(".printcart-results-check");
    result_check.html("");

    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxUrl,
      data: {
        action: "printcart_check_connection_dashboard",
        sid: sid,
        secret: secret,
        unauth_token: unauth_token,
      },
      context: this,
      beforeSend: function () {
        jQuery(this).html("Checking...");
        jQuery(this).attr("disabled", "disabled");
      },
      success: function (response) {
        jQuery(this).attr("disabled", false);
        jQuery(this).html(text);
        if (response.success && response.data) {
          result_check.html(
            '<div style="color: #0f631e"><b>Connected.</b></div>'
          );
        } else {
          result_check.html('<div style="color: #f11"><b>Error.</b></div>');
        }
      },
      error: function (error) {
        jQuery(this).attr("disabled", false);
        jQuery(this).html("Try again");
      },
    });
  });
});
