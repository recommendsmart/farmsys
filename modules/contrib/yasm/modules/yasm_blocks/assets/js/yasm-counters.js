(function ($) {
  'use strict';

  Drupal.behaviors.yasmCounter = {
    attach: function (context, settings) {
      function startCounter(element, duration) {
        element.addClass('counter-on');
        element.prop('contador', 0).animate({
          contador: element.text()
        }, {
          duration: duration,
          easing: 'swing',
          step: function (now) {
            element.text(Math.ceil(now));
          },
          complete: function () {
            element.removeClass('counter-on');
          }
        });
      }

      $('.yasm-counters .count').once('yasm-counter').each(function () {
        if ($(this).text() > 0) {
          startCounter($(this), 5000);
        }
      });

      $('.yasm-counters .count').on('mouseenter', function () {
        if (!$(this).hasClass('counter-on') && $(this).text() > 0) {
          startCounter($(this), 500);
        }
      });
    }
  };

}(jQuery));
