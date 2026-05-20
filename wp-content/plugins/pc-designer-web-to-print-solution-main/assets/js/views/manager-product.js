'use strict';

var changeLink = function(e){
  var vid = jQuery(e).val(),
  btn = jQuery(e).parents('table').siblings('p').find('a.nbd-create'),
  origin_fref = btn.data('href'),
  new_href = origin_fref + '&variation_id=' + vid;
  btn.attr('href', new_href);
}

var openProductConfig = function( title, url ){
  tb_show(title, url ); 
}