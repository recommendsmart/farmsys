/**
 * @file
 * Config Terms behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Move a block in the blocks table from one region to another.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the drag behavior to a applicable table element.
   */
  Drupal.behaviors.termDrag = {
    attach: function (context, settings) {
      var backStep = settings.config_terms.backStep;
      var forwardStep = settings.config_terms.forwardStep;
      // Get the blocks tableDrag object.
      var tableDrag = Drupal.tableDrag.config_terms;
      var $table = $('#config_terms');
      var rows = $table.find('tr').length;

      // When a row is swapped, keep previous and next page classes set.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        $table.find('tr.config-terms-term-preview').removeClass('config_terms-term-preview');
        $table.find('tr.config-terms-term-divider-top').removeClass('config_terms-term-divider-top');
        $table.find('tr.config-terms-term-divider-bottom').removeClass('config_terms-term-divider-bottom');

        var tableBody = $table[0].tBodies[0];
        if (backStep) {
          for (var n = 0; n < backStep; n++) {
            $(tableBody.rows[n]).addClass('config-terms-term-preview');
          }
          $(tableBody.rows[backStep - 1]).addClass('config-terms-term-divider-top');
          $(tableBody.rows[backStep]).addClass('config-terms-term-divider-bottom');
        }

        if (forwardStep) {
          for (var k = rows - forwardStep - 1; k < rows - 1; k++) {
            $(tableBody.rows[k]).addClass('config-terms-term-preview');
          }
          $(tableBody.rows[rows - forwardStep - 2]).addClass('config-terms-term-divider-top');
          $(tableBody.rows[rows - forwardStep - 1]).addClass('config-terms-term-divider-bottom');
        }
      };
    }
  };

})(jQuery, Drupal);
