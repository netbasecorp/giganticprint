( function ( $ ) {
  'use strict';

  $(document).ready(function(){
      $('#nbdesigner_show_helper').on('click', function(e){
          e.preventDefault();
          $("html, body").animate({ scrollTop: $("#contextual-help-wrap").offset().top }, 500, function(){
              if(!$('#contextual-help-wrap').is(':visible')){
                  $('#contextual-help-link').trigger("click");
              }
              $('#tab-link-facebook a').trigger("click");
          });
      });
      $('#nbdesigner_google_drive_helper').on('click', function(e){
          e.preventDefault();
          $("html, body").animate({ scrollTop: $("#contextual-help-wrap").offset().top }, 500, function(){
              if(!$('#contextual-help-wrap').is(':visible')){
                  $('#contextual-help-link').trigger("click");
              }
              $('#tab-link-google_drive a').trigger("click"); 
          });
      });
  })
} ( jQuery ) )