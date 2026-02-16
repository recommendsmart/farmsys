<?php

namespace Drupal\friggeri_cv\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\friggeri_cv\ProfileSectionInterface;

/**
 * Defines the profile section entity class.
 *
 * @ContentEntityType(
 *   id = "profile_section",
 *   label = @Translation("Profile Section"),
 *   label_singular = @Translation("section"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   base_table = "profile_section",
 *   admin_permission = "access profile section overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class ProfileSection extends ContentEntityBase implements ProfileSectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityBox(array $entity_box) {
    $this->set('entity_box', $entity_box);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('The title of the experiences'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'placeholder' => "ex. Volunteering",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title_color'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title color'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'color_picker',
        'settings' => [
          'default_color' => "#ac0707",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['colored_letter_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('# of colored letters'))
      ->setSettings(['min' => 0, 'max' => 25])
      ->setDefaultValue(3)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_box'] = BaseFieldDefinition::create('friggeri_cv_profile_entity_box')
      ->setLabel(new TranslatableMarkup('Experiences'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'friggeri_cv_profile_entity_box_default',
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
