<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for Subgroup entity handlers.
 *
 * @ingroup subgroup
 */
interface SubgroupHandlerInterface extends EntityHandlerInterface {

  /**
   * Wraps an entity in a LeafInterface.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to wrap.
   *
   * @return \Drupal\subgroup\LeafInterface
   *   The wrapped entity.
   */
  public function wrapLeaf(EntityInterface $entity);

  /**
   * Checks whether an entity is part of a tree.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Whether the entity is part of a tree.
   */
  public function isLeaf(EntityInterface $entity);

  /**
   * Checks whether an entity is the root of a tree.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Whether the entity is the root of a tree.
   */
  public function isRoot(EntityInterface $entity);

  /**
   * Checks whether one entity is an ancestor or descendant of another.
   *
   * @param \Drupal\Core\Entity\EntityInterface $a
   *   The first entity to check.
   * @param \Drupal\Core\Entity\EntityInterface $b
   *   The second entity to check.
   *
   * @return bool
   *   Whether $a is an ancestor or descendant of $b.
   */
  public function areVerticallyRelated(EntityInterface $a, EntityInterface $b);

  /**
   * Initializes a tree with the provided entity as the root.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as the root.
   */
  public function initTree(EntityInterface $entity);

  /**
   * Adds a leaf to a tree.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The entity to use as the parent.
   * @param \Drupal\Core\Entity\EntityInterface $child
   *   The entity to use as the new leaf.
   */
  public function addLeaf(EntityInterface $parent, EntityInterface $child);

  /**
   * Removes a leaf from a tree.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to remove from the tree.
   * @param bool $save
   *   (optional) Whether the entity should be saved. Defaults to TRUE. Set this
   *   to FALSE when removing a leaf after an entity delete so that you avoid
   *   saving the entity that's about to be deleted.
   */
  public function removeLeaf(EntityInterface $entity, $save = TRUE);

  /**
   * Gets the parent of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve the parent for.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity that represents the leaf's parent in the tree.
   */
  public function getParent(EntityInterface $entity);

  /**
   * Gets the ancestors of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve the ancestors for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities that represent the leaf's ancestors in the tree.
   */
  public function getAncestors(EntityInterface $entity);

  /**
   * Gets the children of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve the children for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities that represent the leaf's children in the tree.
   */
  public function getChildren(EntityInterface $entity);

  /**
   * Gets the descendants of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve the descendants for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities that represent the leaf's descendants in the tree.
   */
  public function getDescendants(EntityInterface $entity);

  /**
   * Counts how many descendants an entity has.
   *
   * This method only exists for safety and optimization checks as there is very
   * little useful information that can be derived from simply knowing the
   * number of descendants.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to count the descendants of.
   *
   * @return int
   *   The number of descendants.
   */
  public function getDescendantCount(EntityInterface $entity);

  /**
   * Checks whether an entity has descendants.
   *
   * Note: We do not have hasParent() or hasAncestors() as we can simply check
   * this by clever use of isLeaf() and isRoot(), as all non-root leaves should
   * have ancestors. For that reason we also don't have hasChildren() because
   * that is easily answered by this method.
   *
   * The only reason we have hasDescendants() is so that we can facilitate the
   * safety checks throughout the codebase that forbid the removal or other
   * manipulation of leaves that have descendants.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve the children for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities that represent the leaf's children in the tree.
   */
  public function hasDescendants(EntityInterface $entity);

  /**
   * Gets the tree cache tags for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the tree cache tags for.
   *
   * @return string[]
   *   The cache tags used to invalidate cache items using the tree.
   */
  public function getTreeCacheTags(EntityInterface $entity);

}
