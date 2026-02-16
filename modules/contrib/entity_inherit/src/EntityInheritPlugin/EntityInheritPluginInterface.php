<?php

namespace Drupal\entity_inherit\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface;

/**
 * An interface for all EntityInheritPlugin type plugins.
 */
interface EntityInheritPluginInterface {

  /**
   * Alter the list of parent fields.
   *
   * @param array $field_names
   *   An array of field names which can be modified. The values are field names
   *   such as node.field_x or node.body or paragraph.field_x.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   */
  public function alterFields(array &$field_names, EntityInherit $app);

  /**
   * Remove field names which should be ignored.
   *
   * @param array $field_names
   *   An array of field names which can be modified. The keys are field names
   *   such as node.field_x or node.body or paragraph.field_x; the value of
   *   each array item is ignored (left as is).
   * @param array $original
   *   An non-modified array of field names; see above.
   * @param string $category
   *   Arbitrary category which is then managed by plugins. "inheritable" and
   *   "parent" can be used.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   */
  public function filterFields(array &$field_names, array $original, string $category, EntityInherit $app);

  /**
   * Act on an entity being saved.
   *
   * @param \Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntitySingleInterface $entity
   *   An entity being presaved.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   */
  public function presave(EntityInheritEntitySingleInterface $entity, EntityInherit $app);

}
