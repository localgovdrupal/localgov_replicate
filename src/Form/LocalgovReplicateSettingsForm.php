<?php

namespace Drupal\localgov_replicate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure LocalGov Replicate settings for this site.
 */
class LocalgovReplicateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['localgov_replicate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'localgov_replicate_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('localgov_replicate.settings');

    // Retrieve all node types.
    $node_types = NodeType::loadMultiple();
    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Select the content types to enable replication for.'),
      '#options' => $options,
      '#default_value' => $config->get('node_types') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('localgov_replicate.settings')
      ->set('node_types', array_filter($form_state->getValue('node_types')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}