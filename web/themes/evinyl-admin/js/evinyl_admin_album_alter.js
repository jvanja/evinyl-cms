(function ($, Drupal) {
 Drupal.behaviors.evinyl_album = {
    attach: function (context, settings) {

      if(settings.path.currentPath.includes('/edit')) {
        $('#tracks details').attr('open', 'true')
      }
    }
  };

})(jQuery, Drupal);

