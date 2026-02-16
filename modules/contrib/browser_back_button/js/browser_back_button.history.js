/**
 * @file
 * Back Button History Redirect js file.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";
   var flag = false; 
   Drupal.behaviors.browser_back_button = {
     attach: function (context, settings) {       
        $('#back-button-wrapper', context).once().on('click', function (e) {
          window.history.back();
        });        
        $(window, context).once().on('pageshow', function ( event ) {          
          var historyBack = event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2);
          var reload_status = drupalSettings.browser_back_button.data.reload_status;
          if(historyBack && reload_status == 1) {
            window.location.reload();
          }
        });          
       }    
   };
   
 })(jQuery, Drupal, drupalSettings);
 