<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'friggeri_cv_profile_entity_box' field type.
 *
 * @FieldType(
 *   id = "friggeri_cv_profile_entity_box",
 *   label = @Translation("Profile Entity Box"),
 *   category = @Translation("Friggeri CV"),
 *   default_widget = "friggeri_cv_profile_entity_box_default",
 *   default_formatter = "friggeri_cv_profile_entity_box_default",
 *   list_class = "Drupal\core\Field\FieldItemList",
 *   constraints = {"friggeri_cv_profile_entity_box_constraint" = {}}
 * )
 */
class ProfileEntityBoxItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $tenure = $this->get('tenure')->getValue();
    $title = $this->get('title')->getValue();
    $employer = $this->get('employer')->getValue();
    $domain = $this->get('domain')->getValue();
    $info = $this->get('info')->getValue();
    return empty($tenure) && empty($title) && empty($employer) && empty($domain) && empty($info);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];
    $properties['tenure'] = DataDefinition::create('string')
      ->setLabel(t('Tenure'));
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title or position'));
    $properties['employer'] = DataDefinition::create('string')
      ->setLabel(t('Employer'));
    $properties['domain'] = DataDefinition::create('string')
      ->setLabel(t('The domain or subject'));
    $properties['info'] = DataDefinition::create('string')
      ->setLabel(t('Additional information'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    return [
      'columns' => [
        'tenure' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'title' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'employer' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'domain' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'info' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

}
