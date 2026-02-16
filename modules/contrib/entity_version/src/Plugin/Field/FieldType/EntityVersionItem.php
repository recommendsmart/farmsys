<?php

declare(strict_types = 1);

namespace Drupal\entity_version\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'entity_version' field type.
 *
 * @FieldType(
 *   id = "entity_version",
 *   label = @Translation("Entity version"),
 *   module = "entity_version",
 *   description = @Translation("Stores the version of the entity."),
 *   default_formatter = "entity_version",
 *   default_widget = "entity_version"
 * )
 */
class EntityVersionItem extends FieldItemBase implements EntityVersionItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'major' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'minor' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'patch' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // We consider the field empty if either of these properties left empty.
    $major = $this->get('major')->getValue();
    $minor = $this->get('minor')->getValue();
    $patch = $this->get('patch')->getValue();

    return $major === NULL || $major === '' || $minor === NULL || $minor === '' || $patch === NULL || $patch === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['major'] = DataDefinition::create('integer')
      ->setLabel(t('Major number'));
    $properties['minor'] = DataDefinition::create('integer')
      ->setLabel(t('Minor number'));
    $properties['patch'] = DataDefinition::create('integer')
      ->setLabel(t('Patch number'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Created fields default to zero.
    $this->setValue(
      [
        'major' => 0,
        'minor' => 0,
        'patch' => 0,
      ],
      $notify
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function increase(string $category): void {
    $value = $this->get($category)->getValue();
    $this->set($category, ($value + 1));
  }

  /**
   * {@inheritdoc}
   */
  public function decrease(string $category): void {
    $value = $this->get($category)->getValue();
    $this->set($category, empty($value) ? 0 : ($value - 1));
  }

  /**
   * {@inheritdoc}
   */
  public function reset(string $category): void {
    $this->set($category, 0);
  }

}
