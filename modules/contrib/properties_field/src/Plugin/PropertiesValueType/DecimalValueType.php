<?php

namespace Drupal\properties_field\Plugin\PropertiesValueType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\properties_field\PropertiesValueType\PropertiesValueTypeBase;

/**
 * Provides the decimal properties value type plugin.
 *
 * @PropertiesValueType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 * )
 */
class DecimalValueType extends PropertiesValueTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'decimal_separator' => '.',
      'thousands_separator' => ' ',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetForm(array $element, $value, FormStateInterface $form_state) {
    $element = parent::widgetForm($element, $value, $form_state);

    $element['#type'] = 'number';
    $element['#step'] = 0.01;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state, array &$complete_form) {
    $options = [
      ''  => t('- None -'),
      '.' => t('Decimal point'),
      ',' => t('Comma'),
      ' ' => t('Space'),
      chr(8201) => t('Thin space'),
      "'" => t('Apostrophe'),
    ];

    $form['decimal_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Decimal marker'),
      '#options' => $options,
      '#default_value' => $this->configuration['decimal_separator'],
      '#required' => FALSE,
    ];

    $form['thousands_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Thousand marker'),
      '#options' => $options,
      '#default_value' => $this->configuration['thousands_separator'],
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsSummary() {
    $summary = [];

    if ($this->configuration['decimal_separator']) {
      $summary[] = $this->t('Decimal marker: @decimal_separator', [
        '@decimal_separator' => $this->configuration['decimal_separator'],
      ]);
    }

    if ($this->configuration['thousands_separator']) {
      $summary[] = $this->t('Thousand marker: @thousands_separator', [
        '@thousands_separator' => $this->configuration['thousands_separator'],
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterRender($value) {
    return number_format(
      $value,
      ceil($value) > $value ? 2 : 0,
      $this->configuration['decimal_separator'],
      $this->configuration['thousands_separator']
    );
  }

}
