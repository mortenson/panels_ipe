/**
 * @file
 * The primary Backbone view for a Region.
 *
 * see Drupal.panels_ipe.RegionModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.RegionView = Backbone.View.extend(/** @lends Drupal.panels_ipe.RegionView# */{

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.RegionModel} options.model
     *   The region state model.
     * @param {string} options.name
     *   An optional region name if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      if (options.name) {
        this.el = "[data-region-name='" + options.name + "']";
      }
    }

  });

}(jQuery, _, Backbone, Drupal));
