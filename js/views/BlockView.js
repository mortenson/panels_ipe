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
     */
    render: function(nosync){
      if (!nosync) {
        this.model.sync('read', this.model);
      }
      this.$el.html('<div>Wrapping test' + this.model.get('html') + '</div>');
      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
