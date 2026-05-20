'use strict';

document.addEventListener('DOMContentLoaded', function() {
  if(typeof window.parent.NBDESIGNERPRODUCT != 'undefined'){
      window.parent.NBDESIGNERPRODUCT.nbdesigner_ready(); 
      window.parent.NBDESIGNERPRODUCT.hide_loading_iframe();
  };
});  