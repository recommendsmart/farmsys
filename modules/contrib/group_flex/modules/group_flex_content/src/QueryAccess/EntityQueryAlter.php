<?php

namespace Drupal\group_flex_content\QueryAccess;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\group\QueryAccess\EntityQueryAlter as EntityQueryAlterBase;

/**
 * Defines a class for altering entity queries.
 *
 * This alters the query so that 'outsider' group content is also allowed. The
 * filtering of the other group content is done by the query altering in Group.
 *
 * @internal
 */
class EntityQueryAlter extends EntityQueryAlterBase {

  /**
   * Actually alters the select query for the given entity type.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $operation
   *   The query operation.
   */
  protected function doAlter(SelectInterface $query, EntityTypeInterface $entity_type, $operation) {
    $entity_type_id = $entity_type->id();
    if ($entity_type_id !== 'node') {
      return;
    }

    // We only alter SQL queries.
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }

    // If the account can bypass group access, we do not alter the query at all.
    if ($this->currentUser->hasPermission('bypass group access')) {
      return;
    }

    $content_types = $this->getUsedContentTypes($entity_type_id);

    // See if there are any flexible nids we need to include.
    $flex_nids = $this->getAllFlexibleIds($content_types);
    if (empty($flex_nids)) {
      return;
    }

    $this->cacheableMetadata->addCacheContexts(['user']);

    $conditions = &$query->conditions();

    // We assume the last condition is from the Group module because we
    // implemented hook_module_implements_alter.
    // There are a few situations here.
    // 1. EntityQueryAlterBase returned early, then we are not here anyway.
    // 2. EntityQueryAlterBase added $query->isNull('gcfd.entity_id').
    // 3. EntityQueryAlterBase added a new Condition (orGroup) which accounts
    // for content not in the group or content in the group.
    // Our code should react on scenario 2 and 3. In both cases we need to add
    // a Condition group which accounts for all the flexible nodes the user is
    // allowed to see.
    $key = array_key_last($conditions);

    // This is scenario 2.
    if (is_string($conditions[$key]['field']) && $conditions[$key]['field'] === 'gcfd.entity_id' && $conditions[$key]['operator'] === 'IS NULL') {
      // Because Group did not create a new Condition Group here we can unset
      // the old isNull query and re-use that in a new orConditionGroup.
      unset($conditions[$key]);
      $query->condition(
        $query->orConditionGroup()
          ->isNull('gcfd.entity_id')
          ->condition($group_flex_condition = $query->andConditionGroup())
      );
    }

    // This is scenario 3.
    if (isset($conditions[$key]) && $conditions[$key]['field'] instanceof Condition) {
      // Group did create a ConditionGroup so we can extend on that.
      $conditions[$key]['field']->condition(
        $group_flex_condition = $query->andConditionGroup()
      );
    }

    if (isset($group_flex_condition)) {
      $group_flex_condition->condition('gcfd.entity_id', $flex_nids, 'IN');
      $group_flex_condition->condition('gcfd.type', $content_types, 'IN');
    }
  }

  /**
   * Get all the flexible (node) ids.
   *
   * @param array $content_types
   *   An array of (group) content types to get the related node ids from.
   *
   * @return array
   *   An array of node ids.
   */
  private function getAllFlexibleIds(array $content_types) {
    $nids = [];

    if (empty($content_types)) {
      return $nids;
    }

    // Determine the content visibility based on the user role.
    $content_visibility = $this->currentUser->isAuthenticated() ? ['anonymous', 'outsider'] : ['anonymous'];

    // Note that we are deliberately not getting any private/member content.
    // These are handled by the default Group EntityQueryAlter Filtering.
    // We assume the permission 'view group_node:node entity' is set correctly.
    // Find all outsider group content items.
    $outsider_gcids = $this->database
      ->select('group_content__content_visibility', 'gcv')
      ->fields('gcv', ['entity_id'])
      ->condition('content_visibility_value', $content_visibility, 'IN')
      ->execute()
      ->fetchCol();

    if (empty($outsider_gcids)) {
      return $nids;
    }

    // Get the node ids linking to these group content items.
    $nids_query = $this->database
      ->select('group_content_field_data', 'gc');
    $nids_query->join('node_field_data', 'nfd', 'nfd.nid = gc.entity_id');
    $nids = $nids_query
      ->fields('gc', ['entity_id'])
      ->distinct()
      ->condition('gc.type', $content_types, 'IN')
      ->condition('gc.id', $outsider_gcids, 'IN')
      ->condition('nfd.status', 1)
      ->execute()
      ->fetchCol();

    return $nids;
  }

  /**
   * Get all the used content types.
   *
   * @param string $entity_type_id
   *   The entity type id to get the used content types for.
   *
   * @return array
   *   An array of used content types.
   */
  private function getUsedContentTypes(string $entity_type_id): array {

    // Find all of the group content plugins that define access.
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess($entity_type_id);
    if (empty($plugin_ids)) {
      return [];
    }
    // Load all of the group content types that define access.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $gct_storage */
    $gct_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_content_types = $gct_storage->loadByContentPluginId($plugin_ids);
    if (empty($group_content_types)) {
      return [];
    }

    // Remove all the non-flexible group content types.
    foreach ($group_content_types as $gct_id => $group_content_type) {
      if ($pluginConfig = $group_content_type->get('plugin_config')) {
        if (!isset($pluginConfig['group_content_visibility']) || $pluginConfig['group_content_visibility'] !== 'flexible') {
          unset($group_content_types[$gct_id]);
        }
      }
    }

    // Find all group content types that have content for them.
    $group_content_type_query = $this->database
      ->select('group_content_field_data', 'gc')
      ->fields('gc', ['type']);

    // Only add a type condition if we have content types to check for.
    if (!empty($group_content_types)) {
      $group_content_type_query->condition('type', array_keys($group_content_types), 'IN');
    }

    $group_content_type_ids_in_use = $group_content_type_query->distinct()
      ->execute()
      ->fetchCol();

    if (empty($group_content_type_ids_in_use)) {
      return [];
    }

    // Get some maps to use in the loops below so we save some milliseconds.
    $plugin_id_map = $this->pluginManager->getPluginGroupContentTypeMap();

    // Create an array of content types to use in our future query.
    $content_types = [];
    foreach ($plugin_ids as $plugin_id) {
      // If the plugin is not installed, skip it.
      if (!isset($plugin_id_map[$plugin_id])) {
        continue;
      }

      foreach ($plugin_id_map[$plugin_id] as $group_content_type_id) {
        // If the group content type has no content, skip it.
        if (!in_array($group_content_type_id, $group_content_type_ids_in_use, TRUE)) {
          continue;
        }
        $content_types[] = $group_content_type_id;
      }
    }
    return $content_types;
  }

}
