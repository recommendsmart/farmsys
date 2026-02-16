<?php

namespace Drupal\contact_default_fields_override;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\Markup;

/**
 * Class Element.
 *
 * Defines the #pre_render callback.
 *
 * @package Drupal\contact_default_fields_override
 */
class Element implements TrustedCallbackInterface {

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRender',
    ];
  }

  /**
   * Overrides the element titles.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The processed element.
   */
  public static function preRender(array $element) {

    if (!isset($element['#contact_default_fields_override_bundle'])) {
      return $element;
    }

    $fields_to_override = contact_default_fields_override_get_fields_to_override();
    $field_to_override = $element['#parents'][0];

    if (!in_array($field_to_override, $fields_to_override, FALSE)) {
      return $element;
    }

    $currentLanguageId = \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();

    /**
     * @var \Drupal\contact\Entity\ContactForm $contactForm
     */
    $contactForm = \Drupal::entityTypeManager()
      ->getStorage('contact_form')
      ->load($element['#contact_default_fields_override_bundle']);

    if (!$contactForm) {
      return $element;
    }

    $settings = $contactForm->getThirdPartySettings('contact_default_fields_override');

    if (isset($settings[$field_to_override . '_label_' . $currentLanguageId])) {
      $element['#title'] = Markup::create($settings[$field_to_override . '_label_' . $currentLanguageId]);
    }
    if (isset($settings[$field_to_override . '_description_' . $currentLanguageId])) {
      $element['#description'] = Markup::create($settings[$field_to_override . '_description_' . $currentLanguageId]);
    }
    if (isset($settings[$field_to_override . '_required'])) {
      $element['#required'] = $settings[$field_to_override . '_required'];
    }

    return $element;
  }

}
