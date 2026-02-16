<?php

/**
 * @file
 * Hooks specific to the Access Records module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of available operations.
 *
 * @param array &$operations
 *   The current list of available operations.
 *
 * @see \Drupal\access_records\Entity\AccessRecord::availableOperations()
 */
function hook_access_record_operations_alter(array &$operations) {
  unset($operations['delete']);
  $operations['archive'] = t('Archive');
}

/**
 * Define a string representation for the given access record.
 *
 * In case the hook implementation returns an empty string, a fallback value
 * will be generated, or another module might generate the value.
 *
 * @param \Drupal\access_records\AccessRecordInterface $access_record
 *   The access record.
 * @param string $string
 *   The current value of the string representation.
 *
 * @return string
 *   The generated string representation.
 *
 * @see \Drupal\access_records\AccessRecordInterface::getStringRepresentation()
 */
function hook_access_record_get_string_representation(\Drupal\access_records\AccessRecordInterface $access_record, $string) {
  if ($access_record->isNew()) {
    return 'NEW - ' . $access_record->get('my_custom_field')->value;
  }
  return $access_record->get('my_custom_field')->value;
}

/**
 * @} End of "addtogroup hooks".
 */
