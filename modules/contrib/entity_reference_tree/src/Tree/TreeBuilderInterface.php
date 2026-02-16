<?php

namespace Drupal\entity_reference_tree\Tree;

/**
 * Provides an interface for a tree builder.
 *
 * @ingroup entity_reference_tree_api
 */
interface TreeBuilderInterface {

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
  public function loadTree(string $entityType, string $bundleID, string $langCode = NULL, int $parent = 0, int $max_depth = NULL);

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
  public function createTreeNode($entity, array $selected = []);

  /**
   * Get the ID of a tree node.
   *
   * @param $entity
   *   The entity for the tree node.
   *
   * @return string|int|null
   *   The id of the tree node for the entity.
   */
  public function getNodeId($entity);

}
