/**
 * @file
 * Base Backbone model for a Block.
 */

(function (_, $, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.BlockModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.BlockModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.BlockModel# */{

      /**
       * The unique ID of the block.
       *
       * @type {string}
       */
      id: null,

      /**
       * The current state of the block.
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
  Drupal.panels_ipe.BlockCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.BlockCollection# */{

    /**
     * @type {Drupal.panels_ipe.BlockModel}
     */
    model: Drupal.panels_ipe.BlockModel
  });

}(_, jQuery, Backbone, Drupal));
