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
     * @type {Drupal.panels_ipe.LayoutModel}
     */
    model: null,

    /**
     * @type {Array}
     *   An array of child Drupal.panels_ipe.BlockView objects.
     */
    blockViews: [],

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
      // Initialiaze our html, this never changes.
      if (this.model.get('html')) {
        this.$el.html(this.model.get('html'));
      }
      // Initialize our Block Views.
      if (this.el) {
        this.initBlockViews();
      }
      this.listenTo(this.model, 'change:active', this.changeState);
    },

    /**
     * Re-renders our blocks, we have no HTML to be re-rendered.
     */
    render: function() {
      // Re-render all of our blocks.
      for (var i in this.blockViews) {
        this.blockViews[i].render();
      }
      return this;
    },

    changeState: function(model, value, options) {
      this.render();
    },

    initBlockViews: function() {
      this.model.get('regionCollection').each(function (region) {
        region.get('blockCollection').each(function (block) {

          // If the target element doesn't exist, append an empty one.
          if (this.$("[data-block-id='" + block.get('uuid') + "']").length == 0) {
            var empty_elem = $('<div data-block-id="' + block.get('uuid') + '">');
            this.$('[data-region-name="' + region.get('name') + '"]').append(empty_elem);
          }

          this.blockViews.push(new Drupal.panels_ipe.BlockView({
            'model': block,
            'el': "[data-block-id='" + block.get('uuid') + "']"
          }));

          // Fetch the block content from the server
          if (typeof empty_elem != 'undefined') {
            block.fetch();
          }

        }, this);
      }, this);
    }

  });

}(jQuery, _, Backbone, Drupal));