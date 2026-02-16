<?php

namespace Drupal\properties_field\Plugin\PropertiesValueType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the size properties value type plugin.
 *
 * @PropertiesValueType(
 *   id = "size",
 *   label = @Translation("Size"),
 * )
 */
class SizeValueType extends DecimalValueType {

  /**
   * {@inheritdoc}
   */
  public function widgetForm(array $element, $value, FormStateInterface $form_state) {
    $element['#type'] = 'container';
    $element['#attributes']['class'][] = 'container-inline';

    $element['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Length'),
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
        'cm' => $this->t('Centimeter'),
        'm' => $this->t('Meter'),
        'km' => $this->t('Kilometer'),
        'inch' => $this->t('Inch'),
        'feet' => $this->t('Feet'),
        'mile' => $this->t('Mile'),
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
      case 'cm':
        return $this->t('@value cm', ['@value' => $formatted], ['context' => 'size properties value type']);

      case 'm':
        return $this->t('@value m', ['@value' => $formatted], ['context' => 'size properties value type']);

      case 'km':
        return $this->t('@value km', ['@value' => $formatted], ['context' => 'size properties value type']);

      case 'inch':
        return $this->t('@value inch', ['@value' => $formatted], ['context' => 'size properties value type']);

      case 'feet':
        return $this->t('@value feet', ['@value' => $formatted], ['context' => 'size properties value type']);

      case 'mile':
        return $this->t('@value mile', ['@value' => $formatted], ['context' => 'size properties value type']);
    }

    return $formatted;
  }

}
