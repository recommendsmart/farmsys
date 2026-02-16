<?php

namespace Drupal\designs\Plugin\designs\setting;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignSettingBase;

/**
 * The textfield setting.
 *
 * @DesignSetting(
 *   id = "textfield",
 *   label = @Translation("Textfield")
 * )
 */
class TextfieldSetting extends DesignSettingBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'value' => $this->getDefinitionValue('default_value', ''),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->label(),
      '#description' => $this->getDescription(),
      '#default_value' => $values['value'] ?? $this->configuration['value'],
      '#required' => $this->isRequired(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSetting(array &$element) {
    return ['#markup' => $this->configuration['value']];
  }

}
