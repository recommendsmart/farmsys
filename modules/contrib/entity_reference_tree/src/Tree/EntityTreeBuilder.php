<?php

namespace Drupal\entity_reference_tree\Tree;

use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a class for building a tree from general entity.
 *
 * @ingroup entity_reference_tree_api
 *
 * @see \Drupal\entity_reference_tree\Tree\TreeBuilderInterface
 */
class EntityTreeBuilder implements TreeBuilderInterface {

  /**
   *
   * @var string
   *   The permission name to access the entity tree.
   *   The entity storage load function is actually responsible for
   *   the permission checking for each individual entity.
   *   So here just use a very weak permission.
   */
  private $accessPermission = 'access content';

  /**
   * The Language code.
   *
   * @var string
   */
  protected $langCode;

  /**
   * Load all entities from an entity bundle for the tree.
   *
   * @param string $entityType
   *   The type of the entity.
   *
   * @param string $bundleID
   *   The bundle ID.
   *
   * @return array
   *   All entities in the entity bundle.
   */
  public function loadTree(string $entityType, string $bundleID, string $langCode = NULL, int $parent = 0, int $max_depth = NULL) {
    if ($this->hasAccess()) {
      if ($bundleID === '*') {
        // Load all entities regardless bundles.
        $entities = \Drupal::entityTypeManager()->getStorage($entityType)->loadMultiple();
        $hasBundle = FALSE;
      }
      else {
        $hasBundle = TRUE;
        $entityStorage = \Drupal::entityTypeManager()->getStorage($entityType);
        // Build the tree node for the bundle.
        $tree = [
            (object) [
                'id' => $bundleID,
                // Required.
                'parent' => '#',
                // Node text.
                'text' => $bundleID,
            ],
        ];
        // Entity query properties.
        $properties = [
            // Bundle key field.
            $entityStorage->getEntityType()->getKey('bundle') => $bundleID,
        ];
        // Load all entities matched the conditions.
        $entities = $entityStorage->loadByProperties($properties);
      }
      
      // Build the tree.
      foreach ($entities as $entity) {
        $tree[] = (object) [
          'id' => $entity->id(),
        // Required.
          'parent' => $hasBundle ? $entity->bundle() : '#',
        // Node text.
          'text' => $entity->label(),
        ];
      }

      return $tree;
    }
    // The user is not allowed to access taxonomy overviews.
    return NULL;
  }

  /**
   * Create a tree node.
   *
   * @param $entity
   *   The entity for the tree node.
   *
   * @param array $selected
   *   A anrray for all selected nodes.
   *
   * @return array
   *   The tree node for the entity.
   */
  public function createTreeNode($entity, array $selected = []) {

    $node = [
    // Required.
      'id' => $entity->id,
    // Required.
      'parent' => $entity->parent,
    // Node text.
      'text' => $entity->text,
      'state' => ['selected' => FALSE],
    ];

    if (in_array($entity->id, $selected)) {
      // Initially selected node.
      $node['state']['selected'] = TRUE;
    }

    return $node;
  }

  /**
   * Get the ID of a tree node.
   *
   * @param $entity
   *   The entity for the tree node.
   *
   * @return string|int|null
   *   The id of the tree node for the entity.
   */
  public function getNodeId($entity) {
    return $entity->id;
  }

  /**
   * Check if a user has the access to the tree.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The user object to check.
   *
   * @return bool
   *   If the user has the access to the tree return TRUE,
   *   otherwise return FALSE.
   */
  private function hasAccess(AccountProxyInterface $user = NULL) {
    // Check current user as default.
    if (empty($user)) {
      $user = \Drupal::currentUser();
    }

    return $user->hasPermission($this->accessPermission);
  }

}
