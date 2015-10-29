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
       * The currently active block.
       *
       * @type {Drupal.panels_ipe.BlockModel}
       *
       * @see Drupal.panels_ipe.BlockModel.states
       */
      activeBlock: null,

      /**
       * The currently active region.
       *
       * @type {Drupal.panels_ipe.RegionModel}
       *
       * @see Drupal.panels_ipe.RegionModel.states
       */
      activeRegion: null
    }

  });

}(Backbone, Drupal));
