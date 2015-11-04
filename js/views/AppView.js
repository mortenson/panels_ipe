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
     * @type {object}
     */
    events: {
      'click .ipe-tab > a': 'openIPE',
      'click .ipe-tab > a[data-id="close"]': 'closeIPE'
    },

    /**
     * @type {Drupal.panels_ipe.TabsView} tabsView
     */
    tabsView: null,

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.AppModel} options.model
     *   The application state model.
     *
     */
    initialize: function (options) {
      this.model = options.model;
      // Create a TabsView instance.
      this.tabsView = new Drupal.panels_ipe.TabsView({'collection': this.model.get('tabCollection')});
    },

    /**
     * Appends the IPE tray to the bottom of the screen.
     */
    render: function() {
      // Empty our list.
      this.$el.empty();
      // Add our tab collection to the App.
      this.$el.append(this.tabsView.render().$el);
      // We need to be appended at the highest level.
      $('body').append(this.$el);
    },

    /**
     * Opens the IPE tray.
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

      this.$el.addClass('active');
    },

    /**
     * Closes the IPE tray.
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

      this.$el.removeClass('active');
    }

  });

}(jQuery, _, Backbone, Drupal));
