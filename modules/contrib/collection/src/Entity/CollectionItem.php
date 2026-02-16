<?php

namespace Drupal\collection\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\collection\Event\CollectionEvents;
use Drupal\collection\Event\CollectionItemCreateEvent;
use Drupal\collection\Event\CollectionItemUpdateEvent;
use Drupal\collection\Event\CollectionItemDeleteEvent;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Cache\Cache;

/**
 * Defines the Collection item entity.
 *
 * @ingroup collection
 *
 * @ContentEntityType(
 *   id = "collection_item",
 *   label = @Translation("Collection item"),
 *   label_collection = @Translation("Collection items"),
 *   bundle_label = @Translation("Collection item type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\collection\CollectionItemListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\collection\Form\CollectionItemForm",
 *       "add" = "Drupal\collection\Form\CollectionItemForm",
 *       "edit" = "Drupal\collection\Form\CollectionItemForm",
 *       "delete" = "Drupal\collection\Form\CollectionItemDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\collection\Form\CollectionItemDeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\collection\CollectionItemRouteProvider",
 *     },
 *     "access" = "Drupal\collection\CollectionAccessControlHandler",
 *   },
 *   base_table = "collection_item",
 *   data_table = "collection_item_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer collections",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/collection/{collection}/items/{collection_item}",
 *     "add-page" = "/collection/{collection}/items/add",
 *     "add-form" = "/collection/{collection}/items/add/{collection_item_type}",
 *     "edit-form" = "/collection/{collection}/items/{collection_item}/edit",
 *     "delete-form" = "/collection/{collection}/items/{collection_item}/delete",
 *     "delete-multiple-form" = "/collection/{collection}/items/delete",
 *     "collection" = "/collection/{collection}/items",
 *   },
 *   bundle_entity_type = "collection_item_type",
 *   field_ui_base_route = "entity.collection_item_type.edit_form",
 *   constraints = {"UniqueItem" = {}, "SingleCanonicalItem" = {}, "PreventSelf" = {}}
 * )
 */
class CollectionItem extends ContentEntityBase implements CollectionItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['collection'] = $this->get('collection')->target_id;
    return $uri_route_parameters;
  }

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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Check for an existing collection item.
    if ($this->isNew() && $this->collection->entity->getItem($this->item->entity)) {
      throw new \LogicException('Collection already has this entity.');
    }

    // Automatically update the name of this collection item to the collected item label.
    $this->set('name', $this->item->entity->label());
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Check the new status before running parent::save(), where it will be set
    // to false.
    $is_new = $this->isNew();

    // Save the collection_item and run core postSave hooks (e.g.
    // hook_entity_insert()).
    $return = parent::save();

    // Get the event_dispatcher service and dispatch the event.
    $event_dispatcher = \Drupal::service('event_dispatcher');

    // Is the collection item being inserted (e.g. is new)?
    if ($is_new) {
      // Dispatch new collection item event.
      $event = new CollectionItemCreateEvent($this);
      $event_dispatcher->dispatch(CollectionEvents::COLLECTION_ITEM_ENTITY_CREATE, $event);
    }
    else {
      // Dispatch update collection item event.
      $event = new CollectionItemUpdateEvent($this);
      $event_dispatcher->dispatch(CollectionEvents::COLLECTION_ITEM_ENTITY_UPDATE, $event);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    Cache::invalidateTags($this->collection->entity->getCacheTagsToInvalidate());
    Cache::invalidateTags($this->item->entity->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    static::invalidateTagsOnDelete($storage->getEntityType(), $entities);

    // Get the event_dispatcher service and dispatch the event.
    $event_dispatcher = \Drupal::service('event_dispatcher');

    foreach ($entities as $entity) {
      if ($entity->collection->entity) {
        Cache::invalidateTags($entity->collection->entity->getCacheTagsToInvalidate());
      }

      if ($entity->item->entity) {
        Cache::invalidateTags($entity->item->entity->getCacheTagsToInvalidate());
      }

      // Dispatch delete collection item event.
      $event = new CollectionItemDeleteEvent($entity);
      $event_dispatcher->dispatch(CollectionEvents::COLLECTION_ITEM_ENTITY_DELETE, $event);
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
   *
   * Returns \Drupal\Core\TypedData\TypedDataInterface
   */
  public function getAttribute(string $key) {
    foreach ($this->attributes as $attribute) {
      if ($attribute->key === $key) {
        return $attribute;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Returns \Drupal\Core\TypedData\TypedDataInterface
   */
  public function setAttribute(string $key, string $value) {
    // Update existing attribute.
    if ($attribute = $this->getAttribute($key)) {
      $attribute->value = $value;
      return $attribute;
    }

    // Add new attribute.
    return $this->attributes->appendItem([
      'key' => $key,
      'value' => $value,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function removeAttribute(string $key) {
    foreach ($this->attributes as $index => $attribute) {
      if ($attribute->key === $key) {
        $this->attributes->removeItem($index);
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isCanonical() {
    return $this->get('canonical')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user that owns this Collection item.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the collection item. This should be autogenerated and is for admin use only.'))
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['collection'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Collection'))
      ->setDescription(t('The collection to which this item belongs.'))
      ->setSetting('target_type', 'collection')
      ->setSetting('handler', 'default:collection')
      ->setDefaultValueCallback(static::class . '::getCollectionParam')
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
        'settings' => ['link' => TRUE]
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['item'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Collected item'))
      ->setDescription(t('The item for the collection.'))
      ->setSetting('exclude_entity_types', FALSE)
      ->setSetting('entity_type_ids', [
        'node' => 'node',
      ])
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'dynamic_entity_reference_label',
        'settings' => ['link' => TRUE],
      ])
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['attributes'] = BaseFieldDefinition::create('key_value')
      ->setLabel(t('Attributes'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(TRUE)
      ->setSetting('key_max_length', 255)
      ->setSetting('max_length', 255)
      ->setSetting('key_is_ascii', FALSE)
      ->setSetting('is_ascii', FALSE)
      ->setSetting('case_sensitive', FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'key_value_textfield',
        'weight' => 100,
        'settings' => ['description_enabled' => FALSE],
      ])
      ->setDisplayConfigurable('view', FALSE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setTranslatable(TRUE)
      ->setSetting('unsigned', FALSE)
      ->setSetting('size', 'normal')
      ->setInitialValue(0)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ]);

    $fields['canonical'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Canonical'))
      ->setDescription(t('A flag to indicate that this collection is the primary home.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 15,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the collection item was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the collection item was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Returns the default value for the collection field.
   *
   * @return int
   *   The entity id of the collection in the current route.
   */
  public static function getCollectionParam() {
    return \Drupal::routeMatch()->getRawParameter('collection');
  }

}
