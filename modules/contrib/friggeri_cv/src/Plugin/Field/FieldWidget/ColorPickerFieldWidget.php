<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'color_picker' widget.
 *
 * @FieldWidget(
 *   id = "color_picker",
 *   label = @Translation("Color picker"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ColorPickerFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_color' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['default_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default color'),
      '#default_value' => $this->getSetting('default_color'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if (!empty($this->getSetting('default_color'))) {
      $summary[] = $this->t('Default color: @placeholder', [
        '@placeholder' => $this->getSetting('default_color'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'color',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : $this->getSetting('default_color'),
    ];

    return $element;
  }

}
