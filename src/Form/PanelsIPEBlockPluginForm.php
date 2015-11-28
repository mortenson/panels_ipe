<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm.
 */

namespace Drupal\panels_ipe\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
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
   *
   * @return array
   *   The form structure.
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, $variant_id = NULL, $regions = NULL) {
    // We require these defaults arguments.
    if (!$plugin_id || !$variant_id || !$regions) {
      return FALSE;
    }

    // Create an instance of this Block plugin.
    /** @var \Drupal\Core\Block\BlockBase $block_instance */
    $block_instance = $this->blockManager->createInstance($plugin_id);

    // Wrap the form so that our AJAX submit can replace its contents.
    $form['#prefix'] = '<div id="panels-ipe-block-plugin-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Wrap the form elements in a container, to make it inline with the preview.
    $form['ipe_form'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'panels-ipe-block-plugin-form-contents'
      ]
    ];

    // Get the base configuration form for this block.
    $form['ipe_form']['form'] = $block_instance->buildConfigurationForm($form, $form_state);

    // Add the block ID to the form as a hidden field.
    $form['ipe_form']['plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $plugin_id
    ];

    // Add the variant ID for rendering the preview with context.
    $form['ipe_form']['variant_id'] = [
      '#type' => 'hidden',
      '#value' => $variant_id
    ];

    // Add a select list for region assignment.
    $form['ipe_form']['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $regions,
      '#required' => TRUE,
      '#default_value' => reset($regions)
    ];

    // Add an add button, which is only used by our App.
    $form['ipe_form']['add'] = [
      '#type' => 'button',
      '#value' => $this->t('Add'),
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

    // Add a preview button that uses AJAX.
    $form['ipe_form']['preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#ajax' => [
        'callback' => '::previewForm',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Rendering preview...'
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
   * Adds a preview of the current Block Plugin to the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @return array $form
   *   A renderable array representing the form.
   */
  public function previewForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state->getValue('variant_id'), $form_state->getValue('plugin_id'));

    // Submit the configuration form.
    $block_instance->submitConfigurationForm($form, $form_state);

    // Replace our preview contents with a rendered block.
    $build = [
      '#theme' => 'block',
      '#configuration' => $block_instance->getConfiguration(),
      '#plugin_id' => $block_instance->getPluginId(),
      '#base_plugin_id' => $block_instance->getBaseId(),
      '#derivative_plugin_id' => $block_instance->getDerivativeId(),
    ];
    $build['content'] = $block_instance->build();

    // Wrap our build so it can be displayed inline.
    $build['#prefix'] = '<div id="panels-ipe-block-plugin-form-preview">';
    $build['#suffix'] = '</div>';

    // Add a special element we'll use to preview the potential block.
    $form['build'] = $build;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state->getValue('variant_id'), $form_state->getValue('plugin_id'));

    // Submit the configuration form.
    $block_instance->submitConfigurationForm($form, $form_state);

    // Get the new block configuration.
    $configuration = $block_instance->getConfiguration();

    // Add block settings to the form so that we can create the new BlockModel.
    $uuid = \Drupal::service('uuid')->generate();
    $elements = [
      '#theme' => 'block',
      '#attributes' => [
        'data-block-id' => $uuid
      ],
      '#configuration' => $configuration,
      '#plugin_id' => $block_instance->getPluginId(),
      '#base_plugin_id' => $block_instance->getBaseId(),
      '#derivative_plugin_id' => $block_instance->getDerivativeId(),
      'content' => $block_instance->build()
    ];

    $settings = [
      'uuid' => $uuid,
      'label' => $block_instance->label(),
      'id' => $block_instance->getPluginId(),
      'provider' => $configuration['provider'],
      'region' => $form_state->getValue('region'),
      'html' => $this->renderer->render($elements),
      'new' => TRUE
    ];

    // Merge in the current configuration.
    $settings = array_merge($configuration, $settings);

    $form = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'panels-ipe-block-plugin-form-json'
      ],
      ['#markup' => Json::encode($settings)]
    ];

    return $form;
  }

  /**
   * Creates a Block Plugin instance suitable for rendering or testing.
   *
   * @param string $variant_id The Variant ID.
   * @param string $plugin_id The Block Plugin ID.
   *
   * @return \Drupal\Core\Block\BlockBase The Block Plugin instance.
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

}
