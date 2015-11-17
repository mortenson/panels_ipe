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
    template_actions: _.template(
      '<div class="ipe-actions" data-block-action-id="<%= uuid %>">' +
      '  <h5>Block: <%= label %></h5>' +
      '  <ul class="ipe-action-list">' +
      '    <li data-action-id="up">' +
      '      <a><span class="ipe-icon ipe-icon-up"></span></a>' +
      '    </li>' +
      '    <li data-action-id="down">' +
      '      <a><span class="ipe-icon ipe-icon-down"></span></a>' +
      '    </li>' +
      '    <li data-action-id="move">' +
      '      <select><option>Move</option></select>' +
      '    </li>' +
      '  </ul>' +
      '</div>'
    ),

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
      // An element already exists and our HTML properly isn't set.
      // This only occurs on initial page load for performance reasons.
      if (options.el && !this.model.get('html')) {
        this.model.set({'html': this.$el.prop('outerHTML')});
      }
      this.listenTo(this.model, 'reset', this.render);
      this.listenTo(this.model, 'change:active', this.render);
    },

    /**
     * Renders the wrapping elements and refreshes a block model.
     */
    render: function() {
      // Replace our current HTML.
      this.$el.replaceWith(this.model.get('html'));
      this.setElement("[data-block-id='" + this.model.get('uuid') + "']");
      if (this.model.get('active')) {
        this.$el.prepend(this.template_actions(this.model.toJSON()));
      }
      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
