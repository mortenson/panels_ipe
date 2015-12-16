<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Plugin\DisplayBuilder\InPlaceEditorDisplayBuilder.
 */

namespace Drupal\panels_ipe\Plugin\DisplayBuilder;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\PageVariantInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Drupal\panels\Plugin\DisplayBuilder\StandardDisplayBuilder;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The In-place editor display builder for viewing and editing a
 * PanelsDisplayVariant in the same place.
 *
 * @DisplayBuilder(
 *   id = "ipe",
 *   label = @Translation("In-place editor")
 * )
 */
class InPlaceEditorDisplayBuilder extends StandardDisplayBuilder {

  /**
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new InPlaceEditorDisplayBuilder.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account, SharedTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $context_handler, $account);

    $this->tempStore = $temp_store_factory->get('panels_ipe');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * Compiles settings needed for the IPE to function.
   *
   * @param array $regions
   *   The render array representing regions.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutInterface $layout
   *   The current layout.
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current path's page variant.
   *
   * @return array|bool
   *   An associative array representing the contents of drupalSettings, or
   *   FALSE if there was an error.
   */
  protected function getDrupalSettings(array $regions, LayoutInterface $layout, PageVariantInterface $page_variant) {
    $settings = [
      'regions' => [],
    ];

    // Add current block IDs to settings sorted by region.
    foreach ($regions as $region => $blocks) {
      $settings['regions'][$region]  = [
        'name' => $region,
        'label' => '',
        'blocks' => [],
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
          'id' => $block->getPluginId(),
        ];
        $settings['regions'][$region]['blocks'][$block_uuid] = NestedArray::mergeDeep($configuration, $setting);
      }
    }

    // Add the layout information.
    $layout_definition = $layout->getPluginDefinition();
    $settings['layout'] = [
      'id' => $layout->getPluginId(),
      'label' => $layout_definition['label'],
      'original' => true,
    ];

    // Add the display variant's config.
    $settings['display_variant'] = [
      'label' => $page_variant->label(),
      'id' => $page_variant->id(),
      'uuid' => $page_variant->uuid(),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions, array $contexts, LayoutInterface $layout = NULL) {
    // Load our PageVariant.
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = \Drupal::request()->attributes->get('page_manager_page_variant');

    // Check to see if the current user has permissions to use the IPE.
    $has_permission = $this->account->hasPermission('access panels in-place editing');

    // Attach the Panels In-place editor library based on permissions.
    if ($page_variant && $has_permission) {
      // This flag tracks whether or not there are unsaved changes.
      $unsaved = FALSE;

      // If a temporary configuration for this variant exists, use it.
      $temp_store_key = 'variant.' . $page_variant->id();
      if ($variant_config = $this->tempStore->get($temp_store_key)) {
        // Reload the PageVariant. This is required to set variant plugin
        // configuration correctly.
        $page_variant = PageVariant::load($page_variant->id());

        /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
        $variant_plugin = $page_variant->getVariantPlugin();
        $variant_plugin->setConfiguration($variant_config);

        // Override our initial values with what's in TempStore.
        $layout = $variant_plugin->getLayout();
        $regions = $variant_plugin->getRegionAssignments();

        // Indicate that the user is viewing un-saved changes.
        $unsaved = TRUE;
      }

      $build = parent::build($regions, $contexts, $layout);

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
      $build['#attached']['library'][] = 'panels_ipe/panels_ipe';
      $build['#attached']['drupalSettings']['panels_ipe'] = $this->getDrupalSettings($regions, $layout, $page_variant);

      // Add our custom elements to the build.
      $build['#prefix'] = '<div id="panels-ipe-content">';

      // Indicate if the current user is viewing temp store.
      $tray_classes = $unsaved ? 'unsaved' : '';

      $build['#suffix'] = '</div><div id="panels-ipe-tray" class="' . $tray_classes . '"></div>';
    }
    // Use a standard build if the user can't use IPE.
    else {
      $build = parent::build($regions, $contexts, $layout);
    }

    return $build;
  }

}
