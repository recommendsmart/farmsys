/**
 * @file
 * Scripts for the color palette.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.collapse = {
    attach(context, settings) {
      // Apply background color to the field & palette buttons.
      $('.colorpalette .color-btn').each(function(index, item) {
        var classes = $(this).attr('class');

        var res = classes.match("hexcode-(.{6})");
        if (res !== null && res.length) {
          $(this).find('.button').css("background", '#' + res[1]);
        }
      });

      // Highlight the already selected color in the color-palette form.
      if (typeof(drupalSettings.colorpalette) !== 'undefined') {
        // For uniformity, initialize all button values.
        $('.colorpalette .color-btn input.button').val(' ');

        // Fetch hexcode containing classes from launched button.
        var classes = $('[data-launch-button="' + drupalSettings.colorpalette.field_selector + '-btn"] .color-btn').attr('class').replace(' ', '.');
        // Highlight in the palette the selected color.
        var selector = '.colorpalette .' + classes + ' input.button';
        $(selector).val(String.fromCharCode(10004));
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
