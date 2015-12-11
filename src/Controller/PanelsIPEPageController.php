<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Controller\PanelsIPEPageController.
 */

namespace Drupal\panels_ipe\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_manager\Entity\PageVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_plugin_manager
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
    $layout = $variant_plugin->getLayout()->getPluginId();

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

    $regions = $variant_plugin->getRegionNames();
    $region_data = [];
    $region_content = [];

    // Compile region content and metadata.
    foreach ($regions as $id => $label) {
      // Wrap the region with a class/data attribute that our app can use.
      $region_name = Html::getClass("block-region-$id");
      $region_content[$id] = [
        '#prefix' =>'<div class="' . $region_name . '" data-region-name="' . $id . '">',
        '#suffix' => '</div>'
      ];

      // Format region metadata.
      $region_data[] = [
        'name' => $id,
        'label' => $label
      ];
    }
    $build = $variant_plugin->getLayout()->build($region_content);

    // Get the current layout.
    $current_layout = $variant_plugin->getLayout()->getPluginId();

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
   *
   * @throws AccessDeniedHttpException
   */
  protected function updateVariant($variant, $layout) {
    // Check variant access.
    if (!$variant->access('update')) {
      throw new AccessDeniedHttpException();
    }

    // Load the current variant plugin.
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $variant->getVariantPlugin();

    // Change the layout.
    $configuration = $variant_plugin->getConfiguration();
    $configuration['layout'] = $layout['id'];
    $variant_plugin->setConfiguration($configuration);
    $variant_plugin->getLayout();

    // Edit our blocks.
    $return_data = ['newBlocks' => []];
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
        unset($block['new']);

        // If the block already exists, update it. Otherwise add it.
        if (isset($configuration['blocks'][$block['uuid']])) {
          $variant_plugin->updateBlock($block['uuid'], NestedArray::mergeDeep($configuration['blocks'][$block['uuid']], $block));
        }
        else {
          $new_uuid = $variant_plugin->addBlock($block);
          $return_data['newBlocks'][$block['uuid']] = $new_uuid;
        }
      }
    }

    // Remove blocks if needed.
    foreach ($layout['deletedBlocks'] as $uuid) {
      $variant_plugin->removeBlock($uuid);
    }

    // Save the plugin.
    $variant->save();

    return new JsonResponse($return_data);
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
   *
   * @throws AccessDeniedHttpException
   */
  public function getBlockPlugins($variant_id) {
    // Check if the variant exists.
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    if (!$variant = PageVariant::load($variant_id)) {
      throw new NotFoundHttpException();
    }

    // Check variant access.
    if (!$variant->access('read')) {
      throw new AccessDeniedHttpException();
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
   * Drupal AJAX compatible route for rendering a given Block Plugin's form.
   *
   * @param string $variant_id
   *   The PageVariant ID.
   * @param string $layout_id
   *   The requested Layout ID.
   * @param string $plugin_id
   *   The requested Block Plugin ID.
   * @param string $block_uuid
   *   The Block UUID, if this is an existing Block.
   *
   * @return Response
   *
   * @throws AccessDeniedHttpException|NotFoundHttpException
   */
  public function getBlockPluginForm($variant_id, $layout_id, $plugin_id, $block_uuid = NULL) {
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

    // Set our configuration to match the current, possibly unsaved layout.
    $configuration = $variant_plugin->getConfiguration();
    $configuration['layout'] = $layout_id;
    $variant_plugin->setConfiguration($configuration);

    // Get the configuration in the block plugin definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($variant->getContexts());

    // Check if the block plugin is defined.
    if (!isset($definitions[$plugin_id])) {
      throw new NotFoundHttpException();
    }

    // If $block_uuid is passed, check if it already exists in the Variant Plugin.
    $new = TRUE;
    if ($block_uuid) {
      $new = isset($configuration['blocks'][$block_uuid]);
    }

    // Grab the current layout's regions.
    $regions = $variant_plugin->getRegionNames();

    // Build a Block Plugin configuration form.
    $form = \Drupal::formBuilder()->getForm('Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm', $plugin_id, $variant_id, $regions, $block_uuid, $new);

    // Return the rendered form as a proper Drupal AJAX response.
    // This is needed as forms often have custom JS and CSS that need added,
    // and it isn't worth replicating things that work in Drupal with Backbone.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-block-plugin-form', $form);
    $response->addCommand($command);
    return $response;
  }

}
