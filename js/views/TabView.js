/**
 * @file
 * The primary Backbone view for a tab.
 *
 * see Drupal.panels_ipe.TabModel
 */

(function ($, _, Backbone, Drupal) {

  Drupal.panels_ipe.TabsView = Backbone.View.extend(/** @lends Drupal.panels_ipe.TabsView# */{

    /**
     * @type {function}
     */
    template_tab: _.template('<li class="ipe-tab<% if (active) { %> active <% } %>" data-tab-id="<%= id %>"><a><%= title %></a></li>'),

    /**
     * @type {function}
     */
    template_content: _.template('<div class="ipe-tab-content<% if (active) { %> active <% } %>" data-tab-content-id="<%= id %>"></div>'),

    /**
     * @type {object}
     */
    events: {
      'click .ipe-tab > a': 'switchTab'
    },

    /**
     * @type {Drupal.panels_ipe.TabCollection}
     */
    tabs: null,

    /**
     * @type {Object}
     *
     * An object mapping tab IDs to Backbone views.
     */
    tabViews: {},

    /**
     * Renders our tab collection.
     */
    render: function() {
      // Empty our list.
      this.$el.empty();

      // Setup the initial wrapping elements.
      this.$el.append('<ul class="ipe-tabs"></ul>');
      this.$el.append('<div class="ipe-tabs-content"></div>');

      // Append each of our tabs and their tab content view.
      this.collection.each(function(tab) {
        // Append the tab.
        var id = tab.get('id');
        this.$('.ipe-tabs').append(this.template_tab(tab.toJSON()));

        // Render the tab content.
        this.$('.ipe-tabs-content').append(this.template_content(tab.toJSON()));
        // Check to see if this tab has content.
        if (this.tabViews[id]) {
          this.tabViews[id].setElement('[data-tab-content-id="' + id + '"]').render();
        }
      }, this);
      return this;
    },

    /**
     * Switches the current tab.
     */
    switchTab: function(e) {
      // Disable all existing tabs.
      this.collection.each(function(tab) {
        tab.set('active', false);
      });

      // Set the active tab correctly.
      e.preventDefault();
      var id = $(e.currentTarget).parent().data('tab-id');
      if (id != 'close') {
        var tab = this.collection.get(id);
        tab.set('active', true);
      }

      // Trigger a re-render.
      this.render();
    }

  });

}(jQuery, _, Backbone, Drupal));
