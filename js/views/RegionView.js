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
     * @type {string}
     */
    template: _.template('<div class="panels-ipe-region"><h5>Region: <%= name %></h5><%= html %></div>'),

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.RegionModel} options.model
     *   The region state model.
     * @param {string} options.el
     *   An optional selector name if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      if (options.el) {
        this.model.set({html: this.$el.html()});
      }
    },

    /**
     * Renders the wrapping elements and refreshes a block model.
     */
    render: function(){
      this.$el.html(this.template(this.model.toJSON()));
      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
