'use strict';

var showAllProduct = function (e) {
  jQuery(e).hide();
  jQuery('.nbd-tem-list-product-wrap').addClass('see-all');
  jQuery('.nbd-tem-list-product-wrap ul li').removeClass('nbd-hide');
}