/**
 * jQuery plugin for Recaptcha
 * It will load recaptcha javascript on demand and generate recaptcha form element in place pointed by selector
 * Usage: $('#selector').recaptcha({pubkey: '__APIKEY__'});
 * selector must have `id` attribute
 */
$.fn.extend({
    recaptcha: function (options) {
        var container, defaults;
        container = this;
        defaults = {
            'pubkey': null,
            'theme': "red"
        };
        options = $.extend(defaults, options);

        function generate() {
            Recaptcha.create(options.pubkey, container.attr('id'), {
                'theme': options.theme,
                'callback': Recaptcha.focus_response_field
            });
        }

        if (typeof (Recaptcha) === 'undefined') {
            $.getScript('http://www.google.com/recaptcha/api/js/recaptcha_ajax.js', function () {
                generate();
            });
        } else {
            generate();
        }
    }
});
