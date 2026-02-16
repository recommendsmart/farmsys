/**
 * @file
 * Behaviors for setting summaries on element form.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on content type edit forms.
   */
  Drupal.behaviors.elementForm = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.element-form-publishing-status').drupalSetSummary(function (context) {
        var $statusContext = $(context);
        var statusCheckbox = $statusContext.find('#edit-status-value');

        if (statusCheckbox.is(':checked')) {
          return Drupal.t('Published');
        }

        return Drupal.t('Not published');
      });

      $context.find('.element-form-authoring-information').drupalSetSummary(function (context) {
        var $authorContext = $(context);
        var authorField = $authorContext.find('input');

        if (authorField.val().length) {
          return authorField.val();
        }
      });
    }
  };
})(jQuery, Drupal);
