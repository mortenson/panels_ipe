/**
 * @file
 * The primary Backbone view for a tab collection.
 *
 * see Drupal.panels_ipe.TabCollection
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  Drupal.panels_ipe.TabsView = Backbone.View.extend(/** @lends Drupal.panels_ipe.TabsView# */{

    /**
     * @type {function}
     */
    template_tab: _.template(
      '<li class="ipe-tab<% if (active) { %> active<% } %>" data-tab-id="<%= id %>">' +
      '  <a title="<%= title %>"><span class="ipe-icon ipe-icon-<% if (loading) { %>loading<% } else { print(id) } %>"></span><%= title %></a>' +
      '</li>'
    ),

    /**
     * @type {function}
     */
    template_content: _.template('<div class="ipe-tab-content<% if (active) { %> active<% } %>" data-tab-content-id="<%= id %>"></div>'),

    /**
     * @type {object}
     */
    events: {
      'click .ipe-tab > a': 'switchTab'
    },

    /**
     * @type {Drupal.panels_ipe.TabCollection}
     */
    collection: null,

    /**
     * @type {Object}
     *
     * An object mapping tab IDs to Backbone views.
     */
    tabViews: {},

    /**
     * @constructs
     *
     * @augments Backbone.TabsView
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {object} options.tabViews
     *   An object mapping tab IDs to Backbone views.
     */
    initialize: function (options) {
      this.tabViews = options.tabViews;
    },

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
        if (tab.get('active') && this.tabViews[id]) {
          this.tabViews[id].setElement('[data-tab-content-id="' + id + '"]').render();
        }
      }, this);
      return this;
    },

    /**
     * Switches the current tab.
     */
    switchTab: function(e) {
      e.preventDefault();
      var id = $(e.currentTarget).parent().data('tab-id');

      // Disable all existing tabs.
      this.collection.each(function(tab) {
        // If the tab is loading, do nothing.
        if (tab.get('loading')) {
          return;
        }
        // If the user is clicking the same tab twice, close it.
        if (tab.get('id') == id && tab.get('active') == true) {
          tab.set('active', false);
        }
        // If this is the first click, open the tab.
        else if (tab.get('id') == id) {
          tab.set('active', true);
        }
        // The tab wasn't clicked, make sure it's closed.
        else {
          tab.set('active', false);
        }
      });

      // Trigger a re-render.
      this.render();
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
