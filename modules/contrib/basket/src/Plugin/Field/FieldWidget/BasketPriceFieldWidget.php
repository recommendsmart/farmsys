<?php

namespace Drupal\basket\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'BasketPriceFieldWidget' widget.
 *
 * @FieldWidget(
 *   id = "BasketPriceFieldWidget",
 *   label = @Translation("Basket Price Field Widget"),
 *   field_types = {
 *     "basket_price_field"
 *   }
 * )
 */
class BasketPriceFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_old_price'    => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['hide_old_price'] = [
      '#type'         => 'checkbox',
      '#title'        => \Drupal::service('Basket')->Translate()->t('Hide old price field'),
      '#default_value' => $this->getSetting('hide_old_price'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary['text'] = \Drupal::service('Basket')->Translate()->t('Hide old price field');
    $summary['text'] .= ': ' . (!empty($this->getSetting('hide_old_price')) ? t('yes') : t('no'));
    return $summary;
  }

  /**
   * Define the form for the field type.
   *
   * Inside this method we can define the form used to edit the field type.
   *
   * Here there is a list of allowed element types: https://goo.gl/XVd4tA
   */
  public function formElement(FieldItemListInterface $items, $delta, Array $element, Array &$form, FormStateInterface $formState) {
    $element_parents = !empty($element['#field_parents']) ? $element['#field_parents'] : [];
    $element_parents[] = $this->fieldDefinition->getName();
    $element_parents[] = $delta;
    $element += [
      '#type'             => 'item',
      'table'             => [
        '#type'             => 'table',
        '#attributes'       => [
          'style'             => 'width:auto;',
        ], [
          'old_value'         => [
            '#type'         => !empty($this->getSetting('hide_old_price')) ? 'hidden' : 'number',
            '#step'         => 0.01,
            '#min'          => 0,
            '#parents'      => $element_parents + ['value' => 'old_value'],
            '#default_value' => isset($items[$delta]->old_value) ? $items[$delta]->old_value : NULL,
            '#attributes'   => [
              'placeholder'   => \Drupal::service('Basket')->Translate()->t('Price old'),
            ],
          ],
          'value'         => [
            '#type'         => 'number',
            '#step'         => 0.01,
            '#min'          => 0,
            '#parents'      => $element_parents + ['value' => 'value'],
            '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
            '#attributes'   => [
              'placeholder'   => \Drupal::service('Basket')->Translate()->t('Price'),
            ],
          ],
          'currency'      => [
            '#type'         => 'select',
            '#options'      => \Drupal::service('Basket')->Currency()->getOptions(),
            '#parents'      => $element_parents + ['currency' => 'currency'],
            '#default_value' => isset($items[$delta]->currency) ? $items[$delta]->currency : NULL,
          ],
        ],
      ],
    ];
    return $element;
  }

}
