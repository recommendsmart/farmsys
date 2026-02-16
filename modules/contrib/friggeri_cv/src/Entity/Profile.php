<?php

namespace Drupal\friggeri_cv\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\friggeri_cv\ProfileInterface;

/**
 * Defines the profile entity class.
 *
 * @ContentEntityType(
 *   id = "profile",
 *   label = @Translation("Profile"),
 *   label_collection = @Translation("Profiles"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\friggeri_cv\ProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\friggeri_cv\ProfileAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\friggeri_cv\Form\ProfileForm",
 *       "edit" = "Drupal\friggeri_cv\Form\ProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "profile",
 *   data_table = "profile_field_data",
 *   translatable = TRUE,
 *   admin_permission = "access profile overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/profile/add",
 *     "canonical" = "/friggeri-cv/profile/{profile}",
 *     "edit-form" = "/admin/profile/{profile}/edit",
 *     "delete-form" = "/admin/profile/{profile}/delete",
 *     "collection" = "/admin/profiles"
 *   },
 * )
 */
class Profile extends ContentEntityBase implements ProfileInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get("name")->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set("name", $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get("title")->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($name) {
    $this->set("title", $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultPicture() {
    $img = base_path() . drupal_get_path("module", "friggeri_cv")
      . "/img/person.svg";
    return [
      "data" => [
        "#markup" => "<img src='$img'>",
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPicture() {
    $media = $this->picture->entity;
    if (isset($media)) {
      $image = $media->field_media_image->entity;
      if (isset($image)) {
        return [
          "data" => [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $image->getFileUri(),
          ],
        ];
      }
    }

    return $this->getDefaultPicture();
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    return $this->get('sections')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    foreach ($entities as $entity) {
      foreach ($entity->getSections() as $section) {
        $section->delete();
      }
      foreach ($entity->get('footer_col_1_items')->referencedEntities() as $item) {
        $item->delete();
      }
      foreach ($entity->get('footer_col_2_items')->referencedEntities() as $item) {
        $item->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('The profile name'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'placeholder' => "John SMITH",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('The professional title'))
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'placeholder' => "Goldsmith",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['picture'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Picture'))
      ->setDescription(new TranslatableMarkup('The profile picture'))
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

    $fields['contact_box'] = BaseFieldDefinition::create('friggeri_cv_profile_contact_box')
      ->setLabel(new TranslatableMarkup('Sidebar Items'))
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'friggeri_cv_profile_contact_box_default',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sections'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Sections'))
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'profile_section')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
      ]);

    // Footer.
    $fields['footer_col_1_title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('The title of the list'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'placeholder' => "ex. Languages",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['footer_col_1_title_color'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title color'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'color_picker',
        'settings' => [
          'default_color' => "#ac0707",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['footer_col_1_colored_letter_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('# of colored letters'))
      ->setSettings(['min' => 0, 'max' => 25])
      ->setDefaultValue(3)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['footer_col_1_items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Items'))
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'profile_footer_item')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
      ]);

    $fields['footer_col_2_title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('The title of the list'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'placeholder' => "ex. Hobbies",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['footer_col_2_title_color'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title color'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'color_picker',
        'settings' => [
          'default_color' => "#ac0707",
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['footer_col_2_colored_letter_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('# of colored letters'))
      ->setSettings(['min' => 0, 'max' => 25])
      ->setDefaultValue(3)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['footer_col_2_items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Items'))
      ->setTranslatable(TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'profile_footer_item')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
      ]);

    return $fields;
  }

}
