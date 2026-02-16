<?php

namespace Drupal\entity_logger\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Defines the log entry entity class.
 *
 * @ContentEntityType(
 *   id = "entity_log_entry",
 *   label = @Translation("Log entry"),
 *   label_singular = @Translation("log entry"),
 *   label_plural = @Translation("log entries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count log entry",
 *     plural = "@count log entries",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\entity_logger\EntityLogEntryAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\entity_logger\Form\EntityLogEntryForm",
 *       "edit" = "Drupal\entity_logger\Form\EntityLogEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\entity_logger\EntityLogEntryListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "storage" = "Drupal\entity_logger\EntityLogEntryStorage",
 *     "view_builder" = "Drupal\entity_logger\EntityLogEntryViewBuilder",
 *     "views_data" = "\Drupal\entity_logger\EntityLogEntryViewsData",
 *   },
 *   base_table = "entity_logger",
 *   internal = TRUE,
 *   admin_permission = "administer entity log entries",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid"
 *   },
 *   links = {
 *     "add-form" = "/entity_logger/{entity_type}/{entity}/add",
 *     "edit-form" = "/entity_logger/{entity_log_entry}/edit",
 *     "delete-form" = "/entity_logger/{entity_log_entry}/delete",
 *     "collection" = "/admin/structure/entity_logger"
 *   },
 *   field_ui_base_route = "entity.entity_log_entry.collection"
 * )
 */
class EntityLogEntry extends ContentEntityBase implements EntityLogEntryInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    $entity_label = $this->getTargetEntity() ? $this->getTargetEntity()->label() : '';
    return $this->t('Log #@id for entity @entity', [
      '@id' => $this->id(),
      '@entity' => $entity_label,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity() {
    return $this->get('target_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntity(EntityInterface $entity) {
    $this->set('target_entity', [
      'target_type' => $entity->getEntityTypeId(),
      'target_id' => $entity->id(),
    ]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return $this->get('severity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverity($severity) {
    $this->set('severity', $severity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message, array $context = []) {
    $this->set('message', $message);
    $this->set('context', $context);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if (!$this->get('context')->isEmpty()) {
      return $this->get('context')->first()->getValue();
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(array $context) {
    $this->set('context', $context);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user creating the log entry.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Target entity'))
      ->setDescription(t('The entity this log entry belongs to.'));

    $fields['severity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Severity'))
      ->setDescription(t('The severity of the log message.'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'tiny')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Log message'))
      ->setDescription(t('The log message'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['context'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Message context'))
      ->setDescription(t('A serialized array of context parameters for the log message.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the log was created.'));

    return $fields;
  }

}
