<?php

namespace Drupal\properties_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides the properties field type.
 *
 * @FieldType(
 *   id = "properties",
 *   label = @Translation("Properties"),
 *   description = @Translation("An entity field containing a properties value."),
 *   category = @Translation("General"),
 *   default_widget = "properties_default",
 *   default_formatter = "properties_table",
 *   constraints = {"UniqueProperties" = {}},
 *   cardinality = \Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
 * )
 */
class PropertiesItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach (['machine_name', 'label', 'type', 'value'] as $name) {
      if (!isset($this->values[$name]) || (empty($this->values[$name]) && $this->values[$name] != '0')) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['machine_name'] = DataDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setRequired(TRUE);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE);

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);

    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'machine_name' => [
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
        ],
        'label' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 50,
          'not null' => TRUE,
        ],
        'value' => [
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

}
