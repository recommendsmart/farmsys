<?php

namespace Drupal\entity_taxonomy\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "entity_taxonomy_term_reference",
 *   type_map = {
 *     "entity_taxonomy_term_reference" = "entity_reference"
 *   },
 *   core = {6,7},
 *   source_module = "entity_taxonomy",
 *   destination_module = "core",
 * )
 */
class entity_taxonomyTermReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'entity_taxonomy_term_reference_link' => 'entity_reference_label',
      'entity_taxonomy_term_reference_plain' => 'entity_reference_label',
      'entity_taxonomy_term_reference_rss_category' => 'entity_reference_label',
      'i18n_entity_taxonomy_term_reference_link' => 'entity_reference_label',
      'entityreference_entity_view' => 'entity_reference_entity_view',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => 'tid',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
