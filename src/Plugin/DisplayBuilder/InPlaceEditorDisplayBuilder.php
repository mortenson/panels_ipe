<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Plugin\DisplayBuilder\InPlaceEditorDisplayBuilder.
 */

namespace Drupal\panels_ipe\Plugin\DisplayBuilder;

use Drupal\Component\Utility\Html;
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
   *
   * @return array
   *   An associative array representing the contents of drupalSettings.
   */
  protected function getDrupalSettings(array $regions, array $contexts, LayoutInterface $layout = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = \Drupal::request()->attributes->get('_entity');

    $settings = [
      'regions' => [],
    ];

    // Add current block IDs to settings sorted by region.
    foreach ($regions as $region => $blocks) {
      $settings['regions'][$region]  = [
        'id' => $region,
        'blocks' => []
      ];

      if (!$blocks) {
        continue;
      }

      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_uuid => $block) {
        $settings['regions'][$region]['blocks'][$block_uuid] = [
          'uuid' => $block_uuid,
          'label' => $block->label(),
          'id' => $block->getPluginId()
        ];
      }
    }

    // Add the layout information.
    if ($layout) {
      $layout_definition = $layout->getPluginDefinition();
      $settings['layout'] = [
        'id' => $layout->getPluginId(),
        'label' => $layout_definition['label']
      ];
    }

    // Explicitly support Page Manger, as we need to have a reference for where
    // to save the display.
    if ($entity->getEntityTypeId() == 'page') {
      /** @var \Drupal\page_manager\Entity\Page $entity */
      $variant = $entity->getExecutable()->selectDisplayVariant();
      $configuration = $variant->getConfiguration();
      $settings['display_variant'] = [
        'id' => $configuration['id'],
        'label' => $configuration['label'],
        'uuid' => $configuration['uuid'],
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

        // Add the UUID of each block to the data-block-id attribute.
        $build[$region]['#attributes']['data-region-name'] = $region;
        if ($blocks) {
          foreach ($blocks as $block_id => $block) {
            $build[$region][$block_id]['#attributes']['data-block-id'] = $block_id;
          }
        }

        // Attach the required settings and IPE.
        $build['#attached'] = [
          'library' => ['panels_ipe/panels_ipe'],
          'drupalSettings' => $this->getDrupalSettings($regions, $contexts, $layout)
        ];
      }
    }
    return $build;
  }

}
