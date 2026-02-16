<?php

namespace Drupal\friggeri_cv\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\friggeri_cv\ProfileFooterItemInterface;

/**
 * Defines the profile section entity class.
 *
 * @ContentEntityType(
 *   id = "profile_footer_item",
 *   label = @Translation("Profile Footer Item"),
 *   label_singular = @Translation("item"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   base_table = "profile_footer_item",
 *   admin_permission = "access profile footer item overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "text",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class ProfileFooterItem extends ContentEntityBase implements ProfileFooterItemInterface {

  /**
   * {@inheritdoc}
   */
  public function getText() {
    return $this->get('text')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setText($text) {
    $this->set('text', $text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['text'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Item Text'))
      ->setDescription(new TranslatableMarkup('The text of this footer list item'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['picture'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Item icon'))
      ->setDescription(new TranslatableMarkup('The icon of this footer list item'))
      ->setRequired(FALSE)
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default:media')
      ->setSettings([
        'handler_settings' => [
          'target_bundles' => [
            'image' => 'image',
          ],
        ],
      ])
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'media_thumbnail',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ]);

    return $fields;
  }

}
