/**
 * @file
 * Renders a list of existing Blocks for selection.
 *
 * see Drupal.panels_ipe.BlockPluginCollection
 *
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.BlockPicker = Backbone.View.extend(/** @lends Drupal.panels_ipe.BlockPicker# */{

    /**
     * @type {Drupal.panels_ipe.BlockPluginCollection}
     */
    collection: null,

    /**
     * @type {function}
     */
    template: _.template(
      '<div class="ipe-block-picker-top"></div><div class="ipe-block-picker-bottom"></div>'
    ),

    /**
     * @type {function}
     */
    template_category: _.template(
      '<a class="ipe-block-category" data-block-category="<%= name %>">' +
      '  <%= name %>' +
      '  <div class="ipe-block-category-count"><%= count %></div>' +
      '</a>'
    ),

    /**
     * @type {function}
     */
    template_plugin: _.template(
      '<div class="ipe-block-plugin" data-plugin-id="<%= plugin_id %>">' +
      '  <h5><%= label %> (<%= id %>)</h5>' +
      '  <a>Add</a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_loading: _.template(
      '<span class="ipe-icon ipe-icon-loading"></span>'
    ),

    /**
     * Renders the selection menu for picking Blocks.
     */
    render: function() {
      // Initialize our BlockPluginCollection if it doesn't already exist.
      if (!this.collection) {
        // Indicate an AJAX request.
        this.$el.html(this.template_loading());

        // Fetch a collection of block plugins from the server.
        this.collection = new Drupal.panels_ipe.BlockPluginCollection;
        var self = this;
        this.collection.fetch().done(function(){
          // We have a collection now, re-render ourselves.
          self.render();
        });
        return;
      }

      // Empty ourselves.
      this.$el.empty();
      this.$el.html(this.template());

      // Get a list of categories from the collection.
      var categories_count = {};
      this.collection.each(function(block_plugin) {
        var category = block_plugin.get('category');
        if (!categories_count[category]) {
          categories_count[category] = 0;
        }
        ++categories_count[category];
      });

      // Render each category.
      for (var i in categories_count) {
        this.$('.ipe-block-picker-bottom').append(this.template_category({'name': i, 'count': categories_count[i]}));
      }
    }

  });

}(jQuery, _, Backbone, Drupal));
