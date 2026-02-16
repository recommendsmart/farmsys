<?php

namespace Drupal\designs\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

trait FormTrait {

  /**
   * Get the wrapper identifier.
   *
   * @param array $parents
   *   The parents of the render element.
   * @param string $suffix
   *   The suffix for the element.
   *
   * @return string
   *   The wrapper identifier.
   */
  protected static function getElementId(array $parents, string $suffix = '-wrapper') {
    return implode('-', $parents) . $suffix;
  }

  /**
   * Provides functionality to support ajax within the plugin.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    $parents = $triggered_element['#wrapper_parents'];
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Provides functionality to support multistep forms within the object.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
