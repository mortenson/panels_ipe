<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Controller\PanelsIPEPageController.
 */

namespace Drupal\panels_ipe\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal\layout_plugin\Layout;
use Drupal\Core\Render\RendererInterface;
use Drupal\page_manager\Entity\PageVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Contains all JSON endpoints required for Panels IPE + Page Manager.
 */
class PanelsIPEPageController extends ControllerBase {

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new PanelsIPEController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(BlockManagerInterface $block_manager, RendererInterface $renderer) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer')
    );
  }

  /**
   * Gets a block's metadata and rendered HTML.
   *
   * @param string $variant_id
   *   The machine name of the current display variant.
   * @param string $block_id
   *   The UUID of the requested block.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function getBlock($variant_id, $block_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Check if the block exists.
    if (!$block = $variant_plugin->getBlock($block_id)) {
      throw new NotFoundHttpException();
    }

    // Check entity access before continuing.
    $user = $this->currentUser();
    if (!$block->access($user)) {
      throw new AccessDeniedHttpException();
    }

    // Assemble a render array for the block.
    // @todo Support contexts and revisions for all entities.
    $configuration = $block->getConfiguration();
    $elements = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $configuration,
      '#plugin_id' => $block->getPluginId(),
      '#base_plugin_id' => $block->getBaseId(),
      '#derivative_plugin_id' => $block->getDerivativeId(),
      'content' => $block->build()
    ];

    // Return a structured JSON response for our Backbone App.
    $data = [
      'html' => $this->renderer->render($elements),
      'uuid' => $configuration['uuid'],
      'label' => $block->label(),
      'id' => $block->getPluginId()
    ];

    return new JsonResponse($data);
  }

  /**
   * Gets a list of available Layouts, without wrapping HTML.
   *
   * @param string $variant_id
   *   The machine name of the current display variant.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function getLayouts($variant_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Get the current layout.
    $layout = $variant_plugin->getConfiguration()['layout'];

    // Get a list of all available layouts.
    $layouts = Layout::getLayoutOptions();
    $data = [];
    foreach ($layouts as $id => $label) {
      $data[] = [
        'id' => $id,
        'label' => $label,
        'current' => $layout == $id
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Gets a given layout with empty regions and relevant metadata.
   *
   * @param string $variant_id
   *   The machine name of the current display variant.
   * @param string $layout_id
   *   The machine name of the requested layout.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function getLayout($variant_id, $layout_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Build the requested layout.
    $configuration = $variant_plugin->getConfiguration();
    $configuration['layout'] = $layout_id;
    $variant_plugin->setConfiguration($configuration);
    $build = $variant_plugin->build();

    // Remove all blocks from the build.
    $regions = $variant_plugin->getRegionNames();
    $region_data = [];
    foreach ($regions as $id => $label) {
      // Get all block/random keys.
      $children = Element::getVisibleChildren($build[$id]);
      // Unset those keys, retaining the theme variables for the region.
      $build[$id] = array_diff_key($build[$id], array_flip($children));

      // Format region metadata.
      $region_data[] = [
        'name' => $id,
        'label' => $label
      ];
    }

    // Remove the wrapping elements, which our builder adds to every build.
    unset($build['#suffix']);
    unset($build['#prefix']);

    // Get the current layout.
    $current_layout = $variant_plugin->getConfiguration()['layout'];

    // Get a list of all available layouts.
    $layouts = Layout::getLayoutOptions();

    $data = [
      'id' => $layout_id,
      'label' => $layouts[$layout_id],
      'current' => $current_layout == $layout_id,
      'html' => $this->renderer->render($build),
      'regions' => $region_data
    ];

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

}
