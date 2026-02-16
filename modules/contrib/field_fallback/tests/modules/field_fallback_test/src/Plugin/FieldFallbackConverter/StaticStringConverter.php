<?php

namespace Drupal\field_fallback_test\Plugin\FieldFallbackConverter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterBase;

/**
 * Converter that converts an image field value to a string.
 *
 * @FieldFallbackConverter(
 *   id = "static_string",
 *   label = @Translation("Static string"),
 *   source = {"*"},
 *   target = {"string"},
 *   weight = 2
 * )
 */
class StaticStringConverter extends FieldFallbackConverterBase {

  /**
   * {@inheritdoc}
   */
  public function convert(FieldItemListInterface $field) {
    return $this->getConfiguration()['static_string_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + ['static_string_value' => 'test value'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    $form['static_string_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Static string value'),
      '#default_value' => $configuration['static_string_value'] ?? '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    if ($form_state->getValue('static_string_value') === 'fail') {
      $form_state->setErrorByName('static_string_value', 'Value should not be fail.');
    }
  }

}
