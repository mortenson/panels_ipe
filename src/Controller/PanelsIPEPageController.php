<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Controller\PanelsIPEPageController.
 */

namespace Drupal\panels_ipe\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_manager\PageVariantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\SharedTempStoreFactory;

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
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new PanelsIPEController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   */
  public function __construct(BlockManagerInterface $block_manager, RendererInterface $renderer, LayoutPluginManagerInterface $layout_plugin_manager, SharedTempStoreFactory $temp_store_factory) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->tempStore = $temp_store_factory->get('panels_ipe');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer'),
      $container->get('plugin.manager.layout_plugin'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * Takes the current Page Variant and returns a possibly modified Page Variant
   * based on what's in TempStore for this user.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current Page Variant.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function loadPageVariant(PageVariantInterface $page_variant) {
    // If a temporary configuration for this variant exists, use it.
    $temp_store_key = 'variant.' . $page_variant->id();
    if ($variant_config = $this->tempStore->get($temp_store_key)) {
      /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
      $variant_plugin = $page_variant->getVariantPlugin();
      $variant_plugin->setConfiguration($variant_config);
    }

    return $page_variant;
  }

  /**
   * Takes the current Page Variant and saves it, optionally deleting anything
   * that may be in temp store.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current Page Variant.
   * @param bool $temp
   *   Whether or not to save to temp store.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function savePageVariant(PageVariantInterface $page_variant, $temp = TRUE) {
    $temp_store_key = 'variant.' . $page_variant->id();

    // Save configuration to temp store.
    if ($temp) {
      /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
      $variant_plugin = $page_variant->getVariantPlugin();
      $this->tempStore->set($temp_store_key, $variant_plugin->getConfiguration());
    }
    else {
      // Check to see if temp store has configuration saved.
      if ($variant_config = $this->tempStore->get($temp_store_key)) {
        // Delete the existing temp store value.
        $this->tempStore->delete($temp_store_key);
      }

      // Save the real entity.
      $page_variant->save();
    }

    return $page_variant;
  }

  /**
   * Removes any temporary changes to the variant.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current Page Variant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function cancelPageVariant(PageVariantInterface $page_variant) {
    // If a temporary configuration for this variant exists, use it.
    $temp_store_key = 'variant.' . $page_variant->id();
    if ($variant_config = $this->tempStore->get($temp_store_key)) {
      $this->tempStore->delete($temp_store_key);
    }

    // Return an empty JSON response.
    return new JsonResponse();
  }

  /**
   * Gets a list of available Layouts, without wrapping HTML.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The page variant entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getLayouts(PageVariantInterface $page_variant) {
    $page_variant = $this->loadPageVariant($page_variant);

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $page_variant->getVariantPlugin();

    // Get the current layout.
    $layout = $variant_plugin->getLayout()->getPluginId();

    // Get a list of all available layouts.
    $layouts = $this->layoutPluginManager->getLayoutOptions();
    $data = [];
    foreach ($layouts as $id => $label) {
      $data[] = [
        'id' => $id,
        'label' => $label,
        'current' => $layout == $id,
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Gets a given layout with empty regions and relevant metadata.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The page variant entity.
   * @param string $layout_id
   *   The machine name of the requested layout.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getLayout(PageVariantInterface $page_variant, $layout_id) {
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $page_variant->getVariantPlugin();

    // Build the requested layout.
    $configuration = $variant_plugin->getConfiguration();
    $configuration['layout'] = $layout_id;
    $variant_plugin->setConfiguration($configuration);

    // Inherit our PageVariant's contexts before rendering.
    $variant_plugin->setContexts($page_variant->getContexts());

    $regions = $variant_plugin->getRegionNames();
    $region_data = [];
    $region_content = [];

    // Compile region content and metadata.
    foreach ($regions as $id => $label) {
      // Wrap the region with a class/data attribute that our app can use.
      $region_name = Html::getClass("block-region-$id");
      $region_content[$id] = [
        '#prefix' =>'<div class="' . $region_name . '" data-region-name="' . $id . '">',
        '#suffix' => '</div>',
      ];

      // Format region metadata.
      $region_data[] = [
        'name' => $id,
        'label' => $label,
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
      'regions' => $region_data,
    ];

    // Update temp store.
    $this->savePageVariant($page_variant);

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Updates the current PageVariant based on the changes done in our app.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current variant.
   * @param array $layout
   *   The decoded LayoutModel from our App.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  protected function updateVariant(PageVariantInterface $page_variant, $layout) {
    // Load the variant.
    $page_variant = $this->loadPageVariant($page_variant);

    // Load the current variant plugin.
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $page_variant->getVariantPlugin();

    // Set our weight and region based on the metadata in our Backbone app.
    foreach ($layout['regionCollection'] as $region) {
      $weight = 0;
      foreach ($region['blockCollection'] as $block) {
        /** @var \Drupal\Core\Block\BlockBase $block_instance */
        $block_instance = $variant_plugin->getBlock($block['uuid']);

        $block_instance->setConfigurationValue('region', $region['name']);
        $block_instance->setConfigurationValue('weight', ++$weight);

        $variant_plugin->updateBlock($block['uuid'], $block_instance->getConfiguration());
      }
    }

    // Remove blocks that need removing.
    // @todo We should do this on the fly instead of at on save.
    foreach ($layout['deletedBlocks'] as $uuid) {
      $variant_plugin->removeBlock($uuid);
    }

    // Save the variant and remove temp storage.
    $this->savePageVariant($page_variant, FALSE);

    return new JsonResponse(['deletedBlocks' => []]);
  }

  /**
   * Updates (PUT) an existing Layout in this Variant.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current variant.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function updateLayout(PageVariantInterface $page_variant, Request $request) {
    // Decode the request.
    $content = $request->getContent();
    if (!empty($content) && $layout = Json::decode($content)) {
      return $this->updateVariant($page_variant, $layout);
    }
    else {
      return new JsonResponse(['success' => false], 400);
    }
  }

  /**
   * Creates (POST) a new Layout for this Variant.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current variant.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function createLayout(PageVariantInterface $page_variant, Request $request) {
    // For now, creating and updating a layout is the same thing.
    return $this->updateLayout($page_variant, $request);
  }

  /**
   * Gets a list of Block Plugins from the server.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current variant.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getBlockPlugins(PageVariantInterface $page_variant) {
    $page_variant = $this->loadPageVariant($page_variant);

    // Get block plugin definitions from the server.
    $definitions = $this->blockManager->getDefinitionsForContexts($page_variant->getContexts());

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
        'provider' => $definition['provider'],
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Drupal AJAX compatible route for rendering a given Block Plugin's form.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current variant.
   * @param string $plugin_id
   *   The requested Block Plugin ID.
   * @param string $block_uuid
   *   The Block UUID, if this is an existing Block.
   *
   * @return Response
   */
  public function getBlockPluginForm(PageVariantInterface $page_variant, $plugin_id, $block_uuid = NULL) {
    $page_variant = $this->loadPageVariant($page_variant);

    // Get the configuration in the block plugin definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($page_variant->getContexts());

    // Check if the block plugin is defined.
    if (!isset($definitions[$plugin_id])) {
      throw new NotFoundHttpException();
    }

    // Build a Block Plugin configuration form.
    $form = $this->formBuilder()->getForm('Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm', $plugin_id, $page_variant, $block_uuid);

    // Return the rendered form as a proper Drupal AJAX response.
    // This is needed as forms often have custom JS and CSS that need added,
    // and it isn't worth replicating things that work in Drupal with Backbone.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-block-plugin-form', $form);
    $response->addCommand($command);
    return $response;
  }

}
