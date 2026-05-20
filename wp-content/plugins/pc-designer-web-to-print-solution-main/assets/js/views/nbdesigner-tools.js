'use strict';

jQuery( document ).ready( function($) {
  var editorCodeMirrorJS = CodeMirror.fromTextArea( document.getElementById( "nbdsigner_custom_js" ), {lineNumbers: true, lineWrapping: true} );
  $('#nbdesigner_custom_js').on('click', function(e){
      var formdata = jQuery('#nbdesigner_custom_js_con').find('input').serialize();
      var content = editorCodeMirrorJS.getValue();
      formdata = formdata + '&action=nbdesigner_custom_js&content=' + encodeURIComponent( content );
      jQuery('#nbdesigner_custom_js_loading').removeClass('nbdesigner_loaded');
      jQuery.post(admin_nbds.url, formdata, function(_data){
          jQuery('#nbdesigner_custom_js_loading').addClass('nbdesigner_loaded');
          var data = JSON.parse(_data);
          if (data.flag == 1) {
              swal(admin_nbds.nbds_lang.complete, data.mes, "success");
          }else{
              swal({
                  title: "Oops!",
                  text: data.mes,
                  imageUrl: admin_nbds.assets_images + "dinosaur.png"
              });
          }
      });
  });
});