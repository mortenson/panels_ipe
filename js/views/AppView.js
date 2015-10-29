/**
 * @file
 * The primary Backbone view for Panels IPE.
 *
 * see Drupal.panels_ipe.AppModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.AppView = Backbone.View.extend(/** @lends Drupal.panels_ipe.AppView# */{

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.AppModel} options.model
     *   The application state model.
     * @param {Drupal.panels_ipe.RegionCollection} options.regionsCollection
     *   All on-page regions.
     * @param {Drupal.panels_ipe.BlockCollection} options.blocksCollection
     *   All on-page blocks.
     */
    initialize: function (options) {

    }

  });

}(jQuery, _, Backbone, Drupal));
