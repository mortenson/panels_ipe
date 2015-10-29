/**
 * @file
 * Base Backbone model for a Region.
 */

(function (_, $, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.RegionModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.RegionModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.RegionModel# */{

      /**
       * The machine name of the region.
       *
       * @type {string}
       */
      name: null,

      /**
       * The label of the region.
       *
       * @type {string}
       */
      label: null,

      /**
       * A BlockCollection for all blocks in this region.
       *
       * @type {Drupal.panels_ipe.BlockCollection}
       *
       * @see Drupal.panels_ipe.BlockCollection
       */
      blocks: null,

      /**
       * The current state of the region.
       *
       * @type {string}
       */
      state: 'none'
    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.RegionCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.RegionCollection# */{

    /**
     * @type {Drupal.panels_ipe.RegionModel}
     */
    model: Drupal.panels_ipe.RegionModel
  });

}(_, jQuery, Backbone, Drupal));
