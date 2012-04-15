/**
 * Usage:
 * $.getUrlVars()['varname'];
 * $.getUrlVar('varname');
 */
$.extend({
    getUrlVars: function () {
        var vars = [], hash, i, hashes;
        hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for (i = 0; i < hashes.length; i += 1) {
          hash = hashes[i].split('=');
          vars.push(hash[0]);
          vars[hash[0]] = (typeof(hash[1]) === 'undefined') ? null : hash[1];
        }
        return vars;
    },
    getUrlVar: function (name) {
        return $.getUrlVars()[name];
    }
});
