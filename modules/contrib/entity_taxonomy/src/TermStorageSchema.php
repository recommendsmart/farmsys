<?php

namespace Drupal\entity_taxonomy;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the term schema handler.
 */
class TermStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        'entity_taxonomy_term__tree' => ['vid', 'weight', 'name'],
        'entity_taxonomy_term__vid_name' => ['vid', 'name'],
      ];
    }

    $schema['entity_taxonomy_index'] = [
      'description' => 'Maintains denormalized information about node/term relationships.',
      'fields' => [
        'entity_id' => [
          'description' => 'The entity_id this record tracks.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'entity_type_id' => [
          'description' => 'The entity_type_id this record tracks.',
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'tid' => [
          'description' => 'The term ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ]
      ],
      'primary key' => ['entity_id','entity_type_id', 'tid'],
      'indexes' => [
        'term_entity' => ['entity_id','entity_type_id', 'tid'],
      ],
      'foreign keys' => [
        'term' => [
          'table' => 'entity_taxonomy_term_data',
          'columns' => ['tid' => 'tid'],
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'entity_taxonomy_term_field_data') {
      // Remove unneeded indexes.
      unset($schema['indexes']['entity_taxonomy_term_field__vid__target_id']);
      unset($schema['indexes']['entity_taxonomy_term_field__description__format']);

      switch ($field_name) {
        case 'weight':
          // Improves the performance of the entity_taxonomy_term__tree index defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'name':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, ContentEntityTypeInterface $entity_type = NULL) {
    $dedicated_table_schema = parent::getDedicatedTableSchema($storage_definition, $entity_type);

    // Add an index on 'bundle', 'delta' and 'parent_target_id' columns to
    // increase the performance of the query from
    // \Drupal\entity_taxonomy\TermStorage::getVocabularyHierarchyType().
    if ($storage_definition->getName() === 'parent') {
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->storage->getTableMapping();
      $dedicated_table_name = $table_mapping->getDedicatedDataTableName($storage_definition);

      unset($dedicated_table_schema[$dedicated_table_name]['indexes']['bundle']);
      $dedicated_table_schema[$dedicated_table_name]['indexes']['bundle_delta_target_id'] = [
        'bundle',
        'delta',
        $table_mapping->getFieldColumnName($storage_definition, 'target_id'),
      ];
    }

    return $dedicated_table_schema;
  }

}
