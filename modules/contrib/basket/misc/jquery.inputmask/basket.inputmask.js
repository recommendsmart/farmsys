/**
 * @file
 * JavaScript behaviors for jquery.inputmask integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize input masks.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.basketInputMask = {
    attach: function (context) {
      if (!$.fn.inputmask) {
        return;
      }
      if (typeof Inputmask !== 'undefined') {
        Inputmask.extendDefaults({
          'removeMaskOnSubmit': false,
          'clearIncomplete': true,
          'showMaskOnHover': false,
        });
      }
      $(context).find('input.js-basket-input-mask').once('basket-input-mask').inputmask();
    }
  };

})(jQuery, Drupal);
