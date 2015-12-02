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
      '<div class="ipe-block-plugin">' +
      '  <div class="ipe-block-plugin-info">' +
      '    <h5><%= label %></h5>' +
      '    <p>Provider: <strong><%= provider %></strong></p>' +
      '  </div>' +
      '  <a data-plugin-id="<%= plugin_id %>">Add</a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_existing: _.template(
      '<div class="ipe-block-plugin">' +
      '  <div class="ipe-block-plugin-info">' +
      '    <h5><%= block.label %></h5>' +
      '    <p>Provider: <strong><%= block.provider %></strong></p>' +
      '  </div>' +
      '  <a data-existing-region-name="<%= region.name %>" data-existing-block-id="<%= block.uuid %>">Configure</a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_plugin_form: _.template(
      '<h4>Configure <strong><%= label %></strong> block</h4>' +
      '<div class="ipe-block-plugin-form"><div class="ipe-icon ipe-icon-loading"></div></div>'
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
      'click .ipe-block-plugin [data-plugin-id]': 'displayBlockPluginForm',
      'click .ipe-block-plugin [data-existing-block-id]': 'displayBlockPluginForm'
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

      // Render existing blocks as a unique category.
      var existing_count = 0;
      Drupal.panels_ipe.app.get('layout').get('regionCollection').each(function(region) {
        region.get('blockCollection').each(function(block) {
          ++existing_count;
        })
      });
      this.$('.ipe-block-categories').append(this.template_category({
        'name': 'On Screen',
        'count': existing_count,
        'active': this.activeCategory == 'panels_ipe_blocks'
      }));

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
        var $top = this.$('.ipe-block-picker-top');
        $top.addClass('active');
        if (this.activeCategory == 'On Screen') {
          Drupal.panels_ipe.app.get('layout').get('regionCollection').each(function(region) {
            region.get('blockCollection').each(function(block) {
              $top.append(this.template_existing({
                'block': block.toJSON(),
                'region': region.toJSON()
              }));
            }, this);
          }, this);
        }
        else {
          this.collection.each(function(block_plugin) {
            if (block_plugin.get('category') == this.activeCategory) {
              $top.append(this.template_plugin(block_plugin.toJSON()));
            }
          }, this);
        }
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
    displayBlockPluginForm: function(e) {
      var self = this;
      var ajax_data = {
        'js': true
      };

      // Get the current plugin_id.
      var plugin_id = $(e.currentTarget).data('plugin-id');
      var plugin;
      if (plugin_id) {
        plugin = this.collection.get(plugin_id);
      }
      // This must be an existing block.
      else {
        var block_id = $(e.currentTarget).data('existing-block-id');
        var region_name = $(e.currentTarget).data('existing-region-name');
        plugin = Drupal.panels_ipe.app.get('layout').get('regionCollection')
          .get(region_name).get('blockCollection').get(block_id);
        plugin_id = plugin.get('id');
        ajax_data.block = plugin.toJSON();
      }

      // Indicate an AJAX request.
      this.$('.ipe-block-picker-top').fadeOut('fast', function() {
        self.$('.ipe-block-picker-top').html(self.template_plugin_form(plugin.toJSON()));
        self.$('.ipe-block-picker-top').fadeIn('fast');
      });

      // Get the dynamic URL of our Block Plugin form.
      var layout_id = Drupal.panels_ipe.app.get('layout').get('id');
      var url = Drupal.panels_ipe.urlRoot(drupalSettings) + '/layout/' + layout_id + '/block_plugins/' + plugin_id + '/form';

      // Setup the Drupal.Ajax instance.
      var ajax = Drupal.ajax({
        'url': url,
        'submit': ajax_data
      });

      // Remove our throbber on load.
      ajax.options.complete = function() {
        self.$('.ipe-block-picker-top .ipe-icon-loading').remove();
        self.$('#panels-ipe-block-plugin-form-wrapper').hide().fadeIn();
      };

      // Make the Drupal AJAX request.
      ajax.execute();
    }

  });

}(jQuery, _, Backbone, Drupal));
