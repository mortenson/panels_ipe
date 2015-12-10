<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm.
 */

namespace Drupal\panels_ipe\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\page_manager\Entity\PageVariant;
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
   * Constructs a new PanelsIPEBlockPluginForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(PluginManagerInterface $block_manager, ContextHandlerInterface $context_handler, RendererInterface $renderer) {
    $this->blockManager = $block_manager;
    $this->contextHandler = $context_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.handler'),
      $container->get('renderer')
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
   * @param string $variant_id
   *   The current PageVariant ID.
   * @param array $regions
   *   An array of region definitions.
   * @param string $uuid
   *   An optional Block UUID, if this is an existing Block.
   * @param bool $new
   *   Whether or not the Block is new (to the Variant Plugin).
   *
   * @return array
   *   The form structure.
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, $variant_id = NULL, $regions = NULL, $uuid = NULL, $new = TRUE) {
    // We require these defaults arguments.
    if (!$plugin_id || !$variant_id || !$regions) {
      return FALSE;
    }

    // Create an instance of this Block plugin.
    /** @var \Drupal\Core\Block\BlockBase $block_instance */
    $block_instance = $this->blockManager->createInstance($plugin_id);

    // Check to see if an existing block is present.
    $input = $form_state->getUserInput();
    if (isset($input['block'])) {
      // @todo This should be removed in Backbone.
      unset($input['block']['html']);
      unset($input['block']['active']);
      unset($input['block']['new']);
      $block_instance->setConfiguration($input['block']);
    }

    // Wrap the form so that our AJAX submit can replace its contents.
    $form['#prefix'] = '<div id="panels-ipe-block-plugin-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Add our various card wrappers.
    $form['flipper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'flipper'
      ]
    ];

    $form['flipper']['front'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'front'
      ]
    ];

    $form['flipper']['back'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'back'
      ]
    ];

    $form['#attributes']['class'][] = 'flip-container';

    // Get the base configuration form for this block.
    $form['flipper']['front']['settings'] = $block_instance->buildConfigurationForm([], $form_state);

    // Add the block ID, variant ID to the form as values.
    $form['plugin_id'] = ['#type' => 'value', '#value' => $plugin_id];
    $form['variant_id'] = ['#type' => 'value', '#value' => $variant_id];
    $form['uuid'] = ['#type' => 'value', '#value' => $uuid];
    $form['new'] = ['#type' => 'value', '#value' => $new];

    // Add a select list for region assignment.
    $form['flipper']['front']['settings']['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $regions,
      '#required' => TRUE,
      '#default_value' => isset($input['block']) ? $input['block']['region'] : reset($regions)
    ];

    // Add an add button, which is only used by our App.
    $form['submit'] = [
      '#type' => 'button',
      '#value' => isset($input['block']) ? $this->t('Update') : $this->t('Add'),
      '#ajax' => [
        'callback' => '::submitForm',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => ''
        ]
      ]
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
          'message' => ''
        ]
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state->getValue('variant_id'), $form_state->getValue('plugin_id'));

    $block_instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state->getValue('variant_id'), $form_state->getValue('plugin_id'));

    $build = $this->buildBlockInstance($block_instance, $form, $form_state);

    // Add our data attribute for the Backbone app.
    $uuid = $form_state->getValue('uuid') ? $form_state->getValue('uuid') : \Drupal::service('uuid')->generate();
    $build['#attributes']['data-block-id'] = $uuid;

    // Grab the Block configuration for convenience.
    $configuration = $build['#configuration'];

    $settings = [
      'uuid' => $uuid,
      'label' => empty($configuration['label']) ? $block_instance->label() : $configuration['label'],
      'id' => $block_instance->getPluginId(),
      'region' => $form_state->getValue('region'),
      'html' => $this->renderer->render($build),
      'new' => $form_state->getValue('new') ? $form_state->getValue('new') : TRUE
    ];

    // Merge in the current configuration.
    $settings = NestedArray::mergeDeep($configuration, $settings);
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
    $block_instance = $this->getBlockInstance($form_state->getValue('variant_id'), $form_state->getValue('plugin_id'));

    // Gather a render array for the block.
    $build = $this->buildBlockInstance($block_instance, $form, $form_state);

    // Add the preview to the backside of the card and inform JS that we need to
    // be flipped.
    $form['flipper']['back']['preview'] = $build;
    $form['#attached']['drupalSettings']['panels_ipe']['toggle_preview'] = TRUE;

    return $form;
  }

  /**
   * Creates a Block Plugin instance suitable for rendering or testing.
   *
   * @param string $variant_id
   *   The Variant ID.
   * @param string $plugin_id
   *   The Block Plugin ID.
   *
   * @return \Drupal\Core\Block\BlockBase
   *   The Block Plugin instance.
   */
  protected function getBlockInstance($variant_id, $plugin_id) {
    // Create an instance of this Block plugin.
    /** @var \Drupal\Core\Block\BlockBase $block_instance */
    $block_instance = $this->blockManager->createInstance($plugin_id);

    // Add context to the block.
    if ($block_instance instanceof ContextAwarePluginInterface) {
      /** @var \Drupal\page_manager\PageVariantInterface $variant */
      $variant = PageVariant::load($variant_id);
      $this->contextHandler->applyContextMapping($block_instance, $variant->getContexts());
    }

    return $block_instance;
  }

  /**
   * Compiles a render array for the given Block instance based on the form.
   *
   * @param \Drupal\Core\Block\BlockBase $block_instance
   *   The Block instance you want to render.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $build
   *   The Block render array.
   */
  protected function buildBlockInstance($block_instance, $form, $form_state) {
    // Submit the configuration form.
    $block_instance->submitConfigurationForm($form, $form_state);

    // Get the new block configuration.
    $configuration = $block_instance->getConfiguration();

    // Compile the render array.
    $build = [
      '#theme' => 'block',
      '#configuration' => $configuration,
      '#plugin_id' => $block_instance->getPluginId(),
      '#base_plugin_id' => $block_instance->getBaseId(),
      '#derivative_plugin_id' => $block_instance->getDerivativeId(),
      'content' => $block_instance->build()
    ];

    return $build;
  }

}
