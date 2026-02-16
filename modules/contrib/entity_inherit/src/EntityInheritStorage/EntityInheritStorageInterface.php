<?php

namespace Drupal\entity_inherit\EntityInheritStorage;

use Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface;

/**
 * Storage.
 */
interface EntityInheritStorageInterface {

  /**
   * Get all children of an entity of a given type and id.
   *
   * @param string $type
   *   The parent entity type.
   * @param string $id
   *   The parent entity id.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface
   *   The child entities.
   */
  public function getChildrenOf(string $type, string $id) : EntityInheritExistingMultipleEntitiesInterface;

}
