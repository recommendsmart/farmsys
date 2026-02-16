<?php

namespace Drupal\Tests\field_fallback\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the field fallback config class.
 *
 * @group field_fallback
 */
class FieldFallbackConfigTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_fallback',
    'text',
    'field',
    'node',
    'user',
    'filter',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('date_format');
    $this->installConfig('filter');
    $this->installConfig('node');

    $this->createContentType([
      'type' => 'page',
      'name' => 'Page',
    ]);

    // Create primary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_primary',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_primary',
      'bundle' => 'page',
      'label' => 'Primary',
    ])->save();

    // Create secondary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_secondary',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_secondary',
      'bundle' => 'page',
      'label' => 'Secondary',
      'widget' => [
        'type' => 'text_textfield',
        'weight' => 0,
      ],
      'third_party_settings' => [
        'field_fallback' => [
          'field' => 'field_primary',
          'converter' => 'default',
        ],
      ],
    ])->save();

    // Create primary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_primary2',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_primary2',
      'bundle' => 'page',
      'label' => 'Primary 2',
    ])->save();

    // Create secondary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_secondary2',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_secondary2',
      'bundle' => 'page',
      'label' => 'Secondary 2',
      'widget' => [
        'type' => 'text_textfield',
        'weight' => 0,
      ],
      'third_party_settings' => [
        'field_fallback' => [
          'field' => 'field_primary2',
          'converter' => 'default',
        ],
      ],
    ])->save();

    // Create a fallback title field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_fallback_title',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_fallback_title',
      'bundle' => 'page',
      'label' => 'Fallback title',
      'widget' => [
        'type' => 'text_textfield',
        'weight' => 0,
      ],
      'third_party_settings' => [
        'field_fallback' => [
          'field' => 'title',
          'converter' => 'default',
        ],
      ],
    ])->save();
  }

  /**
   * Test the config cleanup when a field is deleted.
   */
  public function testFieldConfigDeletionCleanup() {
    // Delete the fallback field of one of the fields.
    $field = FieldConfig::loadByName('node', 'page', 'field_primary2');
    $field->delete();

    // Check that the field was cleared from the config, since it no longer
    // exists.
    $fallback_field = $this->config('field.field.node.page.field_secondary2')
      ->get('third_party_settings.field_fallback');
    $this->assertEmpty($fallback_field);

    // Check that the value for the other field is still present.
    $fallback_field = $this->config('field.field.node.page.field_secondary')
      ->get('third_party_settings.field_fallback');
    $this->assertEquals([
      'field' => 'field_primary',
      'converter' => 'default',
    ], $fallback_field);
  }

  /**
   * Test the config cleanup when a base field is deleted.
   */
  public function testBaseFieldDeletionCleanup() {
    $update_manager = \Drupal::entityDefinitionUpdateManager();
    $base_fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('node');
    $update_manager->uninstallFieldStorageDefinition($base_fields['title']);

    // Check that the field was cleared from the config, since it no longer
    // exists.
    $fallback_field = $this->config('field.field.node.page.field_fallback_title')
      ->get('third_party_settings.field_fallback');
    $this->assertEmpty($fallback_field);
  }

}
