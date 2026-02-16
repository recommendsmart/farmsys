<?php

namespace Drupal\entity_inherit\EntityInheritFieldValue;

/**
 * A colleciton of field values and their previous values.
 */
interface EntityInheritFieldValueCollectionInterface extends EntityInheritFieldValueInterface {

  /**
   * Add items to the collection.
   *
   * @param \Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueInterface $items
   *   Items to add.
   */
  public function add(EntityInheritFieldValueInterface $items);

  /**
   * Get an array of fields with their original values.
   *
   * @return array
   *   New field values, for example:
   *   ['field_x' => [['value' => 'hi']]].
   */
  public function toChangedArray() : array;

  /**
   * Get an array of fields with their original values.
   *
   * @return array
   *   Original field values, for example:
   *   ['field_x' => [['value' => 'hello']]].
   */
  public function toOriginalArray() : array;

}
