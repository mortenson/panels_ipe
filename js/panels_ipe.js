/**
 * @file
 * Attaches behavior for the Panels IPE module.
 *
 */

(function ($, _, Backbone, Drupal) {

    'use strict';

    /**
     * Contains initial Backbone initialization for the IPE.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.panels_ipe = {
        attach: function (context, settings) {
            // Perform initial setup of our app.
            $('body').once('panels-ipe-init').each(Drupal.panels_ipe.init, [settings]);
        },
        detach: function (context, settings) {

        }
    };

    /**
     * @namespace
     */
    Drupal.panels_ipe = {};

    /**
     * Setups up our initial Collection and Views based on the current settings.
     */
    Drupal.panels_ipe.init = function(settings) {
        // Create our app.
        Drupal.panels_ipe.app = new Drupal.panels_ipe.AppModel();
        var app_view = new Drupal.panels_ipe.AppView({
            model: Drupal.panels_ipe.app,
            'el': '#panels-ipe-tray'
        });
        app_view.render();

        // Assemble the initial region and block collections.
        var region_collection = new Drupal.panels_ipe.RegionCollection();
        for (var i in settings.panels_ipe.regions) {
            var region = new Drupal.panels_ipe.RegionModel();
            region.set(settings.panels_ipe.regions[i]);

            var block_collection = new Drupal.panels_ipe.BlockCollection();
            for (var j in settings.panels_ipe.regions[i].blocks) {
                // Add a new block model.
                var block = new Drupal.panels_ipe.BlockModel();
                block.set(settings.panels_ipe.regions[i].blocks[j]);
                block_collection.add(block);

                // Attach the new block model to a view.
                var block_view = new Drupal.panels_ipe.BlockView({
                    'model': block,
                    'el': "[data-block-id='" + settings.panels_ipe.regions[i].blocks[j].uuid + "']"
                });
                block_view.render();
            }

            region.set({'blockCollection': block_collection});

            var region_view = new Drupal.panels_ipe.RegionView({
                'model': region,
                'el': "[data-region-name='" + settings.panels_ipe.regions[i].name + "']"
            });
            region_view.render();
            region_collection.add(region);
        }

        Drupal.panels_ipe.app.set({'regionCollection': region_collection});
    };

    /**
     * Returns the urlRoot for all callbacks
     */
    Drupal.panels_ipe.urlRoot = function(settings) {
        return '/admin/panels_ipe/page/' + settings.panels_ipe.page.id + '/variant/' + settings.panels_ipe.display_variant.id;
    };

}(jQuery, _, Backbone, Drupal));
