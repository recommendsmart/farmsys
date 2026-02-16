<?php

namespace Drupal\basket;

use Drupal\Component\Utility\NestedArray;

/**
 * {@inheritdoc}
 */
class ViewsUiAddHandler {

  /**
   * {@inheritdoc}
   */
  public static function formAlter(&$form, $form_state) {
    if (!empty($form['options']['name']['#options'])) {
      $view = $form_state->get('view');
      $viewsData = \Drupal::service('views.views_data')->getAll();
      foreach ($form['options']['name']['#options'] as $keyField => $value) {
        $fieldInfo = NestedArray::getValue($viewsData, explode('.', $keyField));
        if (!empty($fieldInfo['basket_views']) && !in_array($view->id(), $fieldInfo['basket_views'])) {
          unset($form['options']['name']['#options'][$keyField]);
        }
      }
    }
  }

}
