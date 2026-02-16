<?php

namespace Drupal\entity_inherit\Plugin\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginBase;

/**
 * Remove system fields from a field list.
 *
 * @EntityInheritPluginAnnotation(
 *   id = "entity_inherit_remove_system_fields",
 *   description = @Translation("Remove system fields from a field list."),
 *   weight = -100,
 * )
 */
class EntityInheritRemoveSystemFields extends EntityInheritPluginBase {

  /**
   * {@inheritdoc}
   */
  public function filterFields(array &$field_names, array $original, string $category, EntityInherit $app) {
    $new_list = [];

    foreach ($field_names as $field_name => $field_value) {
      if (preg_match('/^[^\.]*\.field_[a-z0-9_]*$/', $field_name) || $field_name == 'node.body') {
        $new_list[$field_name] = $field_value;
      }
    }

    $field_names = $new_list;
  }

}
