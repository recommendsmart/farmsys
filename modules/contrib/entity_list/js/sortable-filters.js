/**
 * @file
 * Custom behavior for sortable filters.
 */

(function ($, Drupal, once) {
  'use strict';
  Drupal.behaviors.sortableFilters = {

    attach: function (context) {
      once("sortable-filter", "body", context).forEach(function () {
        $('.sortable-filters').each(function (key, elem) {
          $(elem).change(function (e) {
            var $elem = $(this);
            var urlParams = new URLSearchParams(window.location.search);
            urlParams.set($elem.attr('data-field-name'), $elem.val());
            window.location.search = urlParams;
          })
        })
      });
    }

  }
})(jQuery, Drupal, once);
