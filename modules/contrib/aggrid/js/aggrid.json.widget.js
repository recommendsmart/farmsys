/**
 * @file
 * JavaScript behaviors for aggrid JSON EDITOR integration.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Initialize aggrid JSON editor.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.aggridJsonEditor = {
        attach: function (context) {
            // Aggrid JSON editor.
            $(context).find('.aggrid-json-widget').once('aggridJsonEditor').each(function () {
                // Get the JSON data.
                let jsonData = JSON.parse($(this).text());
                // Stringify it.
                jsonData = JSON.stringify(jsonData, undefined, 2);
                // Put it back.
                $(this).text(jsonData);
            });
        }
    };

})(jQuery, Drupal);
