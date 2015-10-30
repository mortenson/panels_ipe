/**
 * @file
 * Attaches behavior for the Panels IPE module.
 *
 */

(function ($, _, Backbone, Drupal, drupalSettings, JSON, storage) {

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
        // Assemble the region and block collections.
        var region_collection = new Drupal.panels_ipe.RegionCollection();
        for (var i in settings.panels_ipe.regions) {
            var block_collection = new Drupal.panels_ipe.BlockCollection();
            for (var j in settings.panels_ipe.regions[i]) {
                var block = new Drupal.panels_ipe.BlockModel();
                block.set(settings.panels_ipe.regions[i][j]);
                block_collection.add(block);
            }

            var region = new Drupal.panels_ipe.RegionModel();
            region.set(settings.panels_ipe.regions[i]);
            region_collection.add(region);
        }

        // Create our app.
        Drupal.panels_ipe.app = new Drupal.panels_ipe.AppView({
            model: new Drupal.quickedit.AppModel(),
            regionCollection: region_collection
        });
    };

})(jQuery, _, Backbone, Drupal, drupalSettings, window.JSON, window.sessionStorage);
