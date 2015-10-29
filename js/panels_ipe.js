/**
 * @file
 * Attaches behavior for the Panels IPE module.
 *
 */

(function ($, _, Backbone, Drupal, drupalSettings, JSON, storage) {

    'use strict';

    /**
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.panels_ipe = {
        attach: function (context) {
          console.log('attached panels ipe');
        },
        detach: function (context, settings, trigger) {
          console.log('detached panels ipe');
        }
    };

    /**
     *
     * @namespace
     */
    Drupal.panels_ipe = {};

})(jQuery, _, Backbone, Drupal, drupalSettings, window.JSON, window.sessionStorage);
