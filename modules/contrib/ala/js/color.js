/**
 * ALA color field.
 **/

(function ($, window, Drupal) {
  Drupal.behaviors.alaColorField = {
    attach: function attach(context) {

      $(".alaColorField", context).each(function () {
        $(this).attr('type', 'color');
      });

    }
  };
})(jQuery, window, Drupal);
