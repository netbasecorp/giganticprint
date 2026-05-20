'use strict';

jQuery( document ).ready( function($) {
  function export_product_data(){
      var product_id = $('.nbie-export-product').val();
      var formdata = new FormData();
      formdata.append('product_id', product_id);
      formdata.append('action', 'nbd_export_product');
      $.ajax({
          url: admin_nbds.url,
          data: formdata,
          processData: false,
          contentType: false,
          type: 'POST',
          success: function(data){
              if(parseInt(data.flag) == 1){
                  alert('Success!');
              }else {
                  alert('Oops! Try again!');
              }
          }
      });
  }

  var currentImportStep = 1, totalStep;

  function import_product_data(){
      var product_id = $('.nbie-import-product').val();
      if( !product_id ) return;
      var formdata = new FormData();
      formdata.append('product_id', product_id);
      formdata.append('step', currentImportStep);
      formdata.append('action', 'nbd_import_product');
      jQuery('.nbp-loading-wrap').addClass('nbp-show');
      $.ajax({
          url: admin_nbds.url,
          data: formdata,
          processData: false,
          contentType: false,
          type: 'POST',
          success: function(data){
              if( parseInt( data.flag ) == 1 ){
                  totalStep = data.total_steps;
                  jQuery('#nbp-processing').show();
                  $('#nbp-process-loaded').html(currentImportStep);
                  $('#nbp-process-total').html(totalStep);
                  if( data.total_steps > data.current_step ){
                      currentImportStep = data.current_step * 1 + 1;
                      import_product_data();
                  }else{
                      jQuery('.nbp-loading-wrap').removeClass('nbp-show');
                      jQuery('#nbp-processing').hide();
                  }
              }else {
                  alert('Oops! Try again!');
              }
          }
      });
  }

  jQuery('.nbix-export-btn').on('click', function(){
      export_product_data();
  });

  jQuery('.nbix-import-btn').on('click', function(){
      import_product_data();
  });

  jQuery('.nbix-pseudo-result').on('click', function(){
      jQuery(this).parents('.nbix-pseudo-dropdown').find('.nbix-pseudo-list').toggleClass('active');
  });

  jQuery('.nbix-pseudo-list-item, .nbix-pseudo-list-item svg, .nbix-pseudo-result-name').on('click', function(){
      var id = jQuery(this).hasClass('nbix-pseudo-list-item') ? jQuery(this).attr('data-id') : jQuery(this).parent('.nbix-pseudo-list-item').attr('data-id'),
      name = jQuery(this).hasClass('nbix-pseudo-list-item') ? jQuery(this).attr('data-name') : jQuery(this).parent('.nbix-pseudo-list-item').attr('data-name');
      jQuery(this).parents('.nbie-import-product-options').find('.nbie-import-product').val( id );
      jQuery(this).parents('.nbix-pseudo-list').removeClass('active');
      jQuery(this).parents('.nbie-import-product-options').find('.nbix-pseudo-result-name').html( name );
  });

  jQuery(document).on('click', function(event){
      var wrapEl = jQuery('.nbie-import-product-options');
      if( wrapEl.has(event.target).length == 0 && !wrapEl.is(event.target) ){
          jQuery('.nbix-pseudo-list').removeClass('active');
      }
  });
});