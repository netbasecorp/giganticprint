'use strict';

window.wp = window.wp || {};
wp.template = _.memoize(function ( id ) {
    var compiled,
    options = {
        evaluate:    /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
        escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
        variable:    'data'
    };
    return function ( data ) {
        compiled = compiled || _.template( jQuery( '#tmpl-' + id ).html(),  options );
        return compiled( data );
    };
});
