<?php

namespace Drupal\properties_field\Plugin\PropertiesValueType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the weight properties value type plugin.
 *
 * @PropertiesValueType(
 *   id = "weight",
 *   label = @Translation("Weight"),
 * )
 */
class WeightValueType extends DecimalValueType {

  /**
   * {@inheritdoc}
   */
  public function widgetForm(array $element, $value, FormStateInterface $form_state) {
    $element['#type'] = 'container';
    $element['#attributes']['class'][] = 'container-inline';

    $element['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $value['value'] ?? '',
      '#step' => 0.01,
      '#required' => $element['#required'],
    ];

    $element['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#title_display' => 'invisible',
      '#options' => [
        'gr' => $this->t('Grams'),
        'kg' => $this->t('Kilograms'),
      ],
      '#empty_value' => '',
      '#empty_option' => '- ' . $this->t('Select') . ' -',
      '#default_value' => $value['unit'] ?? '',
      '#required' => $element['#required'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterRender($value) {
    $formatted = number_format(
      $value['value'],
      ceil($value['value']) > $value['value'] ? 2 : 0,
      $this->configuration['decimal_separator'],
      $this->configuration['thousands_separator']
    );

    switch ($value['unit']) {
      case 'gr':
        return $this->t('@value gr.', ['@value' => $formatted], ['context' => 'weight properties value type']);

      case 'kg':
        return $this->t('@value kg', ['@value' => $formatted], ['context' => 'weight properties value type']);
    }

    return $formatted;
  }

}
