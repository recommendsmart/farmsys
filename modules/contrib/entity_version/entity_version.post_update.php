<?php

/**
 * @file
 * Entity version post updates.
 */

declare(strict_types = 1);

/**
 * Migrate to configuration-based version field settings.
 */
function entity_version_post_update_configure_settings() {
  // Get entity types and bundles where the entity_version field is present.
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $versioned_entity_types = $entity_field_manager->getFieldMapByFieldType('entity_version');
  $version_settings_storage = \Drupal::entityTypeManager()->getStorage('entity_version_settings');
  $entity_bundles_with_field = [];

  // Collect entity types and bundles that have entity version field.
  foreach ($versioned_entity_types as $entity_type_id => $fields) {
    $entity_bundles_with_field[$entity_type_id] = [];
    foreach ($fields as $field_name => $bundle_info) {
      if (!empty($bundle_info['bundles'])) {
        $entity_bundles_with_field[$entity_type_id] = array_merge($entity_bundles_with_field[$entity_type_id], $bundle_info['bundles']);
      }
    }
  }

  // Loop through the collected entity types and bundles and load the field
  // definitions. We need to ensure that we retrieve the version field the
  // same way as we did before the config entity was in place.
  foreach ($entity_bundles_with_field as $entity_type_id => $bundles) {
    foreach ($bundles as $bundle) {
      if ($version_settings_storage->load("$entity_type_id.$bundle")) {
        continue;
      }

      $field_definitions = $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle);

      /** @var \Drupal\field\FieldConfigInterface $field */
      foreach ($field_definitions as $field) {
        if ($field->getType() === 'entity_version') {
          // Create the entity version setting.
          $version_settings_storage->create([
            'target_entity_type_id' => $entity_type_id,
            'target_bundle' => $bundle,
            'target_field' => $field->getName(),
          ])->save();

          break;
        }
      }
    }
  }
}
