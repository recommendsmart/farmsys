<?php

namespace Drupal\field_suggestion\Element;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Class FieldSuggestionOperations.
 *
 * @package Drupal\field_suggestion\Element
 */
class FieldSuggestionOperations implements TrustedCallbackInterface {

  /**
   * #pre_render callback to associate the appropriate cache tag.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with all group members.
   */
  public static function preRender(array $element) {
    $element['#cache']['tags'][] = 'field_suggestion_operations';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

}
