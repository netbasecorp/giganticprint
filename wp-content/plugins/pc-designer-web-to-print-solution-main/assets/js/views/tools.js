'use strict';

jQuery( document ).ready( function($) {
    var editorCodeMirror = CodeMirror.fromTextArea( document.getElementById( "nbdsigner_custom_css" ), {lineNumbers: true, lineWrapping: true} );
    $('#nbdesigner_custom_css').on('click', function(e){
        var formdata = jQuery('#nbdesigner_custom_css_con').find('input').serialize();
        var content = editorCodeMirror.getValue();
        formdata = formdata + '&action=nbdesigner_custom_css&content=' + encodeURIComponent( content );
        jQuery('#nbdesigner_custom_css_loading').removeClass('nbdesigner_loaded');
        jQuery.post(admin_nbds.url, formdata, function(_data){
            jQuery('#nbdesigner_custom_css_loading').addClass('nbdesigner_loaded');
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