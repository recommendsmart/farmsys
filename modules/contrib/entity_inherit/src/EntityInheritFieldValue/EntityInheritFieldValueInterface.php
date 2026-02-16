<?php

namespace Drupal\entity_inherit\EntityInheritFieldValue;

/**
 * A field value and its previous value.
 */
interface EntityInheritFieldValueInterface {

  /**
   * Get as array.
   *
   * @return array
   *   Array of EntityInheritFieldValueInterface items.
   */
  public function toArray() : array;

}
