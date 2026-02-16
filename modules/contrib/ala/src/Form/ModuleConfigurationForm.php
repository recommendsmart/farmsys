<?php

namespace Drupal\ala\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ala_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ala.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ala.settings');

    $form['ala_default_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Define possibles classes'),
      '#default_value' => $config->get('ala_default_classes'),
      '#description' => $this->selectClassDescription(),
      '#attributes' => [
        'placeholder' => 'btn btn-default|Default button' . PHP_EOL . 'btn btn-primary|Primary button',
      ],
      '#size' => '30',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Return the description for the class select mode.
   */
  protected function selectClassDescription() {
    return $this->t('<p>The possible classes this link can have. Enter one value per line, in the format key|label.
    <br/>The key is the string which will be used as a class on a link. The label will be used on edit forms.
    <br/>If the key contains several classes, each class must be separated by a <strong>space</strong>.
    <br/>The label is optional: if a line contains a single string, it will be used as key and label.</p>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ala.settings')
      ->set('ala_default_classes', $form_state->getValue('ala_default_classes'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
