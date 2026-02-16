<?php

namespace Drupal\content_as_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures settings for content sync.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_as_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_as_config.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging'),
      '#default_value' => $this->config('content_as_config.config')->get('log') ?? TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('content_as_config.config')
      ->set('log', $form_state->getValue('log'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
