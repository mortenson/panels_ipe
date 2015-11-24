<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm.
 */

namespace Drupal\panels_ipe\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a block plugin temporarily using AJAX.
 *
 * Unlike most forms, this never saves a block plugin instance or persists it
 * from state to state. This is only for the initial addition to the Layout.
 */
class PanelsIPEBlockPluginForm extends FormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new PanelsIPEBlockPluginForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(PluginManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_ipe_block_plugin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL) {
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

    // Add the block to the form as a hidden field.
    $form['ipe_form']['plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $plugin_id
    ];

    // Add a preview button that uses AJAX.
    $form['ipe_form']['preview'] = [
      '#type' => 'button',
      '#value' => t('Preview'),
      '#ajax' => [
        'callback' => '::submitForm',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace'
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Create an instance of this Block plugin.
    /** @var \Drupal\Core\Block\BlockBase $block_instance */
    $block_instance = $this->blockManager->createInstance($form_state->getValue('plugin_id'));

    $block_instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create an instance of this Block plugin.
    /** @var \Drupal\Core\Block\BlockBase $block_instance */
    $block_instance = $this->blockManager->createInstance($form_state->getValue('plugin_id'));

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

}
