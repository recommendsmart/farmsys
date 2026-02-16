<?php

namespace Drupal\properties_field\Plugin\PropertiesValueType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\properties_field\PropertiesValueType\PropertiesValueTypeBase;

/**
 * Provides the integer properties value type plugin.
 *
 * @PropertiesValueType(
 *   id = "Integer",
 *   label = @Translation("Integer"),
 * )
 */
class IntegerValueType extends PropertiesValueTypeBase {

  /**
   * {@inheritdoc}
   */
  public function widgetForm(array $element, $value, FormStateInterface $form_state) {
    $element = parent::widgetForm($element, $value, $form_state);

    $element['#type'] = 'number';
    $element['#step'] = 1;

    return $element;
  }

}
