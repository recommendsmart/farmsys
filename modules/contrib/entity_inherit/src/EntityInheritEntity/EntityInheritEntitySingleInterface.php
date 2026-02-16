<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;

/**
 * An single entity.
 */
interface EntityInheritEntitySingleInterface extends EntityInheritUpdatableEntityInterface, EntityInheritEntityRevisionInterface {

  /**
   * Get the type of this entity.
   *
   * @return string
   *   The type, for example 'node'.
   */
  public function getType() : string;

  /**
   * Check if we have a field.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId $field
   *   A field name.
   *
   * @return bool
   *   TRUE if we have new parents.
   */
  public function hasField(EntityInheritFieldId $field) : bool;

  /**
   * Check if we have new parents.
   *
   * @return bool
   *   TRUE if we have new parents.
   */
  public function hasNewParents() : bool;

  /**
   * Presave this entity.
   */
  public function presave();

  /**
   * Whether presaving this entity should trigger the queue.
   *
   * An entity triggered modified by the queue should not trigger the queue,
   * otherwise we'd have an infinite loop.
   *
   * @return bool
   *   Whether presaving this entity should trigger the queue.
   */
  public function triggersQueue() : bool;

}
