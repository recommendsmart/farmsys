/**
 * @file
 * The label library.
 */

(function ($, Drupal) {

  Drupal.behaviors.propertiesFieldLabel = {
    attach: function (context, settings) {
      $('.field--widget-properties-default .properties-label')
        .once('properties-label')
        .on('autocompleteopen', function () {
          $(this).on('change.formUpdated input.formUpdated', false);
        })
        .on('autocompleteclose', function () {
          $(this).off('change.formUpdated input.formUpdated', false);
        })
        .autocomplete('option', 'select', function (event, ui) {
          event.target.value = ui.item.label;

          var row = $(this).closest('tr');
          row.find('.machine-name-target').val(ui.item.value);
          row.find('.machine-name-value').text(ui.item.value);

          $(event.target).blur();

          return false;
        });
    }
  };

})(jQuery, Drupal);
