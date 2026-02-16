<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field list.
 */
interface EntityInheritFieldListInterface extends \Countable {

  /**
   * Add a field to the array.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritField $field
   *   A field to add.
   */
  public function add(EntityInheritField $field);

  /**
   * Filter by only returning matches.
   *
   * @param array $field_list
   *   A field list such as [node.field_x, paragraph.field_x].
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Filtered field list.
   */
  public function filter(array $field_list) : EntityInheritFieldListInterface;

  /**
   * Filter by only returning matches by type.
   *
   * @param array $entity_types
   *   A list of entity types such as [node].
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Filtered field list.
   */
  public function filterByType(array $entity_types) : EntityInheritFieldListInterface;

  /**
   * Filter by only returning matches by name.
   *
   * @param array $names
   *   A name list such as [field_x, field_y].
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Filtered field list.
   */
  public function filterByName(array $names) : EntityInheritFieldListInterface;

  /**
   * Find a single field by its id.
   *
   * @param string $id
   *   An id such as node.field_x.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldInterface
   *   A field.
   *
   * @throws \Exception
   */
  public function findById(string $id) : EntityInheritFieldInterface;

  /**
   * Check if this list includes a field.
   *
   * @param string $entity_type
   *   An entity type to which the field name belongs.
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if the field is included.
   */
  public function includes(string $entity_type, string $field_name) : bool;

  /**
   * Get only invalid fields, no duplicates.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Invalid fields.
   */
  public function invalidOnly(string $category) : EntityInheritFieldListInterface;

  /**
   * Get as array.
   *
   * @return array
   *   Array of fields.
   */
  public function toArray() : array;

  /**
   * Get as array of ids.
   *
   * @return array
   *   Array of EntityInheritFieldId objects.
   */
  public function toFieldIdsArray() : array;

  /**
   * Get as array of names, ignoring types.
   *
   * @return array
   *   Array of names, ignoring types, for example [field_x, field_y].
   */
  public function toFieldNamesArray() : array;

  /**
   * Get as a text area.
   *
   * @return string
   *   The fields as a text area.
   */
  public function toTextArea() : string;

  /**
   * Get only valid fields, no duplicates.
   *
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   *
   * @return \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface
   *   Valid fields.
   */
  public function validOnly(string $category) : EntityInheritFieldListInterface;

}
