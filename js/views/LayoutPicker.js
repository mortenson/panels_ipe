/**
 * @file
 * Renders a collection of Layouts for selection.
 *
 * see Drupal.panels_ipe.LayoutCollection
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutPicker = Backbone.View.extend(/** @lends Drupal.panels_ipe.LayoutPicker# */{

    /**
     * @type {function}
     */
    template_layout: _.template('<li class="ipe-layout" data-layout-id="<%- id %>"><h5 class="ipe-layout-title"><a><%- label %></a></h5></li>'),

    /**
     * @type {function}
     */
    template_current: _.template('<p>Current Layout: </p><h5 class="ipe-layout-title"><%- label %></h5>'),

    /**
     * @type {function}
     */
    template_loading: _.template(
      '<span class="ipe-icon ipe-icon-loading"></span>'
    ),

    /**
     * @type {function}
     */
    template: _.template(
      '<div class="ipe-current-layout"></div><div class="ipe-all-layouts"><p>Available Layouts:</p><ul class="ipe-layouts"></ul></div>'
    ),

    /**
     * @type {Drupal.panels_ipe.LayoutCollection}
     */
    collection: null,

    /**
     * @type {object}
     */
    events: {
      'click .ipe-layout': 'selectLayout'
    },

    /**
     * Renders the selection menu for picking Layouts.
     */
    render: function () {
      // If we don't have layouts yet, pull some from the server.
      if (!this.collection) {
        // Indicate an AJAX request.
        this.$el.html(this.template_loading());

        // Fetch a list of layouts from the server.
        this.collection = new Drupal.panels_ipe.LayoutCollection();
        var self = this;
        this.collection.fetch().done(function () {
          // We have a collection now, re-render ourselves.
          self.render();
        });
      }
      // Render our LayoutCollection.
      else {
        this.$el.empty();

        // Setup the empty list.
        this.$el.html(this.template());

        // Append each layout option.
        this.collection.each(function (layout) {
          if (!layout.get('current')) {
            this.$('.ipe-layouts').append(this.template_layout(layout.toJSON()));
          }
          else {
            this.$('.ipe-current-layout').append(this.template_current(layout.toJSON()));
          }
        }, this);
      }
    },

    /**
     * Fires a global Backbone event that the App watches to switch layouts.
     *
     * @param {Object} e
     *   The event object.
     */
    selectLayout: function (e) {
      e.preventDefault();
      var id = $(e.currentTarget).data('layout-id');

      // Unset the current tab.
      this.collection.each(function (layout) {
        if (id === layout.id) {
          layout.set('current', true);
          // Indicate an AJAX request.
          this.$el.html(this.template_loading());

          // Only the AppView is aware of the rendered Layout.
          // @todo Investigate using non-global events.
          Drupal.panels_ipe.app.trigger('changeLayout', [layout]);
        }
        else {
          layout.set('current', false);
        }
      }, this);
    }

  });

}(jQuery, _, Backbone, Drupal));
