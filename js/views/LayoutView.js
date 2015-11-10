/**
 * @file
 * The primary Backbone view for a Layout.
 *
 * see Drupal.panels_ipe.LayoutModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutView = Backbone.View.extend(/** @lends Drupal.panels_ipe.LayoutView# */{

    /**
     * @type {Drupal.panels_ipe.RegionModel}
     */
    model: null,

    /**
     * @type {Array}
     *   An array of child Drupal.panels_ipe.RegionViews.
     */
    regionViews: [],

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.LayoutModel} options.model
     *   The layout state model.
     * @param {string} options.el
     *   An optional selector if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      // Initialize our Region Views.
      if (this.el) {
        this.initRegionViews();
      }
      this.listenTo(this.model, 'change:active', this.changeState);
      this.listenTo(this.model, 'reset', this.initRegionViews);
    },

    /**
     * Re-renders our regions, we have no HTML to be re-rendered.
     */
    render: function() {
      // Re-render all of our regions.
      for (var i in this.regionViews) {
        this.regionViews[i].render();
      }
      return this;
    },

    changeState: function(model, value, options) {
      // Change state of all of our regions.
      this.model.get('regionCollection').each(function(region){
        region.set('active', value);
      });
      this.render();
    },

    initRegionViews: function() {
      this.model.get('regionCollection').each(function (region) {
        this.regionViews.push(new Drupal.panels_ipe.RegionView({
          'model': region,
          'el': "[data-region-name='" + region.get('name') + "']"
        }));
      }, this);
    }

  });

}(jQuery, _, Backbone, Drupal));
