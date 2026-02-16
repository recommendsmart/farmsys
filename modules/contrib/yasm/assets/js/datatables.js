(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.YasmDatatables = {
    attach: function (context, settings) {
      let locale = drupalSettings.datatables.locale;
      let datatableSettings = {
        order: [],
        destroy: true,
        dom: 'Bfrtip',
        buttons: [
          'copyHtml5',
          'excelHtml5',
          'csvHtml5',
          {
            extend: 'pdfHtml5',
            orientation: 'landscape'
          }
        ]
      };

      if (locale.length) {
        let localeSettings = {
          language: {url: locale},
          retrieve: true
        };

        Object.assign(datatableSettings, localeSettings);
      }

      $('.datatable').dataTable(datatableSettings);
    }
  };

})(jQuery, Drupal, drupalSettings);
