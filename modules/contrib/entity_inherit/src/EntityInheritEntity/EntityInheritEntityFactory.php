<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\Utilities\FriendTrait;

/**
 * A factory to build entities. Instantiate through EntityEnherit.
 */
class EntityInheritEntityFactory {

  use FriendTrait;

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The application singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->friendAccess([EntityInherit::class]);
    $this->app = $app;
  }

  /**
   * Get an entity from a type and id.
   *
   * @param string $type
   *   A type, for example "node".
   * @param string $id
   *   An id, for example "1".
   * @param null|\Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The Drupal entity object, or NULL if we don't have it.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface
   *   An entity.
   */
  public function fromTypeIdEntity(string $type, string $id, $entity) : EntityInheritEntitySingleInterface {
    return new EntityInheritExistingEntity($type, $id, $entity, $this->app);
  }

  /**
   * Get an entity from an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface
   *   An entity.
   */
  public function fromEntity(FieldableEntityInterface $entity) : EntityInheritEntitySingleInterface {
    if ($entity->id()) {
      return $this->fromTypeIdEntity($entity->getEntityTypeId(), $entity->id(), $entity);
    }
    return new EntityInheritNewEntity($entity, $this->app);
  }

  /**
   * Get an entity from an existing entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritSingleExistingEntityInterface
   *   An entity.
   */
  public function fromExistingEntity(FieldableEntityInterface $entity) : EntityInheritSingleExistingEntityInterface {
    $candidate = $this->fromEntity($entity);

    if (is_a($candidate, EntityInheritSingleExistingEntityInterface::class)) {
      return $candidate;
    }

    throw new \Exception('The entity object provided could not be converted to an existing interface.');
  }

  /**
   * Get a new entity from a queueable item.
   *
   * @param array $item
   *   A queueable item.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritSingleExistingEntityInterface
   *   An entity if possible.
   *
   * @throws \Exception
   */
  public function fromQueueableItem(array $item) : EntityInheritSingleExistingEntityInterface {
    if (!array_key_exists('id', $item)) {
      throw new \Exception('id key is required.');
    }
    $parts = explode(':', $item['id']);
    if (count($parts) != 2) {
      throw new \Exception('id key is expected to be in the format type:id.');
    }
    $type = $parts[0];
    $id = $parts[1];
    $candidate = $this->fromTypeIdEntity($type, $id, NULL);
    if (!is_a($candidate, EntityInheritSingleExistingEntityInterface::class)) {
      throw new \Exception('Expecting an existing single interface.');
    }
    return $candidate;
  }

  /**
   * Get a new collection.
   *
   * @param array $drupal_entities
   *   An array of Drupal entities.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface
   *   A new collection.
   */
  public function newCollection(array $drupal_entities = []) : EntityInheritExistingMultipleEntitiesInterface {
    $return = new EntityInheritExistingEntityCollection($this->app);

    foreach ($drupal_entities as $drupal_entity) {
      $return->add($this->app->wrapExisting($drupal_entity));
    }

    return $return;
  }

}
