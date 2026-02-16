<?php

/**
 * @file
 * Hooks specific to the Field Bundle module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define a string representation for the given field bundle.
 *
 * In case the hook implementation returns an empty string, a fallback value
 * will be generated, or another module might generate the value.
 *
 * @param \Drupal\field_bundle\FieldBundleInterface $field_bundle
 *   The field bundle.
 * @param string $string
 *   The current value of the string representation.
 *
 * @return string
 *   The generated string representation.
 *
 * @see \Drupal\field_bundle\FieldBundleInterface::getStringRepresentation()
 */
function hook_field_bundle_get_string_representation(\Drupal\field_bundle\FieldBundleInterface $field_bundle, $string) {
  if ($field_bundle->isNew()) {
    return 'NEW - ' . $field_bundle->get('my_custom_field')->value;
  }
  return $field_bundle->get('my_custom_field')->value;
}

/**
 * @} End of "addtogroup hooks".
 */
