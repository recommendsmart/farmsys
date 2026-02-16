<?php

namespace Drupal\collection;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityInterface;

/**
 * The collection content manager service.
 */
class CollectionContentManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * CollectionContentManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get collection(s) to which this entity belongs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string|boolean $access
   *   The access level to check, or false to return all items.
   *
   * @return array
   *   The collections to which this entity belongs.
   */
  public function getCollectionsForEntity(EntityInterface $entity, $access = 'view') {
    $collections = [];

    foreach ($this->getCollectionItemsForEntity($entity, $access) as $collection_item) {
      $collection = $collection_item->collection->entity;
      $collections[$collection->id()] = $collection;
    }

    return $collections;
  }

  /**
   * Get collection items which collect this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string|boolean $access
   *   The access level to check, or false to return all items.
   *
   * @return array
   *   The collections_items that collect this entity.
   */
  public function getCollectionItemsForEntity(EntityInterface $entity, $access = 'view') {
    $collections_items = [];

    // Load all collection items that reference this entity.
    $collection_item_storage = $this->entityTypeManager->getStorage('collection_item');

    $collection_item_ids = $collection_item_storage->getQuery()
      ->condition('item__target_type', $entity->getEntityTypeId())
      ->condition('item__target_id', $entity->id())
      ->execute();

    $collection_items = $collection_item_storage->loadMultiple($collection_item_ids);

    foreach ($collection_items as $cid => $collection_item) {
      if ($access !== FALSE && !$collection_item->access($access)) {
        unset($collection_items[$cid]);
      }
    }

    return $collection_items;
  }

  /**
   * Check which collections a given entity can be added to, based on the
   * collection type configuration. This is used, for example, by the
   * collection_request submodule.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string|boolean $access
   *   The access level to check, or false to return all items.
   *
   * @return array
   *   The collections to which this entity can be added.
   */
  public function getAvailableCollections(EntityInterface $entity, $access = 'view') {
    $available_collections = [];
    $available_collection_types = [];
    $collection_type_storage = $this->entityTypeManager->getStorage('collection_type');
    $collection_storage = $this->entityTypeManager->getStorage('collection');

    foreach ($collection_type_storage->loadMultiple() as $id => $collection_type) {
      if (empty($collection_type->getAllowedCollectionItemTypes($entity->getEntityTypeId(), $entity->bundle()))) {
        continue;
      }

      $collections_by_type = $collection_storage->loadByProperties([
        'type' => $id,
      ]);

      foreach ($collections_by_type as $cid => $collection) {
        if ($access === FALSE || $collection->access($access)) {
          $available_collections[$cid] = $collection;
        }
      }
    }

    return $available_collections;
  }

}
