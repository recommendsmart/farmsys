<?php

namespace Drupal\typed_telephone\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'typed_telephone_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "typed_telephone_default_widget",
 *   module = "typed_telephone",
 *   label = @Translation("Typed telephone default"),
 *   field_types = {
 *     "typed_telephone"
 *   }
 * )
 */
class TypedTelephoneWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of telephone textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Telephone textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['details'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
      '#description' => $element['#description'],
    ] + $element;

    $config_helper = \Drupal::service('typed_telephone.confighelper');

    $element['details']['teltype'] = [
      '#type' => 'select',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#options' => $config_helper->getTypesAsOptions($this->getFieldSetting('allowed_types')),
      '#required' => $element['#required'],
    ];

    $element['details']['value'] = [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#size' => $this->getSetting('size'),
      '#required' => $element['#required'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['teltype'] = $value['details']['teltype'];
      $value['value'] = $value['details']['value'];
      unset($value['details']);
    }
    return $values;
  }

}
