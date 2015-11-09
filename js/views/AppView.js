/**
 * @file
 * The primary Backbone view for Panels IPE. For now this only controls the
 * bottom tray, but in the future could have a larger scope.
 *
 * see Drupal.panels_ipe.AppModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.AppView = Backbone.View.extend(/** @lends Drupal.panels_ipe.AppView# */{

    /**
     * @type {function}
     */
    template: _.template('<div class="ipe-tab-wrapper"></div>'),

    /**
     * @type {Drupal.panels_ipe.TabsView}
     */
    tabsView: null,

    /**
     * @type {Drupal.panels_ipe.AppModel}
     */
    model: null,

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.AppModel} options.model
     *   The application state model.
     * @param {Object} options.tabContentViews
     *   An object mapping TabModel ids to arbitrary Backbone views.
     */
    initialize: function (options) {
      this.model = options.model;
      // Create a TabsView instance.
      this.tabsView = new Drupal.panels_ipe.TabsView({
        'collection': this.model.get('tabCollection'),
        'tabViews': options.tabContentViews
      });
      // Listen to important events throughout the app.
      this.listenTo(this.model, "changeLayout", this.changeLayout);
    },

    /**
     * Appends the IPE tray to the bottom of the screen.
     */
    render: function() {
      // Empty our list.
      this.$el.html(this.template(this.model.toJSON()));
      // Add our tab collection to the App.
      this.tabsView.setElement(this.$('.ipe-tab-wrapper')).render();
      return this;
    },

    /**
     * Actives all regions and blocks for editing.
     */
    openIPE: function() {
      var active = this.model.get('active');
      if (active) {
        return;
      }

      // Set our active state correctly.
      this.model.set({'active': true});

      // Change state of all of our regions.
      this.model.get('regionCollection').each(function(region){
        region.set('state', 'active');
      });

      // @todo Replace with a re-render.
      this.$el.addClass('active');
    },

    /**
     * Deactivate all regions and blocks for editing.
     */
    closeIPE: function() {
      var active = this.model.get('active');
      if (!active) {
        return;
      }

      // Set our active state correctly.
      this.model.set({'active': false});

      // Change state of all of our regions.
      this.model.get('regionCollection').each(function(region){
        region.set('state', 'inactive');
      });

      // @todo Replace with a re-render.
      this.$el.removeClass('active');
    },

    /**
     * Event callback for when a new layout has been selected.
     */
    changeLayout: function (args) {
      // Grab the layout from the argument list.
      var layout = args[0];

      // Sync the layout from Drupal. This generates empty HTML we're going to insert.
      layout.fetch().done(function(){
        // Replace the panel display with the empty layout.
        $('.panel-display').replaceWith(layout.get('html'));
      });
    }

  });

}(jQuery, _, Backbone, Drupal));
