(function (Drupal, $) {

  Drupal.behaviors.viewsDesignDialog = {
    attach: function (context) {
      $(context).find('.scroll').once('designDialog').on('change', 'select', function (e) {
        $(e.currentTarget).trigger('dialogContentResize');
      });
    }
  };

})(Drupal, jQuery);
