'use strict';

var options = {
    success: function(files) {
        var scope = angular.element(document.getElementById("designer-controller")).scope();
        scope.getDropboxImages(files);
        if (scope.$root.$$phase !== "$apply" && scope.$root.$$phase !== "$digest") scope.$apply()
    },                                    
    linkType: "direct",
    multiselect: true,
    extensions: ['.jpg', '.jpeg', '.png']
};
var button = Dropbox.createChooseButton(options);
document.getElementById("nbdesigner_dropbox").appendChild(button);