<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Plugin\DisplayBuilder\InPlaceEditorDisplayBuilder.
 */

namespace Drupal\panels_ipe\Plugin\DisplayBuilder;

use Drupal\panels\Plugin\DisplayBuilder\StandardDisplayBuilder;
use Drupal\Component\Utility\Html;

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
   * Build render arrays for each of the regions.
   *
   * @param array $regions
   *   The render array representing regions.
   * @param array $contexts
   *   The array of context objects.
   *
   * @return array
   *   An associative array, keyed by region ID, containing the render arrays
   *   representing the content of each region.
   */
  protected function buildRegions(array $regions, array $contexts) {
    $build = parent::buildRegions($regions, $contexts);

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
      }
    }

    return $build;
  }

}
