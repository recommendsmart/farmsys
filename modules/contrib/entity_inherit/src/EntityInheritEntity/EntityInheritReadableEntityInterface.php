<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface;

/**
 * An entity which can be read.
 */
interface EntityInheritReadableEntityInterface {

  /**
   * Update this entity based on its parents.
   *
   * @return \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface
   *   Field values.
   */
  public function fieldValues() : EntityInheritFieldValueCollectionInterface;

}
