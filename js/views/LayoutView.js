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
     * @type {function}
     */
    template_region_actions: _.template(
      '<div class="ipe-actions" data-region-action-id="<%= name %>">' +
      '  <h5>Region: <%= name %></h5>' +
      '  <ul class="ipe-action-list"></ul>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_region_option: _.template(
      '<option data-region-option-name="<%= name %>"><%= name %></option>'
    ),

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
     * @type {object}
     */
    events: {
      'mousedown [data-action-id="move"] > select': 'showBlockRegionList',
      'blur [data-action-id="move"] > select': 'hideBlockRegionList',
      'change [data-action-id="move"] > select': 'selectBlockRegionList',
      'click [data-action-id="up"]': 'moveBlock',
      'click [data-action-id="down"]': 'moveBlock'
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.LayoutModel} options.model
     *   The layout state model.
     */
    initialize: function (options) {
      this.model = options.model;
      // Initialize our html, this never changes.
      if (this.model.get('html')) {
        this.$el.html(this.model.get('html'));
      }
      this.listenTo(this.model, 'change:active', this.changeState);
    },

    /**
     * Re-renders our blocks, we have no HTML to be re-rendered.
     */
    render: function() {
      // Remove all existing BlockViews.
      for (var i in this.blockViews) {
        this.blockViews[i].remove();
      }
      this.blockViews = [];

      // Re-attach all BlockViews to appropriate regions.
      this.model.get('regionCollection').each(function (region) {
        region.get('blockCollection').each(function (block) {
          // Attach an empty element for our View to attach itself to.
          if (this.$('[data-block-id="' + block.get('uuid') + '"]').length == 0) {
            var empty_elem = $('<div data-block-id="' + block.get('uuid') + '">');
            this.$('[data-region-name="' + region.get('name') + '"]').append(empty_elem);
          }

          // Attach a View to this empty element.
          var block_view = new Drupal.panels_ipe.BlockView({
            'model': block,
            'el': '[data-block-id="' + block.get('uuid') + '"]'
          });
          this.blockViews.push(block_view);

          // Fetch the Block's content from the server, if needed.
          if (!block.get('html')) {
            block.fetch();
          }
          else {
            block_view.render();
          }

        }, this);
      }, this);

      return this;
    },

    /**
     * Prepends Regions and Blocks with action items.
     */
    changeState: function(model, value, options) {
      // Toggles the action headers on each RegionView and BlockView.
      this.model.get('regionCollection').each(function (region) {
        // Prepend or remove the action header for this region.
        if (value) {
          var selector = '[data-region-name="' + region.get('name') + '"]';
          this.$(selector).prepend(this.template_region_actions(region.toJSON()));
        }
        else {
          this.$('.ipe-actions').remove();
        }

        // BlockViews handle their own rendering, so just set the active value here.
        region.get('blockCollection').each(function (block) {
          block.set({'active': value});
        }, this);
      }, this);
      this.render();
    },

    /**
     * Replaces the "Move" button with a select list of regions.
     */
    showBlockRegionList: function(e) {
      // Get the BlockModel id (uuid).
      var id = $(e.currentTarget).closest('[data-block-action-id]').data('block-action-id');

      $(e.currentTarget).empty();

      // Add other regions to select list.
      this.model.get('regionCollection').each(function (region) {
        // If this is the current region, place it first in the list.
        if (region.get('blockCollection').get(id)) {
          $(e.currentTarget).prepend(this.template_region_option(region.toJSON()));
        }
        else {
          $(e.currentTarget).append(this.template_region_option(region.toJSON()));
        }
      }, this);
    },

    /**
     * Hides the region selector.
     */
    hideBlockRegionList: function(e) {
      $(e.currentTarget).html('<option>Move</option>');
    },

    /**
     * React to a new region being selected.
     */
    selectBlockRegionList: function(e) {
      // Get the BlockModel id (uuid).
      var id = $(e.currentTarget).closest('[data-block-action-id]').data('block-action-id');

      // Grab the value of this region.
      var region_name = $(e.currentTarget).children(':selected').data('region-option-name');

      // First, remove the Block from the current region.
      var block;
      var region_collection = this.model.get('regionCollection');
      region_collection.each(function (region) {
        var block_collection = region.get('blockCollection');
        if (block_collection.get(id)) {
          block = block_collection.get(id);
          block_collection.remove(block);
        }
      });

      // Next, add the Block to the new region.
      if (block) {
        var region = this.model.get('regionCollection').get(region_name);
        region.get('blockCollection').add(block);
      }

      // Hide the select list.
      this.hideBlockRegionList(e);

      // Re-render.
      this.render();

      // Highlight the block.
      this.$('[data-block-id="' + id + '"]').addClass('ipe-highlight');
    },

    /**
     * Changes the LayoutModel for this view.
     *
     * @param {Drupal.panels_ipe.LayoutModel} layout
     */
    changeLayout: function (layout) {
      // Stop listening to the current model.
      this.stopListening(this.model);
      // Initialize with the new model.
      this.initialize({'model': layout});
    },

    /**
     * Moves a block up or down in its RegionModel's BlockCollection.
     */
    moveBlock: function(e) {
      // Get the BlockModel id (uuid).
      var id = $(e.currentTarget).closest('[data-block-action-id]').data('block-action-id');

      // Get the direction the block is moving.
      var dir = $(e.currentTarget).data('action-id');

      // Grab the model for this region.
      var region_name = $(e.currentTarget).closest('[data-region-name]').data('region-name');
      var region = this.model.get('regionCollection').get(region_name);
      var block = region.get('blockCollection').get(id);

      // Shift the Block.
      region.get('blockCollection').shift(block, dir);

      // Re-render ourselves.
      this.render();

      // Highlight the block.
      this.$('[data-block-id="' + id + '"]').addClass('ipe-highlight');
    }

  });

}(jQuery, _, Backbone, Drupal));
