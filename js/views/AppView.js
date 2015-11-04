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
    template: _.template('<a class="enable-ipe">Toggle IPE</a>'),

    /**
     * @type {object}
     */
    events: {
      'click .enable-ipe': 'toggleIPE'
    },

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
    },

    /**
     * Renders the bottom tray for the IPE.
     */
    render: function() {
      this.$el.html(this.template(this.model.toJSON()));
    },

    /**
     * Toggles the IPE tray.
     */
    toggleIPE: function() {
      var state = this.model.get('state') == null ? 'active' : null;
      this.model.set({'state': state});
      // Change state of all of our regions.
      this.model.get('regionCollection').each(function(region){
        region.set('state', state);
      });
    }

  });

}(jQuery, _, Backbone, Drupal));
