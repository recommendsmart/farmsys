<?php

namespace Drupal\ingredient\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * MigrateField plugin for Drupal 7 ingredient_reference fields.
 *
 * @MigrateField(
 *   id = "ingredient",
 *   type_map = {
 *     "ingredient_reference" = "ingredient",
 *   },
 *   core = {7},
 *   source_module = "recipe",
 *   destination_module = "ingredient"
 * )
 */
class IngredientReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    parent::alterFieldInstanceMigration($migration);

    $process = [
      'plugin' => 'get',
      'source' => 'widget/settings/default_unit',
    ];
    $migration->mergeProcessOfProperty('settings/default_unit', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return ['recipe_ingredient_autocomplete' => 'ingredient_autocomplete'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return ['recipe_ingredient_default' => 'ingredient_default'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    parent::alterFieldFormatterMigration($migration);

    $process = [
      'plugin' => 'get',
      'source' => 'formatter/settings/unit_abbreviation',
    ];
    $migration->mergeProcessOfProperty('options/settings/unit_display', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => [
          'plugin' => 'migration_lookup',
          'migration' => 'recipe1x_ingredient',
          'source' => 'iid',
        ],
        'quantity' => 'quantity',
        'unit_key' => 'unit_key',
        'note' => 'note',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }
}
