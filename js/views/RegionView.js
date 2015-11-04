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
     * @type {function}
     */
    template: _.template('<div class="panels-ipe-header"><h5>Region: <%= name %></h5></div>'),

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
     *   An optional selector if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      if (options.el) {
        this.model.set({html: this.$el.html()});
      }
      this.listenTo(this.model, 'change:state', this.changeState);
    },

    /**
     * Renders the wrapping elements and refreshes a block model.
     */
    render: function() {
      this.$('.panels-ipe-header').remove();
      if (this.model.get('state') == 'active') {
        this.$el.prepend(this.template(this.model.toJSON()));
      }
      return this;
    },

    changeState: function(model, value, options) {
      this.render();
      // Change state of all of our blocks.
      this.model.get('blockCollection').each(function(block){
        block.set('state', value);
      });
    }

  });

}(jQuery, _, Backbone, Drupal));
