/**
 * URL params extraction utility
 * Usage:
 *
 *  $.getAllParams(); // return {...} from window.location.href
 *  $.getAllParams('http://...'); // return {...} from string
 *  $.getAllParams()['test'];
 *  $.getParam('test');
 *  $.getParam('test', 'http://');
 *  $.getParam('test', 'http://example.com?test'); will return NULL
 *  $.getParam('test', 'http://example.com?test='); will return empty string
 *  $.getParam('test', 'http://example.com?test=xxx'); will return 'xxx'
 *
 * @param  {string}                  URL  (optional) when not passed method will use window.location.href
 * @return {object}                  Object containing all params and their values
 */
$.extend({
    getAllParams: function (string) {
        string = string || window.location.href;

        var params = string.slice(string.indexOf('?') + 1).split('&'),
            paramsObject = {};

        if(params[0] === string) {
           return {};
        }

        $.each(params, function(index, value) {
            var pair = value.split('=');
            paramsObject[pair[0]] = (typeof pair[1] === 'undefined') ? null : pair[1];
        });

        return paramsObject;
    },
    getParam: function (name, string) {
        return $.getAllParams(string)[name];
    }
});
