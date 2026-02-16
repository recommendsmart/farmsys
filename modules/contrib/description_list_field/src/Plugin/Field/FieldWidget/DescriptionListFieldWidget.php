<?php

declare(strict_types = 1);

namespace Drupal\description_list_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Description list widget' widget.
 *
 * @FieldWidget(
 *   id = "description_list_widget",
 *   label = @Translation("Description list widget"),
 *   field_types = {
 *     "description_list_field"
 *   }
 * )
 */
class DescriptionListFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term'),
      '#default_value' => $items[$delta]->term ?? NULL,
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => $element['#required'],
    ];
    $element['description'] = [
      '#type' => 'text_format',
      '#base_type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $items[$delta]->description ?? NULL,
      '#format' => $items[$delta]->format ?? filter_fallback_format(),
      '#rows' => 5,
      '#required' => $element['#required'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item['format'] = $item['description']['format'];
      $item['description'] = $item['description']['value'];
    }

    return $values;
  }

}
