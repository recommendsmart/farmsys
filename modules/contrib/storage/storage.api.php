<?php

/**
 * @file
 * Hooks specific to the Storage Entities module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define a string representation for the given Storage entity.
 *
 * In case the hook implementation returns an empty string, a fallback value
 * will be generated, or another module might generate the value.
 *
 * @param \Drupal\storage\Entity\StorageInterface $storage
 *   The Storage entity.
 * @param string $string
 *   The current value of the string representation.
 *
 * @return string
 *   The generated string representation.
 *
 * @see \Drupal\storage\Entity\StorageInterface::getStringRepresentation()
 */
function hook_storage_get_string_representation(\Drupal\storage\Entity\StorageInterface $storage, $string) {
  if ($storage->isNew()) {
    return 'NEW - ' . $storage->get('my_custom_field')->value;
  }
  return $storage->get('my_custom_field')->value;
}

/**
 * @} End of "addtogroup hooks".
 */
