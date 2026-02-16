<?php

namespace Drupal\subgroup;

/**
 * Defines an interface for entities that act as a tree leaf.
 */
interface LeafInterface {

  /**
   * Get the leaf's depth in the tree.
   *
   * @return int
   *   The depth of the leaf in the tree.
   */
  public function getDepth();

  /**
   * Get the leaf's left bound in the tree.
   *
   * @return int
   *   The left bound of the leaf in the tree.
   */
  public function getLeft();

  /**
   * Get the leaf's right bound in the tree.
   *
   * @return int
   *   The right bound of the leaf in the tree.
   */
  public function getRight();

  /**
   * Get the leaf's tree ID.
   *
   * @return int|string
   *   The ID of the tree the leaf belongs to.
   */
  public function getTree();

}
