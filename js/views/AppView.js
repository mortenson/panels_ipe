/**
 * @file
 * The primary Backbone view for Panels IPE. For now this only controls the
 * bottom tray, but in the future could have a larger scope.
 *
 * see Drupal.panels_ipe.AppModel
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

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
        collection: this.model.get('tabCollection'),
        tabViews: options.tabContentViews
      });

      // Listen to important global events throughout the app.
      this.listenTo(this.model, 'changeLayout', this.changeLayout);
      this.listenTo(this.model, 'addBlockPlugin', this.addBlockPlugin);
      this.listenTo(this.model, 'configureBlock', this.configureBlock);

      // Listen to tabs that don't have associated BackboneViews.
      this.listenTo(this.model.get('editTab'), 'change:active', this.clickEditTab);
      this.listenTo(this.model.get('saveTab'), 'change:active', this.clickSaveTab);
      this.listenTo(this.model.get('cancelTab'), 'change:active', this.clickCancelTab);
    },

    /**
     * Appends the IPE tray to the bottom of the screen.
     *
     * @return {Drupal.panels_ipe.AppView}
     *   Returns this, for chaining.
     */
    render: function () {
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
    openIPE: function () {
      var active = this.model.get('active');
      if (active) {
        return;
      }

      // Set our active state correctly.
      this.model.set({active: true});

      // Set the layout's active state correctly.
      this.model.get('layout').set({active: true});

      this.$el.addClass('active');
    },

    /**
     * Deactivate all regions and blocks for editing.
     */
    closeIPE: function () {
      var active = this.model.get('active');
      if (!active) {
        return;
      }

      // Set our active state correctly.
      this.model.set({active: false});

      // Set the layout's active state correctly.
      this.model.get('layout').set({active: false});

      this.$el.removeClass('active');
    },

    /**
     * Event callback for when a new layout has been selected.
     *
     * @param {Array} args
     *   An array of event arguments.
     */
    changeLayout: function (args) {
      // Grab the layout from the argument list.
      var layout = args[0];

      // Sync the layout from Drupal.
      var self = this;
      layout.fetch().done(function () {
        // Grab all the blocks from the current layout.
        var regions = self.model.get('layout').get('regionCollection');
        var block_collection = new Drupal.panels_ipe.BlockCollection();
        regions.each(function (region) {
          block_collection.add(region.get('blockCollection').toJSON());
        });

        // Get the first region in the layout.
        // @todo Be smarter about re-adding blocks.
        var first_region = layout.get('regionCollection').at(0);

        // Append all blocks from previous layout.
        first_region.set({blockCollection: block_collection});

        // Change the default layout in our AppModel.
        self.model.set({layout: layout});

        // Change the LayoutView's layout.
        self.layoutView.changeLayout(layout);

        // Re-render the app.
        self.render();
      });
    },

    /**
     * Sets the IPE active state based on the "Edit" TabModel.
     */
    clickEditTab: function () {
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
    clickSaveTab: function () {
      if (this.model.get('saveTab').get('active')) {
        // Save the Layout and disable the tab.
        var self = this;
        self.model.get('saveTab').set({loading: true});
        this.model.get('layout').save().done(function () {
          self.model.get('saveTab').set({loading: false, active: false});
          self.tabsView.render();
          self.$el.removeClass('unsaved');
        });
      }
    },

    /**
     * Cancels our temporary changes and refreshes the page.
     */
    clickCancelTab: function () {
      var cancel_tab = this.model.get('cancelTab');
      if (cancel_tab.get('active') && !cancel_tab.get('loading')) {
        // Remove our changes and refresh the page.
        cancel_tab.set({loading: true});
        $.ajax(Drupal.panels_ipe.urlRoot(drupalSettings) + '/cancel')
          .done(function (data) {
            location.reload();
          });
      }
    },

    /**
     * Adds a new BlockPlugin to the screen.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The new BlockModel
     * @param {string} region
     *   The region the block should be placed in.
     */
    addBlockPlugin: function (block, region) {
      this.layoutView.addBlock(block, region);

      // Mark all tabs as inactive and close the view.
      this.tabsView.collection.each(function (tab) {
        tab.set('active', false);
      });

      this.tabsView.closeTabContent();
    },

    /**
     * Opens the Manage Content tray when configuring an existing Block.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The Block that needs to have its form opened.
     */
    configureBlock: function (block) {
      this.tabsView.tabViews['manage_content'].activeCategory = 'On Screen';
      this.tabsView.tabViews['manage_content'].autoClick = '[data-existing-block-id=' + block.get('uuid') + ']';
      this.tabsView.switchTab('manage_content');
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
