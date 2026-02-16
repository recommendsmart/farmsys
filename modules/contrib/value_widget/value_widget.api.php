<?php

/**
 * @file
 * Definition for call provided by the Value Widget module.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * My value widget callback.
 *
 * @param FieldableEntityInterface $entity
 *   The entity.
 * @param FieldItemListInterface $items
 *   The field items.
 * @param array $element
 *   The form element.
 * @param FormStateInterface $formState
 *   The form state.
 * @param array $form
 *   The complete form.
 *
 * @return array
 *   The value to set on the field. Keys are ignored, and value are expected to
 *   be in delta order.
 */
function _my_value_widget_callback(FieldableEntityInterface $entity, FieldItemListInterface $items, array $element, FormStateInterface $formState, array $form) {
  // Populate the field with the value of a timestamp from 2 weeks ago.
  return [
    ['value' => strtotime('now -2 weeks')],
  ];
}
