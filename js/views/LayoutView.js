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
      'change [data-action-id="move"] > select': 'selectBlockRegionList'
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
     * @param {string} options.el
     *   An optional selector if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      // Initialiaze our html, this never changes.
      if (this.model.get('html')) {
        this.$el.html(this.model.get('html'));
      }
      // Initialize our Block Views, if HTML is already provided to us.
      if (this.el) {
        this.initBlockViews();
      }
      this.listenTo(this.model, 'change:active', this.changeState);
    },

    /**
     * Re-renders our blocks, we have no HTML to be re-rendered.
     */
    render: function() {
      // Re-render all of our blocks (regions never change).
      for (var i in this.blockViews) {
        this.blockViews[i].render();
      }
      return this;
    },

    changeState: function(model, value, options) {
      // Prepend all regions with the appropriate action header.
      this.model.get('regionCollection').each(function (region) {
        if (value) {
          var selector = '[data-region-name="' + region.get('name') + '"]';
          this.$(selector).prepend(this.template_region_actions(region.toJSON()));
        }
        else {
          this.$('.ipe-actions').remove();
        }
        region.get('blockCollection').each(function (block) {
          block.set({'active': value});
        }, this);
      }, this);
      this.render();
    },

    /**
     * Initializes our blockViews property if HTML is provided to us.
     *
     * If anything in our BlockCollection isn't already on screen, this
     * function will also fetch new HTML from the server and render that.
     */
    initBlockViews: function() {
      this.model.get('regionCollection').each(function (region) {
        region.get('blockCollection').each(function (block) {

          // If the target element doesn't exist, append an empty one.
          // The "empty_elem" variable will be later used to trigger a
          // BlockModel.fetch() call, which will re-render and remove our
          // placeholder.
          if (this.$('[data-block-id="' + block.get('uuid') + '"]').length == 0) {
            var empty_elem = $('<div data-block-id="' + block.get('uuid') + '">');
            this.$('[data-region-name="' + region.get('name') + '"]').append(empty_elem);
          }

          // Attach a View to this empty element.
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
          region_collection.remove(block);
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
    }

  });

}(jQuery, _, Backbone, Drupal));
