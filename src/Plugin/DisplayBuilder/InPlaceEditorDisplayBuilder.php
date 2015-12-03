<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Plugin\DisplayBuilder\InPlaceEditorDisplayBuilder.
 */

namespace Drupal\panels_ipe\Plugin\DisplayBuilder;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Drupal\panels\Plugin\DisplayBuilder\StandardDisplayBuilder;

/**
 * The In-place editor display builder for viewing and editing a 
 * PanelsDisplayVariant in the same place.
 *
 * @DisplayBuilder(
 *   id = "in_place_editor",
 *   label = @Translation("In-place editor")
 * )
 */
class InPlaceEditorDisplayBuilder extends StandardDisplayBuilder {

  /**
   * Compiles settings needed for the IPE to function.
   *
   * @param array $regions
   *   The render array representing regions.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutInterface $layout
   *   The current layout.
   *
   * @return array
   *   An associative array representing the contents of drupalSettings.
   */
  protected function getDrupalSettings(array $regions, LayoutInterface $layout) {
    // Explicitly support Page Manger, as we need to have a reference for where
    // to save the display.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    $variant = \Drupal::request()->attributes->get('page_manager_page_variant');

    $settings = [
      'regions' => [],
    ];

    // Add current block IDs to settings sorted by region.
    foreach ($regions as $region => $blocks) {
      $settings['regions'][$region]  = [
        'name' => $region,
        'label' => '',
        'blocks' => []
      ];

      if (!$blocks) {
        continue;
      }

      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_uuid => $block) {
        $configuration = $block->getConfiguration();
        $setting = [
          'uuid' => $block_uuid,
          'label' => $block->label(),
          'id' => $block->getPluginId()
        ];
        $settings['regions'][$region]['blocks'][$block_uuid] = NestedArray::mergeDeep($configuration, $setting);
      }
    }

    // Add the layout information.
    $layout_definition = $layout->getPluginDefinition();
    $settings['layout'] = [
      'id' => $layout->getPluginId(),
      'label' => $layout_definition['label'],
      'original' => true
    ];

    // Add the display variant's config.
    if ($variant) {
      $settings['display_variant'] = [
        'label' => $variant->label(),
        'id' => $variant->id(),
        'uuid' => $variant->uuid(),
      ];
    }

    return ['panels_ipe' => $settings];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions, array $contexts, LayoutInterface $layout = NULL) {
    $build = parent::build($regions, $contexts, $layout);
    // Attach the Panels In-place editor library based on permissions.
    if ($this->account->hasPermission('access panels in-place editing')) {
      foreach ($regions as $region => $blocks) {
        // Wrap each region with a unique class and data attribute.
        $region_name = Html::getClass("block-region-$region");
        $build[$region]['#prefix'] = '<div class="' . $region_name . '" data-region-name="' . $region . '">';
        $build[$region]['#suffix'] = '</div>';

        if ($blocks) {
          foreach ($blocks as $block_id => $block) {
            $build[$region][$block_id]['#attributes']['data-block-id'] = $block_id;
          }
        }
      }

      // Attach the required settings and IPE.
      $build['#attached'] = [
        'library' => ['panels_ipe/panels_ipe'],
        'drupalSettings' => $this->getDrupalSettings($regions, $layout)
      ];

      // Add our custom elements to the build.
      $build['#prefix'] = '<div id="panels-ipe-content">';
      $build['#suffix'] = '</div><div id="panels-ipe-tray"></div>';
    }
    return $build;
  }

}
