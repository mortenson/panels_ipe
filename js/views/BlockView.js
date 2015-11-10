/**
 * @file
 * The primary Backbone view for a Block.
 *
 * see Drupal.panels_ipe.BlockModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.BlockView = Backbone.View.extend(/** @lends Drupal.panels_ipe.BlockView# */{

    /**
     * @type {function}
     */
    template: _.template('<div class="panels-ipe-header"><h5>Block: <%= label %></h5></div>'),

    /**
     * @type {Drupal.panels_ipe.BlockModel}
     */
    model: null,

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.BlockModel} options.model
     *   The block state model.
     * @param {string} options.el
     *   An optional selector if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      if (options.el && !this.model.get('html')) {
        this.model.set({'html': this.$el.prop('outerHTML')});
      }
      this.listenTo(this.model, 'reset', this.render);
    },

    /**
     * Renders the wrapping elements and refreshes a block model.
     */
    render: function() {
      // Replace our current HTML.
      this.$el.replaceWith(this.model.get('html'));
      this.setElement(this.el);
      if (this.model.get('active')) {
        this.$el.prepend(this.template(this.model.toJSON()));
      }
      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
