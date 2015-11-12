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
     * @type {Drupal.panels_ipe.LayoutView}
     */
    layoutView: null,

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
      this.listenTo(this.model.get('editTab'), "change:active", this.clickEditTab);
      this.listenTo(this.model.get('saveTab'), "change:active", this.clickSaveTab);
    },

    /**
     * Appends the IPE tray to the bottom of the screen.
     */
    render: function() {
      // Empty our list.
      this.$el.html(this.template(this.model.toJSON()));
      // Add our tab collection to the App.
      this.tabsView.setElement(this.$('.ipe-tab-wrapper')).render();
      // Re-render our layout.
      if (this.layoutView) {
        this.layoutView.render();
      }
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

      // Set the layout's active state correctly.
      this.model.get('layout').set({'active': true});

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

      // Set the layout's active state correctly.
      this.model.get('layout').set({'active': false});

      this.$el.removeClass('active');
    },

    /**
     * Event callback for when a new layout has been selected.
     */
    changeLayout: function (args) {
      // Grab the layout from the argument list.
      var layout = args[0];

      // Sync the layout from Drupal.
      var self = this;
      layout.fetch().done(function() {
        // Grab all the blocks from the current layout.
        var regions = self.model.get('layout').get('regionCollection');
        var block_collection = new Drupal.panels_ipe.BlockCollection();
        regions.each(function(region) {
          block_collection.add(region.get('blockCollection').toJSON());
        });

        // Get the first region in the layout.
        // @todo Be smarter about re-adding blocks.
        var first_region = layout.get('regionCollection').at(0);

        // Append all blocks from previous layout.
        first_region.set({'blockCollection': block_collection});

        // Change the default layout in our AppModel.
        self.model.set({'layout': layout});

        // Change the LayoutView's layout.
        self.layoutView.changeLayout(layout);

        // Re-render the app.
        self.render();
      });
    },

    /**
     * Sets the IPE active state based on the "Edit" TabModel.
     */
    clickEditTab: function(){
      var active = this.model.get('editTab').get('active');
      if (active) {
        this.openIPE();
      }
      else {
        this.closeIPE();
      }
    },

    /**
     * Saves our layout to the server.
     */
    clickSaveTab: function(){
      if (this.model.get('saveTab').get('active')) {
        this.model.get('layout').save();
        this.model.get('saveTab').set({'active': false});
      }
    }

  });

}(jQuery, _, Backbone, Drupal));
