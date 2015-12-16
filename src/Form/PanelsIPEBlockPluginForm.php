<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm.
 */

namespace Drupal\panels_ipe\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\PageVariantInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a block plugin temporarily using AJAX.
 *
 * Unlike most forms, this never saves a block plugin instance or persists it
 * from state to state. This is only for the initial addition to the Layout.
 */
class PanelsIPEBlockPluginForm extends FormBase {

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface $blockManager
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   */
  protected $contextHandler;

  /**
   * @var \Drupal\Core\Render\RendererInterface $renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new PanelsIPEBlockPluginForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   */
  public function __construct(PluginManagerInterface $block_manager, ContextHandlerInterface $context_handler, RendererInterface $renderer, SharedTempStoreFactory $temp_store_factory) {
    $this->blockManager = $block_manager;
    $this->contextHandler = $context_handler;
    $this->renderer = $renderer;
    $this->tempStore = $temp_store_factory->get('panels_ipe');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.handler'),
      $container->get('renderer'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_ipe_block_plugin_form';
  }

  /**
   * Builds a form that constructs a unsaved instance of a Block for the IPE.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $plugin_id
   *   The requested Block Plugin ID.
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The current PageVariant ID.
   * @param string $uuid
   *   An optional Block UUID, if this is an existing Block.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, PageVariantInterface $page_variant = NULL, $uuid = NULL) {
    // We require these default arguments.
    if (!$plugin_id || !$page_variant) {
      return FALSE;
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $page_variant->getVariantPlugin();

    // Grab the current layout's regions.
    $regions = $variant_plugin->getRegionNames();

    // If $uuid is present, a block should exist.
    if ($uuid) {
      /** @var \Drupal\Core\Block\BlockBase $block_instance */
      $block_instance = $variant_plugin->getBlock($uuid);
    }
    else {
      // Create an instance of this Block plugin.
      /** @var \Drupal\Core\Block\BlockBase $block_instance */
      $block_instance = $this->blockManager->createInstance($plugin_id);
    }

    // Determine the current region.
    $block_config = $block_instance->getConfiguration();
    if (isset($block_config['region']) && isset($regions[$block_config['region']])) {
      $region = $block_config['region'];
    }
    else {
      $region = reset($regions);
    }

    // Wrap the form so that our AJAX submit can replace its contents.
    $form['#prefix'] = '<div id="panels-ipe-block-plugin-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Add our various card wrappers.
    $form['flipper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'flipper',
      ],
    ];

    $form['flipper']['front'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'front',
      ],
    ];

    $form['flipper']['back'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'back',
      ],
    ];

    $form['#attributes']['class'][] = 'flip-container';

    // Get the base configuration form for this block.
    $form['flipper']['front']['settings'] = $block_instance->buildConfigurationForm([], $form_state);

    // Add the block ID, variant ID to the form as values.
    $form['plugin_id'] = ['#type' => 'value', '#value' => $plugin_id];
    $form['variant_id'] = ['#type' => 'value', '#value' => $page_variant->id()];
    $form['uuid'] = ['#type' => 'value', '#value' => $uuid];

    // Add a select list for region assignment.
    $form['flipper']['front']['settings']['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $regions,
      '#required' => TRUE,
      '#default_value' => $region
    ];

    // Add an add button, which is only used by our App.
    $form['submit'] = [
      '#type' => 'button',
      '#value' => $uuid ? $this->t('Update') : $this->t('Add'),
      '#ajax' => [
        'callback' => '::submitForm',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => '',
        ],
      ],
    ];

    // Add a preview button.
    $form['preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Toggle Preview'),
      '#ajax' => [
        'callback' => '::submitPreview',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => '',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state);

    $block_instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state);

    // Submit the block form.
    $block_instance->submitConfigurationForm($form, $form_state);

    // Save the block instance to our temporary configuration.
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = PageVariant::load($form_state->getValue('variant_id'));
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $page_variant->getVariantPlugin();

    // If a temporary configuration for this variant exists, use it.
    $temp_store_key = 'variant.' . $page_variant->id();
    if ($variant_config = $this->tempStore->get($temp_store_key)) {
      $variant_plugin->setConfiguration($variant_config);
    }

    // Set the block region appropriately.
    $block_config = $block_instance->getConfiguration();
    $block_config['region'] = $form_state->getValue('region');

    // Determine if we need to update or add this block.
    if ($uuid = $form_state->getValue('uuid')) {
      $variant_plugin->updateBlock($uuid, $block_config);
    }
    else {
      $uuid = $variant_plugin->addBlock($block_config);
    }

    // Set the tempstore value.
    $variant_config = $variant_plugin->getConfiguration();
    $this->tempStore->set('variant.' . $page_variant->id(), $variant_config);

    // Assemble data required for our App.
    $build = $this->buildBlockInstance($block_instance);

    // Add our data attribute for the Backbone app.
    $build['#attributes']['data-block-id'] = $uuid;

    $settings = [
      'uuid' => $uuid,
      'label' => $block_instance->label(),
      'id' => $block_instance->getPluginId(),
      'region' => $form_state->getValue('region'),
      'html' => $this->renderer->render($build)
    ];

    // Add Block metadata and HTML as a drupalSetting.
    // @todo How do we handle #attachments in the $build this way?
    $form['#attached']['drupalSettings']['panels_ipe']['updated_block'] = $settings;

    return $form;
  }

  /**
   * Previews our current Block configuration.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $form
   *   The form structure.
   */
  public function submitPreview(array &$form, FormStateInterface $form_state) {
    // Get the Block instance.
    $block_instance = $this->getBlockInstance($form_state);
    $block_instance->submitConfigurationForm($form, $form_state);

    // Gather a render array for the block.
    $build = $this->buildBlockInstance($block_instance);

    // Add the preview to the backside of the card and inform JS that we need to
    // be flipped.
    $form['flipper']['back']['preview'] = $build;
    $form['#attached']['drupalSettings']['panels_ipe']['toggle_preview'] = TRUE;

    return $form;
  }

  /**
   * Loads or creates a Block Plugin instance suitable for rendering or testing.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Block\BlockBase
   *   The Block Plugin instance.
   */
  protected function getBlockInstance($form_state) {
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = PageVariant::load($form_state->getValue('variant_id'));

    // If a UUID is provided, the Block should already exist.
    if ($uuid = $form_state->getValue('uuid')) {
      /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
      $variant_plugin = $page_variant->getVariantPlugin();

      // If a temporary configuration for this variant exists, use it.
      $temp_store_key = 'variant.' . $page_variant->id();
      if ($variant_config = $this->tempStore->get($temp_store_key)) {
        $variant_plugin->setConfiguration($variant_config);
      }

      // Load the existing Block instance.
      $block_instance = $variant_plugin->getBlock($uuid);
    }
    else {
      // Create an instance of this Block plugin.
      /** @var \Drupal\Core\Block\BlockBase $block_instance */
      $block_instance = $this->blockManager->createInstance($form_state->getValue('plugin_id'));
    }

    // Add context to the block.
    if ($block_instance instanceof ContextAwarePluginInterface) {
      $this->contextHandler->applyContextMapping($block_instance, $page_variant->getContexts());
    }

    return $block_instance;
  }

  /**
   * Compiles a render array for the given Block instance based on the form.
   *
   * @param \Drupal\Core\Block\BlockBase $block_instance
   *   The Block instance you want to render.
   *
   * @return array $build
   *   The Block render array.
   */
  protected function buildBlockInstance($block_instance) {
    // Get the new block configuration.
    $configuration = $block_instance->getConfiguration();

    // Compile the render array.
    $build = [
      '#theme' => 'block',
      '#configuration' => $configuration,
      '#plugin_id' => $block_instance->getPluginId(),
      '#base_plugin_id' => $block_instance->getBaseId(),
      '#derivative_plugin_id' => $block_instance->getDerivativeId(),
      'content' => $block_instance->build(),
    ];

    return $build;
  }

}
