<?php

namespace Drupal\designs\Plugin\designs\setting;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignSettingBase;

/**
 * The boolean setting.
 *
 * @DesignSetting(
 *   id = "boolean",
 *   label = @Translation("Boolean")
 * )
 */
class BooleanSetting extends DesignSettingBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $form['string'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a string.'),
      '#description' => $this->t('Uses the setting as a string value rather than boolean.'),
      '#default_value' => $values['string'] ?? $this->configuration['string'],
    ];

    $form['on'] = [
      '#type' => 'textfield',
      '#title' => $this->t('on'),
      '#description' => $this->t('The value to use when true.'),
      '#default_value' => $values['on'] ?? $this->configuration['on'],
    ];

    $form['off'] = [
      '#type' => 'textfield',
      '#title' => $this->t('off'),
      '#description' => $this->t('The value to use when false.'),
      '#default_value' => $values['off'] ?? $this->configuration['off'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'value' => $this->getDefinitionValue('default_value', ''),
        'string' => $this->getDefinitionValue('string', FALSE),
        'on' => $this->getDefinitionValue('on', '1'),
        'off' => $this->getDefinitionValue('off', '0'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $form['value'] = [
      '#type' => 'checkbox',
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

  /**
   * {@inheritdoc}
   */
  public function process(array $build, &$element) {
    // When there is no setting content, we use the provided values.
    $output = $this->renderer->render($build);

    // Process the rendered content using PHP coersion.
    $output = trim(strip_tags($output));
    $result = filter_var($output, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    // Respond using the values provided by the configuration.
    if ($this->configuration['string']) {
      return ['#markup' => $result ? $this->configuration['on'] : $this->configuration['off']];
    }
    return ['#markup' => $result];
  }

}
