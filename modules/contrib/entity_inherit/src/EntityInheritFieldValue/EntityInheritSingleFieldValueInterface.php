<?php

namespace Drupal\entity_inherit\EntityInheritFieldValue;

use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;

/**
 * A single field value and its previous value.
 */
interface EntityInheritSingleFieldValueInterface {

  /**
   * Check if the previous value is different from the current value.
   *
   * @return bool
   *   TRUE if the previous value is different from the current value.
   */
  public function changed() : bool;

  /**
   * Get the field name.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId
   *   The field id.
   */
  public function fieldName() : EntityInheritFieldId;

  /**
   * The new value.
   *
   * @return array
   *   The new value.
   */
  public function newValue() : array;

  /**
   * The previous value.
   *
   * @return array
   *   The previous value.
   */
  public function previousValue() : array;

}
