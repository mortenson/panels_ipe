/**
 * @file
 * Base Backbone model for a Block Plugin.
 */

(function (_, $, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.BlockPluginModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.BlockPluginModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.BlockPluginModel# */{

      /**
       * The plugin ID.
       *
       * @type {string}
       */
      plugin_id: null,

      /**
       * The block's id (machine name).
       *
       * @type {string}
       */
      id: null,

      /**
       * The plugin label.
       *
       * @type {string}
       */
      label: null,

      /**
       * The category of the plugin.
       *
       * @type {string}
       */
      category: null,

      /**
       * The provider for the block (usually the module name).
       *
       * @type {string}
       */
      provider: null

    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.BlockPluginCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.BlockPluginCollection# */{

    /**
     * @type {Drupal.panels_ipe.BlockPluginModel}
     */
    model: Drupal.panels_ipe.BlockPluginModel,

    /**
     * Defines a sort parameter for the collection.
     *
     * @type {string}
     */
    comparator: 'category',

    /**
     * @type {function}
     */
    url: function() {
      return 'admin/panels_ipe/block_plugins'
    }

  });

}(_, jQuery, Backbone, Drupal));
