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

      // If this setting is present, it means we need to add/update a new
      // BlockModel somewhere. Inform the App that this has occurred.
      if (settings['panels_ipe']['updated_block']) {
        var data = settings['panels_ipe']['updated_block'];
        // Create a BlockModel.
        var block = new Drupal.panels_ipe.BlockModel(data);
        // Trigger the event.
        Drupal.panels_ipe.app.trigger('addBlockPlugin', block, data.region);
        // Remove the setting.
        delete settings['panels_ipe']['updated_block'];
      }

      // If this setting is present, it means we need to toggle the preview.
      // We need to do this with drupalSettings as the animation won't work
      // if triggered by a form submit. It must occur after the form is rendered.
      if (context.className == 'panels-ipe-block-plugin-form flip-container'
        && settings['panels_ipe']['toggle_preview']) {
        $('.ipe-block-plugin-form').toggleClass('flipped');
        delete settings['panels_ipe']['toggle_preview'];
      }
    }
  };

  /**
   * @namespace
   */
  Drupal.panels_ipe = {};

  /**
   * Setups up our initial Collection and Views based on the current settings.
   *
   * @param {Object} settings
   *   The contextual drupalSettings.
   */
  Drupal.panels_ipe.init = function (settings) {
    // Set up our initial tabs.
    var tab_collection = new Drupal.panels_ipe.TabCollection();
    tab_collection.add({title: 'Change Layout', id: 'change_layout'});
    tab_collection.add({title: 'Manage Content', id: 'manage_content'});

    // The edit/save/cancel tabs are special, and are tracked by our app.
    var edit_tab = new Drupal.panels_ipe.TabModel({title: 'Edit', id: 'edit'});
    var save_tab = new Drupal.panels_ipe.TabModel({title: 'Save', id: 'save'});
    var cancel_tab = new Drupal.panels_ipe.TabModel({title: 'Cancel', id: 'cancel'});
    tab_collection.add(edit_tab);
    tab_collection.add(save_tab);
    tab_collection.add(cancel_tab);

    // Create a global(ish) AppModel.
    Drupal.panels_ipe.app = new Drupal.panels_ipe.AppModel({
      tabCollection: tab_collection,
      editTab: edit_tab,
      saveTab: save_tab,
      cancelTab: cancel_tab
    });

    // Set up our initial tab views.
    var tab_views = {
      change_layout: new Drupal.panels_ipe.LayoutPicker(),
      manage_content: new Drupal.panels_ipe.BlockPicker()
    };

    // Create an AppView instance.
    var app_view = new Drupal.panels_ipe.AppView({
      model: Drupal.panels_ipe.app,
      el: '#panels-ipe-tray',
      tabContentViews: tab_views
    });
    $('body').append(app_view.render().$el);

    // Assemble the initial region and block collections.
    // This logic is a little messy, as traditionally we would never initialize
    // Backbone with existing HTML content.
    var region_collection = new Drupal.panels_ipe.RegionCollection();
    for (var i in settings.panels_ipe.regions) {
      if (settings.panels_ipe.regions.hasOwnProperty(i)) {
        var region = new Drupal.panels_ipe.RegionModel();
        region.set(settings.panels_ipe.regions[i]);

        var block_collection = new Drupal.panels_ipe.BlockCollection();
        for (var j in settings.panels_ipe.regions[i].blocks) {
          if (settings.panels_ipe.regions[i].blocks.hasOwnProperty(j)) {
            // Add a new block model.
            var block = new Drupal.panels_ipe.BlockModel();
            block.set(settings.panels_ipe.regions[i].blocks[j]);
            block_collection.add(block);
          }
        }

        region.set({blockCollection: block_collection});

        region_collection.add(region);
      }
    }

    // Create the Layout model/view.
    var layout = new Drupal.panels_ipe.LayoutModel(settings.panels_ipe.layout);
    layout.set({regionCollection: region_collection});
    var layout_view = new Drupal.panels_ipe.LayoutView({
      model: layout,
      el: '#panels-ipe-content'
    });
    layout_view.render();

    Drupal.panels_ipe.app.set({layout: layout});
    app_view.layoutView = layout_view;
  };

  /**
   * Returns the urlRoot for all callbacks
   *
   * @param {Object} settings
   *   The contextual drupalSettings.
   *
   * @return {string}
   *   A base path for most other URL callbacks in this App.
   */
  Drupal.panels_ipe.urlRoot = function (settings) {
    return settings.path.baseUrl + 'admin/panels_ipe/variant/' + settings.panels_ipe.display_variant.id;
  };

}(jQuery, _, Backbone, Drupal));
