<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Controller\PanelsIPEPageController.
 */

namespace Drupal\panels_ipe\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_manager\Entity\PageVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Constructs a new PanelsIPEController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(BlockManagerInterface $block_manager, RendererInterface $renderer, LayoutPluginManagerInterface $layout_plugin_manager) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer'),
      $container->get('plugin.manager.layout_plugin')
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
      '#attributes' => [
        'data-block-id' => $block->getConfiguration()['uuid']
      ],
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

    // Check variant access.
    if (!$variant->access('read')) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Get the current layout.
    $layout = $variant_plugin->getConfiguration()['layout'];

    // Get a list of all available layouts.
    $layouts = $this->layoutPluginManager->getLayoutOptions();
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

    // Check variant access.
    if (!$variant->access('read')) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Build the requested layout.
    $configuration = $variant_plugin->getConfiguration();
    $configuration['layout'] = $layout_id;
    $variant_plugin->setConfiguration($configuration);

    // Inherit our PageVariant's contexts before rendering.
    $variant_plugin->setContexts($variant->getContexts());

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
    $layouts = $this->layoutPluginManager->getLayoutOptions();

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

  /**
   * Updates the current PageVariant based on the changes done in our app.
   *
   * @param \Drupal\page_manager\PageVariantInterface $variant
   *   The current variant.
   * @param array $layout
   *   The decoded LayoutModel from our App.
   *
   * @return JsonResponse
   */
  protected function updateVariant($variant, $layout) {
    // Load the current variant plugin.
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Change the layout.
    $configuration = $variant_plugin->getConfiguration();
    $configuration['layout'] = $layout['id'];
    $variant_plugin->setConfiguration($configuration);
    $variant_plugin->getLayout();

    // Edit our blocks.
    foreach ($layout['regionCollection'] as $region) {
      $weight = 0;
      foreach ($region['blockCollection'] as $block) {
        // Our Backbone app models Blocks to abstract their region.
        $block['region'] = $region['name'];

        // Weight is based by order in the collection.
        $block['weight'] = ++$weight;

        // @todo This should be removed in Backbone.
        unset($block['html']);
        unset($block['active']);

        // If the block already exists, update it. Otherwise add it.
        if (isset($configuration['blocks'][$block['uuid']])) {
          $variant_plugin->updateBlock($block['uuid'], array_merge($configuration['blocks'][$block['uuid']], $block));
        }
        else {
          $variant_plugin->addBlock($block);
        }
      }
    }

    // Remove blocks if needed.
    foreach ($layout['deletedBlocks'] as $uuid) {
      $variant_plugin->removeBlock($uuid);
    }

    // Save the plugin.
    $variant->save();

    return new JsonResponse(['success' => true]);
  }

  /**
   * Updates (PUT) an existing Layout in this Variant.
   *
   * @param string $variant_id
   *   The machine name of the current display variant.
   * @param string $layout_id
   *   The machine name of the requested layout.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function updateLayout($variant_id, $layout_id, Request $request) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    // Check variant access.
    if (!$variant->access('update')) {
      throw new AccessDeniedHttpException();
    }

    // Decode the request.
    $content = $request->getContent();
    if (!empty($content) && $layout = Json::decode($content)) {
      return $this->updateVariant($variant, $layout);
    }
    else {
      return new JsonResponse(['success' => false], 400);
    }
  }

  /**
   * Creates (POST) a new Layout for this Variant.
   *
   * @param string $variant_id
   *   The machine name of the current display variant.
   * @param string $layout_id
   *   The machine name of the new layout.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return JsonResponse
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function createLayout($variant_id, $layout_id, Request $request) {
    // For now, creating and updating a layout is the same thing.
    return $this->updateLayout($variant_id, $layout_id, $request);
  }

  /**
   * Gets a list of Block Plugins from the server.
   *
   * @param string $variant_id
   *   The PageVariant ID.
   *
   * @return JsonResponse
   */
  public function getBlockPlugins($variant_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    // Get block plugin definitions from the server.
    $definitions = $this->blockManager->getDefinitionsForContexts($variant->getContexts());

    // Assemble our relevant data.
    $data = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Don't add broken Blocks.
      if ($plugin_id == 'broken') {
        continue;
      }
      $data[] = [
        'plugin_id' => $plugin_id,
        'label' => $definition['admin_label'],
        'category' => $definition['category'],
        'id' => $definition['id'],
        'provider' => $definition['provider']
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Drupal AJAX compatible route for rending a given Block Plugin's form.
   *
   * @param string $variant_id
   *   The PageVariant ID.
   * @param string $layout_id
   *   The requested Layout ID.
   * @param string $plugin_id
   *   The requested Block Plugin ID.
   *
   * @return Response
   *
   * @throws NotFoundHttpException
   */
  public function getBlockPluginForm($variant_id, $layout_id, $plugin_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    // Get the configuration in the block plugin definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($variant->getContexts());

    // Check if the block plugin is defined.
    if (!isset($definitions[$plugin_id])) {
      throw new NotFoundHttpException();
    }

    // Grab the current layout's regions.
    /** @var \Drupal\layout_plugin\Plugin\Layout\LayoutBase $layout */
    $layout = $this->layoutPluginManager->createInstance($layout_id, []);
    $regions = $layout->getRegionNames();

    // Build a Block Plugin configuration form.
    $form = \Drupal::formBuilder()->getForm('Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm', $plugin_id, $variant_id, $regions);

    // Return the rendered form as a proper Drupal AJAX response.
    // This is needed as forms often have custom JS and CSS that need added,
    // and it isn't worth replicating things that work in Drupal with Backbone.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-block-plugin-form', $form);
    $response->addCommand($command);
    return $response;
  }

}
