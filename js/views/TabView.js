/**
 * @file
 * The primary Backbone view for a tab.
 *
 * see Drupal.panels_ipe.TabModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.TabView = Backbone.View.extend(/** @lends Drupal.panels_ipe.TabView# */{

    /**
     * @type {string}
     */
    tagName: 'li',

    /**
     * @type {string}
     */
    className: 'ipe-tab',

    /**
     * @type {Backbone.View}
     *   Some Backbone view that contains the tab content.
     */
    contentView: null,

    /**
     * @type {function}
     */
    template: _.template('<a data-id="<%= id %>"><%= title %></a><div class="ipe-tab-content"><%= title %></div>'),

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.TabModel} options.model
     *   The tab model.
     */
    initialize: function (options) {
      this.model = options.model;
      this.listenTo(this.model, 'change:active', this.toggleActive);
    },

    /**
     * Renders this tab.
     */
    render: function() {
      // Render our tab structure.
      this.$el.html(this.template(this.model.toJSON()));

      // Render the tab content.
      if (this.contentView) {
        this.$('.ipe-tab-content').html(this.contentView.render().$el);
      }

      // Add a class for styling purposes.
      this.$el.addClass('ipe-tab-' + this.model.get('id'));

      return this;
    },

    /**
     * Toggles the active state of this tab.
     */
    toggleActive: function() {
      // Set the active state of the tab.
      if (this.model.get('active')) {
        this.$el.addClass('active');
      }
      else {
        this.$el.removeClass('active');
      }
    }

  });

  Drupal.panels_ipe.TabsView = Backbone.View.extend(/** @lends Drupal.panels_ipe.TabsView# */{

    /**
     * @type {object}
     */
    events: {
      'click .ipe-tab > a': 'switchTab'
    },

    /**
     * @type {string}
     */
    tagName: 'ul',

    /**
     * @type {string}
     */
    className: 'ipe-tabs',

    /**
     * @type {Array}
     *
     * An array of Drupal.panels_ipe.TabsView instances.
     */
    tabs: [],

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.TabCollection} options.collection
     *   The tab collection.
     */
    initialize: function (options) {
      this.collection = options.collection;
      // Create sub-views for each tab.
      this.collection.each(function(tab) {
        this.tabs.push(new Drupal.panels_ipe.TabView({'model': tab}));
      },this);
    },

    /**
     * Renders our tab collection.
     */
    render: function() {
      // Empty our list.
      this.$el.empty();
      // Append each of our tabs.
      for (var i in this.tabs) {
        this.$el.append(this.tabs[i].render().$el);
      }
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
      var id = $(e.currentTarget).data('id');
      if (id != 'close') {
        var tab = this.collection.get(id);
        tab.set('active', true);
      }
    }

  });

}(jQuery, _, Backbone, Drupal));
