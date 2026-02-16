<?php

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @file
 * Post update functions for the Collection module.
 */

/**
 * Update all collection_items to update labels.
 */
function collection_post_update_collection_item_labels(&$sandbox) {
  $item_storage = \Drupal::service('entity_type.manager')->getStorage('collection_item');
  $collection_items = $item_storage->loadMultiple();

  foreach ($collection_items as $collection_item) {
    // Retain the changed time value. Note the `+ 1` hack. This is because
    // Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem::preSave() has some
    // wonky logic for automating the value to the current time.
    $changed_time = $collection_item->getChangedTime();
    $collection_item->setChangedTime($changed_time + 1);

    // This should trigger CollectionItem::preSave() and update the
    // collection_item label.
    $collection_item->save();
  }
}

/**
 * Make collection_item entities translatable.
 */
function collection_post_update_collection_item_translatable(&$sandbox) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $definition_update_manager->getEntityType('collection_item');
  $entity_type->set('translatable', TRUE);
  $entity_type->set('data_table', 'collection_item_field_data');
  $keys = $entity_type->getKeys();
  $keys['langcode'] = 'langcode';
  $entity_type->set('entity_keys', $keys);

  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('collection_item');
  $field_storage_definitions['name']->setTranslatable(TRUE);
  $field_storage_definitions['attributes']->setTranslatable(TRUE);
  $field_storage_definitions['weight']->setTranslatable(TRUE);
  $field_storage_definitions['created']->setTranslatable(TRUE);
  $field_storage_definitions['changed']->setTranslatable(TRUE);
  $field_storage_definitions['collection']->setTranslatable(FALSE);
  $field_storage_definitions['item']->setTranslatable(FALSE);

  $field_storage_definitions['langcode'] = BaseFieldDefinition::create('language')
    ->setName('langcode')
    ->setLabel(t('Language'))
    ->setTranslatable(TRUE)
    ->setRevisionable(FALSE);

  $field_storage_definitions['default_langcode'] = BaseFieldDefinition::create('boolean')
    ->setName('default_langcode')
    ->setLabel(t('Default translation'))
    ->setDescription(t('A flag indicating whether this is the default translation.'))
    ->setTranslatable(TRUE)
    ->setDefaultValue(TRUE)
    ->setInitialValue(TRUE)
    ->setRevisionable(TRUE);

  $definition_update_manager->installFieldStorageDefinition('langcode', $entity_type->id(), 'collection_item', $field_storage_definitions['langcode']);

  $definition_update_manager->installFieldStorageDefinition('default_langcode', $entity_type->id(), 'collection_item', $field_storage_definitions['default_langcode']);

  // Update the entity type and the database schema, including data migration.
  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
}

/**
 * Fix collection_item dynamic entity reference field after adding translation.
 */
function collection_post_update_collection_item_translatable_2(&$sandbox) {
  // Call the dynamic entity reference service that adds the target_id_int
  // column to the newly created 'collection_item_field_data` table (see the
  // previous post-update hook). For some reason, this field was not migrated by
  // updateFieldableEntityType.
  $der_storage_create_column_service = \Drupal::service('dynamic_entity_reference.storage.create_column');
  $der_storage_create_column_service->create('collection_item_field_data', ['item__target_id'], [
    'item__target_type' => [],
  ]);

  // Now trigger the population of the target_id_int field.
  \Drupal::database()->query('UPDATE {collection_item_field_data} SET item__target_id = item__target_id')->execute();
}

/**
 * Update the translatable properties for collection entities.
 */
function collection_post_update_collection_translatable_properties(&$sandbox) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $definition_update_manager->getEntityType('collection');

  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('collection');
  $field_storage_definitions['name']->setTranslatable(TRUE);
  $field_storage_definitions['status']->setTranslatable(TRUE);
  $field_storage_definitions['created']->setTranslatable(TRUE);
  $field_storage_definitions['changed']->setTranslatable(TRUE);

  // Update the entity type and the database schema, including data migration.
  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
}

/**
 * Make user_id on collection entities non-translatable.
 */
function collection_post_update_collection_untranslatable_user_id(&$sandbox) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $definition_update_manager->getEntityType('collection');

  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('collection');
  $field_storage_definitions['user_id']->setTranslatable(FALSE);
  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
}
