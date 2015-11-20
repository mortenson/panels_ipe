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
     * The name of the currently selected category.
     *
     * @type {string}
     */
    activeCategory: null,

    /**
     * @type {function}
     */
    template: _.template(
      '<div class="ipe-block-picker-top"></div><div class="ipe-block-picker-bottom"><div class="ipe-block-categories"></div></div>'
    ),

    /**
     * @type {function}
     */
    template_category: _.template(
      '<a class="ipe-block-category<% if (active) { %> active<% } %>" data-block-category="<%= name %>">' +
      '  <%= name %>' +
      '  <div class="ipe-block-category-count"><%= count %></div>' +
      '</a>'
    ),

    /**
     * @type {function}
     */
    template_plugin: _.template(
      '<div class="ipe-block-plugin" data-plugin-id="<%= plugin_id %>">' +
      '  <div class="ipe-block-plugin-info">' +
      '    <h5><%= label %></h5>' +
      '    <p>Provider: <strong><%= provider %></strong></p>' +
      '  </div>' +
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
     * @type {object}
     */
    events: {
      'click [data-block-category]': 'toggleCategory',
      'click [data-plugin-id] a': 'displayBlockForm'
    },

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
        this.$('.ipe-block-categories').append(this.template_category({
          'name': i,
          'count': categories_count[i],
          'active': this.activeCategory == i
        }));
      }

      // Check if a category is selected. If so, render the top-tray.
      if (this.activeCategory) {
        this.$('.ipe-block-picker-top').addClass('active');
        this.collection.each(function(block_plugin) {
          if (block_plugin.get('category') == this.activeCategory) {
            this.$('.ipe-block-picker-top').append(this.template_plugin(block_plugin.toJSON()));
          }
        }, this);
      }
    },

    /**
     * Reacts to a category being clicked.
     */
    toggleCategory: function(e) {
      var category = $(e.currentTarget).data('block-category');

      var animation = false;

      // No category is open.
      if (!this.activeCategory) {
        this.activeCategory = category;
        animation = 'slideDown';
      }
      // The same category is clicked twice.
      else if (this.activeCategory == category) {
        this.activeCategory = null;
        animation = 'slideUp';
      }
      // Another category is already open.
      else if (this.activeCategory) {
        this.activeCategory = category;
      }

      // Trigger a re-render, with animation if needed.
      if (animation == 'slideUp') {
        // Close the tab, then re-render.
        var self = this;
        this.$('.ipe-block-picker-top')[animation]('fast', function() { self.render(); });
      }
      else if (animation == 'slideDown') {
        // We need to render first as hypothetically nothing is open.
        this.render();
        this.$('.ipe-block-picker-top').hide();
        this.$('.ipe-block-picker-top')[animation]('fast');
      }
      else {
        this.render();
      }
    },

    /**
     * Displays a Block Configuration form when adding a Block Plugin.
     */
    displayBlockForm: function(e) {
      // Get the current plugin_id.
      var plugin_id = $(e.currentTarget).data('plugin-id');

      // Get the target plugin.
      var plugin = this.collection.get(plugin_id);

      // Fetch the full content of the plugin, which pulls in the configuration form.
      plugin.fetch();
    }

  });

}(jQuery, _, Backbone, Drupal));
