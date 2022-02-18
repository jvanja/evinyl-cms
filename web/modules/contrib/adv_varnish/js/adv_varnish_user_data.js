/**
 * @file
 * Handle user blocks behavior on the page.
 */
(function ($, drupalSettings) {

  "use strict";

  /**
   * Move user data blocks on appropriate place on the page.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.advVarnishUserBlocks = {
    attach(context) {
      // Replace placeholder with actual user data.
      $('#ad-varnish-user-blocks .ad-varnish-user-block').each(function () {
        var $this = jQuery(this);
        var $target = jQuery($this.attr('data-target'));
        if ($target.length > 0) {
          $target.replaceWith($this.html());
        }
      });
    }
  };

  if (typeof advUserBlocksSettings !== 'undefined') {
    $.extend(true, drupalSettings, advUserBlocksSettings);
  }

})(jQuery, drupalSettings);
