<?php

/**
 * @file
 * Provides views data for config_terms.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 */
function config_terms_views_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'entity_reference' && $field_storage->getSetting('target_type') == 'config_terms_term') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['filter']) && $field_name != 'delta') {
          $data[$table_name][$field_name]['filter']['id'] = 'config_terms_index';
        }
      }
    }
  }
}
