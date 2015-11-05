<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Controller\PanelsIPEPageController.
 */

namespace Drupal\panels_ipe\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_plugin\Layout;
use Drupal\page_manager\PageVariantInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\page_manager\PageInterface;
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
   * AJAX callback to get a block's metadata and rendered HTML.
   *
   * @param PageInterface $page
   *   The current Page Manager page.
   * @param string $variant_id
   *   The machine name of the current display variant.
   * @param string $block_id
   *   The UUID of the requested block.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function getBlock(PageInterface $page, $variant_id, $block_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = $page->getVariant($variant_id)) {
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
   * AJAX callback to get a list of available Layouts.
   *
   * @param PageInterface $page
   *   The current Page Manager page.
   * @param string $variant_id
   *   The machine name of the current display variant.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function getLayouts(PageInterface $page, $variant_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = $page->getVariant($variant_id)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Get the current layout.
    $layout = $variant_plugin->getConfiguration()['layout'];

    // Get a list of all available layouts.
    $layouts = Layout::getLayoutOptions(['group_by_category' => TRUE]);

    // Return a structured JSON response for our Backbone App.
    $data = [
      'current_layout' => $layout,
      'layouts' => $layouts
    ];

    return new JsonResponse($data);
  }

}
