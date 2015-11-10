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
     * @type {Drupal.panels_ipe.RegionModel}
     */
    model: null,

    /**
     * @type {Array}
     *   An array of child Drupal.panels_ipe.BlockViews.
     */
    blockViews: [],

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
      // Initialize our Block Views.
      this.initBlockViews();
      this.listenTo(this.model, 'change:active', this.changeState);
      this.listenTo(this.model, 'change:blockCollection', this.initBlockViews);
    },

    /**
     * Renders header elements and re-renders the region and contained blocks.
     */
    render: function() {
      // Decide if our header should be displayed.
      this.$('.panels-ipe-header').remove();
      if (this.model.get('active')) {
        this.$el.prepend(this.template(this.model.toJSON()));
      }
      // Re-render all of our sub-views of blocks.
      for (var i in this.blockViews) {
        this.blockViews[i].render();
      }
      return this;
    },

    changeState: function(model, value, options) {
      // Change state of all of our blocks.
      this.model.get('blockCollection').each(function(block){
        block.set('active', value);
      });
      this.render();
    },

    initBlockViews: function() {
      this.model.get('blockCollection').each(function(block) {
        this.blockViews.push(new Drupal.panels_ipe.BlockView({
          'model': block,
          'el': "[data-block-id='" + block.get('uuid') + "']"
        }));
      }, this);
    }

  });

}(jQuery, _, Backbone, Drupal));
