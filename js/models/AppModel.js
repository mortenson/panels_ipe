/**
 * @file
 * The primary Backbone model for Panels IPE.
 *
 * @see Drupal.panels_ipe.AppView
 */

(function (Backbone, Drupal) {

  'use strict';

  /**
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.panels_ipe.AppModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.AppModel# */{

    /**
     * @type {object}
     *
     * @prop {Drupal.panels_ipe.BlockModel} activeBlock
     * @prop {Drupal.panels_ipe.RegionModel} activeRegion
     */
    defaults: /** @lends Drupal.quickedit.AppModel# */{

      /**
       * The application state.
       *
       * @type {string}
       */
      state: null,

      /**
       * The currently active block.
       *
       * @type {Drupal.panels_ipe.BlockModel}
       *
       * @see Drupal.panels_ipe.BlockModel
       */
      activeBlock: null,

      /**
       * The currently active region.
       *
       * @type {Drupal.panels_ipe.RegionModel}
       *
       * @see Drupal.panels_ipe.RegionModel
       */
      activeRegion: null,

      /**
       * A collection of all regions on screen.
       *
       * @type {Drupal.panels_ipe.RegionCollection}
       *
       * @see Drupal.panels_ipe.RegionCollection
       */
      regionCollection: null
    }

  });

}(Backbone, Drupal));
