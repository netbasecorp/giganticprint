'use strict';

showNBDLicensePopup = function($event){
   $event.stopPropagation();
   jQuery('.nbd-license-popup').addClass('active');
};
hideNBDLicensePopup = function(event, force){
    if(event.target.id == 'nbd-license-popup' || force ){
        jQuery('.nbd-license-popup').removeClass('active');
    }
}