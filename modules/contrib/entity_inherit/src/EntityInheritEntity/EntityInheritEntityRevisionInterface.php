<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;

/**
 * An entity or entity revision interface.
 */
interface EntityInheritEntityRevisionInterface {

  /**
   * Get all the entity's parents.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface
   *   All parents.
   */
  public function getMergedParents() : EntityInheritExistingMultipleEntitiesInterface;

  /**
   * Get all our fields which are candidates to be inherited.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   All inheritable fields for a type and bundle.
   */
  public function inheritableFields() : EntityInheritFieldListInterface;

  /**
   * Get a field value as an array.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId $field_name
   *   A field name.
   *
   * @return array
   *   The field value, such as:
   *   [
   *     [
   *       "target_id" => "1",
   *     ],
   *   ] or
   *   [
   *     [
   *       "value" => "<p>Hello world</p>\r\n",
   *       "summary" => "",
   *       "format" => "basic_html",
   *     ],
   *   ].
   */
  public function value(EntityInheritFieldId $field_name) : array;

  /**
   * Get referenced entities.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface $fields
   *   A field name.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface
   *   The referenced entities.
   */
  public function referencedEntities(EntityInheritFieldListInterface $fields) : EntityInheritExistingMultipleEntitiesInterface;

}
