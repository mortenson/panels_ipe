/**
 * @file
 * Renders a collection of Layouts for selection in an IPE tab.
 *
 * see Drupal.panels_ipe.LayoutCollection
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutTabView = Backbone.View.extend(/** @lends Drupal.panels_ipe.LayoutTabView# */{

    /**
     * @type {function}
     */
    template: _.template('<li class="ipe-layout"><a><%= label %></a></li>'),

    /**
     * @type {function}
     */
    template_current: _.template('<h3>Current Layout: </h3><div class="ipe-current-layout"><%= label %></div>'),

    /**
     * @type {Drupal.panels_ipe.LayoutCollection}
     */
    layouts: null,

    /**
     * Renders the selection menu for picking Layouts.
     */
    render: function() {
      // If we don't have layouts yet, pull some from the server.
      if (!this.layouts) {
        this.layouts = new Drupal.panels_ipe.LayoutCollection;
        var self = this;
        this.layouts.fetch().done(function(){
          self.render();
        });
      }
      // Render our LayoutCollection.
      else {
        this.$el.empty();

        // Setup the empty list.
        this.$el.append('<ul class="ipe-layouts"></ul>');

        // Append each layout option.
        this.layouts.each(function(layout) {
          if (layout.get('current')) {
            this.$('.ipe-layouts').append(this.template_current(layout.toJSON()));
          }
          else {
            this.$('.ipe-layouts').append(this.template(layout.toJSON()));
          }
        }, this);
      }
    }

  });

}(jQuery, _, Backbone, Drupal));
