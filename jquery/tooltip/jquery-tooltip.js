/**
 * jquery.tooltip.js
 * @copyright Timi (http://timiportfolio.com/)
 */
$.fn.extend({
    tooltip: function (options) {
        return this.each(function () {
            $(this).addClass('tooltip');
            $(this).append("");
            $(this).hover(function () {
                $(this).find(".bubble").filter(':not(:animated)').animate({opacity: "show", bottom: "25"}, "slow");
                var hover_txt = $(this).attr("title");
                $(this).data("hover_txt", hover_txt);
                $(this).attr("title", "");
                $(this).find("em").text(hover_txt);
            }, function () {
                $(this).find(".bubble").filter(':not(:animated)').animate({opacity: "hide", bottom: "30"}, "fast");
                $(this).attr("title", $(this).data("hover_txt"));
            });
        });
    }
});
