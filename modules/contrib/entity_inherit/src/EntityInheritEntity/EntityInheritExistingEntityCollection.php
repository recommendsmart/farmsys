<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface;

/**
 * An collection of existing entities.
 */
class EntityInheritExistingEntityCollection implements EntityInheritUpdatableEntityInterface, EntityInheritExistingMultipleEntitiesInterface {

  /**
   * The app singleton.
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * The entities in this collection.
   *
   * @var array
   */
  protected $entities;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->app = $app;
    $this->entities = [];
  }

  /**
   * {@inheritdoc}
   */
  public function add(EntityInheritExistingEntityInterface $items) {
    $this->entities += $items->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->entities);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldValues() : EntityInheritFieldValueCollectionInterface {
    $return = $this->app->getFieldValueFactory()->newCollection();

    foreach ($this->entities as $entity) {
      $return->add($entity->fieldValues());
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getMergedParents() : EntityInheritExistingMultipleEntitiesInterface {
    $return = $this->app->getEntityFactory()->newCollection();

    foreach ($this->toArray() as $entity) {
      $return->add($entity->getMergedParents());
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function preload() : EntityInheritExistingMultipleEntitiesInterface {
    foreach ($this->toArrayByType() as $type => $entity_ids) {
      $this->app->getEntityTypeManager()->getStorage($type)->loadMultiple($entity_ids);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(EntityInheritExistingMultipleEntitiesInterface $items) : EntityInheritExistingMultipleEntitiesInterface {
    $to_remove = $items->toArray();
    foreach ($this->entities as $key => $data) {
      if (array_key_exists($key, $to_remove)) {
        unset($this->entities[$key]);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    return $this->entities;
  }

  /**
   * {@inheritdoc}
   */
  public function toArrayByType() : array {
    $return = [];

    foreach ($this->entities as $entity) {
      $return[$entity->getType()][] = $entity->getId();
    }

    return $return;
  }

}
