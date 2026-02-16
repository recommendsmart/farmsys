<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field.
 */
interface EntityInheritFieldInterface {

  /**
   * Stringify.
   *
   * @return string
   *   The field name.
   */
  public function __toString();

  /**
   * Get the entity type.
   *
   * @return string
   *   The entity type.
   */
  public function entityType() : string;

  /**
   * Get the field name.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId
   *   The field name.
   */
  public function fieldName() : EntityInheritFieldId;

  /**
   * Check if this field matches an entity type and name.
   *
   * @param string $entity_type
   *   An entity type to which the field name belongs.
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if the field matches.
   */
  public function matches(string $entity_type, string $field_name) : bool;

  /**
   * Check if this field matches an entity type and name (e.g. node.field_x).
   *
   * @param string $field_string
   *   A field string (e.g. node.field_x).
   *
   * @return bool
   *   TRUE if the field matches.
   */
  public function matchesString(string $field_string) : bool;

  /**
   * Whether or not this field is valid.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return bool
   *   Whether or not this field is valid.
   */
  public function valid(string $category) : bool;

}
