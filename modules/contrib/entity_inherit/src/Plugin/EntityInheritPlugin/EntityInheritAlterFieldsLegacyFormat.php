<?php

namespace Drupal\entity_inherit\Plugin\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginBase;

/**
 * Alter a list of parent fields in legacy (1.0.0-beta5 or before format).
 *
 * Prior to 1.0.0-beta6, field names such as field_parents were assumed to
 * belong to the node content type, however it is possible to have one
 * field_parents field per entity type; we now internally store field names
 * prefixed by their entity type. To conserve compatibility with prior versions,
 * we will assume that field names which are not prefixed belong to the node
 * entity type.
 *
 * @EntityInheritPluginAnnotation(
 *   id = "entity_inherit_alter_parent_fields_legacy",
 *   description = @Translation("Alter a list of parent fields."),
 *   weight = -100,
 * )
 */
class EntityInheritAlterFieldsLegacyFormat extends EntityInheritPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterFields(array &$field_names, EntityInherit $app) {
    foreach ($field_names as $key => $field_name) {
      if ($field_name && strpos($field_name, '.') === FALSE) {
        $field_names[$key] = 'node.' . $field_name;
      }
    }
  }

}
