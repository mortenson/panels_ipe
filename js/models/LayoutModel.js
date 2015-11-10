/**
 * @file
 * Base Backbone model for a Layout.
 */

(function (_, $, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.LayoutModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.LayoutModel# */{

      /**
       * The layout machine name.
       *
       * @type {string}
       */
      id: null,

      /**
       * The layout label.
       *
       * @type {string}
       */
      label: null,

      /**
       * Whether or not this is the current layout.
       *
       * @type {bool}
       */
      current: false,

      /**
       * The wrapping HTML for this layout. Only used for initial rendering.
       *
       * @type {string}
       */
      html: null,

      /**
       * A collection of regions contained in this Layout.
       *
       * @type {Drupal.panels_ipe.RegionCollection}
       */
      regionCollection: null

    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.LayoutCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.LayoutCollection# */{

    /**
     * @type {Drupal.panels_ipe.LayoutModel}
     */
    model: Drupal.panels_ipe.LayoutModel,

    /**
     * @type {function}
     */
    url: function() {
      return Drupal.panels_ipe.urlRoot(drupalSettings) + '/layouts'
    }

  });

}(_, jQuery, Backbone, Drupal));
