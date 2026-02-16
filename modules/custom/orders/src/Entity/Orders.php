<?php

namespace Drupal\orders\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Orders entity.
 *
 * @ingroup orders
 *
 * @ContentEntityType(
 *   id = "orders",
 *   label = @Translation("Orders"),
 *   bundle_label = @Translation("Orders type"),
 *   handlers = {
 *     "storage" = "Drupal\orders\OrdersStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\orders\OrdersListBuilder",
 *     "views_data" = "Drupal\orders\Entity\OrdersViewsData",
 *     "translation" = "Drupal\orders\OrdersTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\orders\Form\OrdersForm",
 *       "add" = "Drupal\orders\Form\OrdersForm",
 *       "edit" = "Drupal\orders\Form\OrdersForm",
 *       "delete" = "Drupal\orders\Form\OrdersDeleteForm",
 *     },
 *     "access" = "Drupal\orders\OrdersAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\orders\OrdersHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "orders",
 *   data_table = "orders_field_data",
 *   revision_table = "orders_revision",
 *   revision_data_table = "orders_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer orders entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/agerp/orders/{orders}",
 *     "add-page" = "/admin/agerp/orders/add",
 *     "add-form" = "/admin/agerp/orders/add/{orders_type}",
 *     "edit-form" = "/admin/agerp/orders/{orders}/edit",
 *     "delete-form" = "/admin/agerp/orders/{orders}/delete",
 *     "version-history" = "/admin/agerp/orders/{orders}/revisions",
 *     "revision" = "/admin/agerp/orders/{orders}/revisions/{orders_revision}/view",
 *     "revision_revert" = "/admin/agerp/orders/{orders}/revisions/{orders_revision}/revert",
 *     "revision_delete" = "/admin/agerp/orders/{orders}/revisions/{orders_revision}/delete",
 *     "translation_revert" = "/admin/agerp/orders/{orders}/revisions/{orders_revision}/revert/{langcode}",
 *     "collection" = "/admin/agerp/orders",
 *   },
 *   bundle_entity_type = "orders_type",
 *   field_ui_base_route = "entity.orders_type.edit_form"
 * )
 */
class Orders extends RevisionableContentEntityBase implements OrdersInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $date = strtotime($this->date->value);
    $this->set('series', date('Y', $date));

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the orders owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
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
  public function setName($name) {
    $this->set('name', $name);
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Orders entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Orders entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
      $fields['date'] = BaseFieldDefinition::create('datetime')
        ->setLabel(t('Date'))
        ->setDescription(t('Date of the invoice.'))
        ->setSetting('datetime_type', 'date')
        ->setDefaultValue('')
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => -4,
          'type' => 'datetime_default',
        ])
        ->setDisplayOptions('view', [
          'label' => 'above',
          'type' => 'datetime_default',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['number'] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Number'))
        ->setDisplayOptions('form', [
          'label' => 'above',
          'type' => 'number',
          'weight' => -4,
        ])
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['series'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Series'))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'weight' => 0,
        ))
        ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Orders is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

      // Customer info.
      $fields['customer_id'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Customer ID'))
        ->setSettings(['max_length' => 16, 'text_processing' => 0])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'type' => 'string_textfield',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      $fields['customer_name'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Customer name'))
        ->setSettings(['max_length' => 16])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'type' => 'string_textfield',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      $fields['customer_address'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('Customer address'))
        ->setSettings(['max_length' => 256, 'text_processing' => 1])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      // Provider info.
      $fields['provider_id'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Provider ID'))
        ->setSettings(['max_length' => 16, 'text_processing' => 0])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'type' => 'string_textfield',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      $fields['provider_name'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Provider name'))
        ->setSettings(['max_length' => 16])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'type' => 'string_textfield',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      $fields['provider_address'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('Provider address'))
        ->setSettings(['max_length' => 256, 'text_processing' => 1])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      // Subtotal.
      $fields['sub_total'] = BaseFieldDefinition::create('decimal')
        ->setLabel(t('Subtotal'))
        ->setSettings(['precision' => 32, 'scale' => 2])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      $fields['gst'] = BaseFieldDefinition::create('decimal')
        ->setLabel(t('GST'))
        ->setSettings(['precision' => 32, 'scale' => 2])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      $fields['total'] = BaseFieldDefinition::create('decimal')
        ->setLabel(t('Total'))
        ->setSettings(['precision' => 32, 'scale' => 2])
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

      // General.
      $fields['comments'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('Comments'))
        ->setSettings(['max_length' => 256, 'text_processing' => 1])
        ->setDisplayOptions('form', ['label' => 'above'])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', [
          'label' => 'above',
          'weight' => 5,
        ])
        ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }
}
