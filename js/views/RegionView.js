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
     */
    initialize: function (options) {
      this.model = options.model;
    }

  });

}(jQuery, _, Backbone, Drupal));
