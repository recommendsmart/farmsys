<?php

/**
 * @file
 * Contains \Drupal\resource\Entity\Resource.
 */

namespace Drupal\resource\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\resource\ResourceInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Resource entity.
 *
 * @ingroup resource
 *
 * @ContentEntityType(
 *   id = "resource",
 *   label = @Translation("Resource"),
 *   bundle_label = @Translation("Resource type"),
 *   handlers = {
 *     "storage" = "Drupal\resource\ResourceStorage",
 *     "storage_schema" = "Drupal\resource\ResourceStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\resource\ResourceAccessControlHandler",
 *     "views_data" = "Drupal\resource\ResourceViewsData",
 *     "form" = {
 *       "default" = "Drupal\resource\Form\ResourceForm",
 *       "edit" = "Drupal\resource\Form\ResourceForm",
 *       "delete" = "Drupal\resource\Form\ResourceDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\resource\ResourceListBuilder",
 *   },
 *   base_table = "resource",
 *   data_table = "resource_field_data",
 *   revision_table = "resource_revision",
 *   revision_data_table = "resource_field_revision",
 *   admin_permission = "administer resource",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uid" = "uid",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "resource_type",
 *   field_ui_base_route = "entity.resource_type.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/resource/{resource}",
 *     "edit-form" = "/resource/{resource}/edit",
 *     "delete-form" = "/resource/{resource}/delete"
 *   }
 * )
 */
class Resource extends ContentEntityBase implements ResourceInterface {

  use EntityChangedTrait;

  protected $tokenService;

  public function __construct(array $values, $entity_type, $bundle, array $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->tokenService = \Drupal::token();
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    // Set default value for name and active properties.
    if (!empty($values['type'])) {
      $type = \Drupal::entityManager()
        ->getStorage('resource_type')
        ->load($values['type']);
      $values += [
        'name' => $type->getNamePattern(),
        'active' => $type->isAutomaticallyActive(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $type = \Drupal::entityManager()
      ->getStorage('resource_type')
      ->load($this->getType());
    if (!$type->isNameEditable() && $this->isNew()) {
      // Pass in an empty bubblable metadata object, so we can avoid starting a
      // renderer, for example if this happens in a REST resource creating
      // context.
      $bubbleable_metadata = new BubbleableMetadata();
      $this->set('name', $this->tokenService->replace(
        $type->getNamePattern(),
        ['resource' => $this],
        [],
        $bubbleable_metadata
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return $this->get('type')->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * @return array
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

  /**
   * @return array
   */
  public static function getCurrentTimestamp() {
    return array(REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Resource entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Resource entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\resource\Entity\Resource::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 99,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 99,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the resource was created.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 99,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The resource revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The resource type.'))
      ->setSetting('target_type', 'resource_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The resource language code.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date'))
      ->setDescription(t('Timestamp of the event being logged.'))
      ->setDefaultValueCallback('Drupal\resource\Entity\Resource::getCurrentTimestamp')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 80,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Boolean indicating whether the resource is active.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 10,
      ))
      ->setDisplayOptions('form', array(
        'settings' => array('display_label' => TRUE),
        'weight' => 90,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Read only.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the resource was last edited.'));
    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);
    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Resource entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
