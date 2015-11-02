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
     * @type {string}
     */
    template: _.template('<div class="panels-ipe-block"><h5>Block: <%= label %></h5><%= html %></div>'),

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.BlockModel} options.model
     *   The block state model.
     * @param {string} options.uuid
     *   An optional UUID if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      if (options.uuid) {
        this.setElement("[data-block-id='" + options.uuid + "']");
        this.model.set({html: this.$el.html()});
      }
    },

    /**
     * Renders the wrapping elements and refreshes a block model.
     *
     *  @param {bool} nosync
     *   An optional flag to skip syncing from the server before render.
     */
    render: function(nosync){
      if (!nosync) {
        this.model.sync('read', this.model);
      }
      this.$el.html(this.template(this.model.toJSON()));
      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
