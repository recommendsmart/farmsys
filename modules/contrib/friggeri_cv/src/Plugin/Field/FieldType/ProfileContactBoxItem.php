<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'friggeri_cv_profile_contact_box' field type.
 *
 * @FieldType(
 *   id = "friggeri_cv_profile_contact_box",
 *   label = @Translation("Profile Contact Box"),
 *   category = @Translation("Friggeri CV"),
 *   default_widget = "friggeri_cv_profile_contact_box_default",
 *   default_formatter = "friggeri_cv_profile_contact_box_default",
 *   list_class = "Drupal\core\Field\FieldItemList"
 * )
 */
class ProfileContactBoxItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value1 = $this->get('heading')->getValue();
    $value2 = $this->get('font_awesome_icon')->getValue();
    $value3 = $this->get('contacts')->getValue();
    return empty($value1) && empty($value2) && empty($value3);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];
    $properties['heading'] = DataDefinition::create('string')
      ->setLabel(t('Profile contact box heading'));
    $properties['font_awesome_icon'] = DataDefinition::create('string')
      ->setLabel(t('Profile contact box font awesome icon'));
    $properties['contacts'] = DataDefinition::create('string')
      ->setLabel(t('The contact details'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    return [
      'columns' => [
        'heading' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'font_awesome_icon' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'contacts' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

}
