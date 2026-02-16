<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\subgroup\InvalidLeafException;
use Drupal\subgroup\InvalidParentException;
use Drupal\subgroup\InvalidRootException;
use Drupal\subgroup\MalformedLeafException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for subgroup handlers.
 */
abstract class SubgroupHandlerBase extends EntityHandlerBase implements SubgroupHandlerInterface {

  /**
   * The entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Constructs a new SubgroupHandlerBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage handler.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->entityTypeId = $entity_type->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );
  }

  /**
   * Checks whether an entity is of the entity type this handler represents.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check the class for.
   *
   * @throws \InvalidArgumentException
   */
  protected function verify(EntityInterface $entity) {
    if ($entity->getEntityTypeId() !== $this->entityTypeId) {
      throw new \InvalidArgumentException('Entity does not belong to the entity type the handler represents.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function wrapLeaf(EntityInterface $entity) {
    $this->verify($entity);

    $class = $this->entityType->get('subgroup_wrapper');
    if (empty($class)) {
      throw new \RuntimeException(sprintf('The "%s" entity type did not define a "subgroup_wrapper" class.', $this->entityTypeId));
    }
    if (!class_exists($class)) {
      throw new \RuntimeException(sprintf('The "subgroup_wrapper" class defined by the "%s" entity type does not exist.', $this->entityTypeId));
    }

    return new $class($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function isLeaf(EntityInterface $entity) {
    $this->verify($entity);

    try {
      $this->wrapLeaf($entity);
    }
    catch (MalformedLeafException $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot(EntityInterface $entity) {
    $this->verify($entity);
    return $this->wrapLeaf($entity)->getDepth() === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function areVerticallyRelated(EntityInterface $a, EntityInterface $b) {
    $this->verify($a);
    $this->verify($b);

    $leaf_a = $this->wrapLeaf($a);
    $leaf_b = $this->wrapLeaf($b);

    if ($leaf_a->getTree() === $leaf_b->getTree()) {
      foreach ([[$leaf_a, $leaf_b], [$leaf_b, $leaf_a]] as [$ancestor, $descendant]) {
        if ($ancestor->getLeft() < $descendant->getLeft() && $ancestor->getRight() > $descendant->getRight()) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Writes the provided leaf data onto the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to write the data onto.
   * @param int $depth
   *   The depth.
   * @param int $left
   *   The left boundary.
   * @param int $right
   *   The right boundary.
   * @param int|string $tree
   *   The tree ID.
   */
  abstract protected function writeLeafData(EntityInterface $entity, $depth, $left, $right, $tree);

  /**
   * Clears the leaf data from the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to clear the data from.
   * @param bool $save
   *   Whether the entity should be saved.
   */
  abstract protected function clearLeafData(EntityInterface $entity, $save);

  /**
   * {@inheritdoc}
   */
  public function initTree(EntityInterface $entity) {
    $this->verify($entity);

    if ($entity->isNew()) {
      throw new InvalidRootException('Cannot use an unsaved entity as a tree root.');
    }
    if ($this->isLeaf($entity)) {
      throw new InvalidRootException('The entity to use as a tree root is already a leaf.');
    }

    $this->doInitTree($entity);
  }

  /**
   * Actually initializes a tree with the provided entity as the root.
   *
   * This is called after a few sanity checks and can be easily overwritten by
   * the extending classes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as the root.
   */
  protected function doInitTree(EntityInterface $entity) {
    $this->writeLeafData($entity, 0, 1, 2, $entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function addLeaf(EntityInterface $parent, EntityInterface $child) {
    $this->verify($parent);
    $this->verify($child);

    if (!$this->isLeaf($parent)) {
      throw new InvalidParentException('The entity to use as the parent is not a leaf.');
    }
    if ($child->isNew()) {
      throw new InvalidLeafException('Cannot use an unsaved entity as a leaf.');
    }
    if ($this->isLeaf($child)) {
      throw new InvalidLeafException('The entity to add as the leaf is already a leaf.');
    }

    $this->doAddLeaf($parent, $child);
  }

  /**
   * Actually adds a leaf to a tree.
   *
   * This is called after a few sanity checks and can be easily overwritten by
   * the extending classes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The entity to use as the parent.
   * @param \Drupal\Core\Entity\EntityInterface $child
   *   The entity to use as the new leaf.
   */
  protected function doAddLeaf(EntityInterface $parent, EntityInterface $child) {
    $parent_leaf = $this->wrapLeaf($parent);
    $ids_to_update = $this->storage->getQuery()
      ->condition($this->getRightPropertyName(), $parent_leaf->getRight(), '>=')
      ->condition($this->getTreePropertyName(), $parent_leaf->getTree())
      // We deal with the passed in parent, rather than a freshly loaded copy so
      // that any code calling this method does not have an out of sync entity
      // for the rest of its runtime.
      ->condition($this->entityType->getKey('id'), $parent->id(), '<>')
      ->accessCheck(FALSE)
      ->execute();

    // This might incur a performance hit on large trees, but it's the cleanest
    // option without having to resort to large update queries and manual entity
    // storage cache clears. If you experience an insurmountable page load time
    // because of the code below, please open an issue and provide a patch that
    // does what's described above.
    $entities_to_update = $this->storage->loadMultiple($ids_to_update);
    foreach ($entities_to_update as $entity_to_update) {
      $leaf_to_update = $this->wrapLeaf($entity_to_update);

      // If the leaf is a direct path up (i.e.: its left bound was lower than
      // the parent's right value), then we only need to update the right bound.
      // Otherwise, we need to increment the left bound by 2 as well.
      $left_value = $leaf_to_update->getLeft() > $parent_leaf->getRight()
        ? $leaf_to_update->getLeft() + 2
        : $leaf_to_update->getLeft();

      $this->writeLeafData(
        $entity_to_update,
        $leaf_to_update->getDepth(),
        $left_value,
        $leaf_to_update->getRight() + 2,
        $leaf_to_update->getTree()
      );
    }

    $parent_original_right = $parent_leaf->getRight();
    $this->writeLeafData(
      $parent,
      $parent_leaf->getDepth(),
      $parent_leaf->getLeft(),
      $parent_leaf->getRight() + 2,
      $parent_leaf->getTree()
    );

    $this->writeLeafData(
      $child,
      $parent_leaf->getDepth() + 1,
      $parent_original_right,
      $parent_original_right + 1,
      $parent_leaf->getTree()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function removeLeaf(EntityInterface $entity, $save = TRUE) {
    $this->verify($entity);

    if (!$this->isLeaf($entity)) {
      throw new InvalidLeafException('The entity to remove is not a leaf.');
    }
    if ($this->hasDescendants($entity)) {
      throw new InvalidLeafException('Cannot remove a leaf that still has descendants.');
    }

    $this->doRemoveLeaf($entity, $save);
  }

  /**
   * Actually removes a leaf from a tree.
   *
   * This is called after a few sanity checks and can be easily overwritten by
   * the extending classes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to remove from the tree.
   * @param bool $save
   *   Whether the entity should be saved.
   */
  protected function doRemoveLeaf(EntityInterface $entity, $save) {
    $leaf = $this->wrapLeaf($entity);

    // If the left and right values are 2 and 3 respectively it means we might
    // be removing the last child of a tree root. In this case, we unset the
    // tree altogether.
    if ($leaf->getLeft() === 2 && $leaf->getRight() === 3 && $this->getDescendantCount($root = $this->storage->load($leaf->getTree())) === 1) {
      $this->clearLeafData($root, TRUE);
    }
    // This might incur a performance hit on large trees, but it's the cleanest
    // option without having to resort to large update queries and manual entity
    // storage cache clears. If you experience an insurmountable page load time
    // because of the code below, please open an issue and provide a patch that
    // does what's described above.
    else {
      $ids_to_update = $this->storage->getQuery()
        ->condition($this->getRightPropertyName(), $leaf->getRight(), '>')
        ->condition($this->getTreePropertyName(), $leaf->getTree())
        ->accessCheck(FALSE)
        ->execute();

      $entities_to_update = $this->storage->loadMultiple($ids_to_update);
      foreach ($entities_to_update as $entity_to_update) {
        $leaf_to_update = $this->wrapLeaf($entity_to_update);

        // If the leaf is a direct path up (i.e.: its left bound was lower than
        // the parent's right value), then we only need to update the right
        // bound. Otherwise, we need to decrease the left bound by 2 as well.
        $left_value = $leaf_to_update->getLeft() > $leaf->getRight()
          ? $leaf_to_update->getLeft() - 2
          : $leaf_to_update->getLeft();

        $this->writeLeafData(
          $entity_to_update,
          $leaf_to_update->getDepth(),
          $left_value,
          $leaf_to_update->getRight() - 2,
          $leaf_to_update->getTree()
        );
      }
    }

    $this->clearLeafData($entity, $save);
  }

  /**
   * Gets the name of the 'depth' property to use in entity queries.
   *
   * @return string
   *   The property name.
   */
  abstract protected function getDepthPropertyName();

  /**
   * Gets the name of the 'left' property to use in entity queries.
   *
   * @return string
   *   The property name.
   */
  abstract protected function getLeftPropertyName();

  /**
   * Gets the name of the 'right' property to use in entity queries.
   *
   * @return string
   *   The property name.
   */
  abstract protected function getRightPropertyName();

  /**
   * Gets the name of the 'tree' property to use in entity queries.
   *
   * @return string
   *   The property name.
   */
  abstract protected function getTreePropertyName();

  /**
   * {@inheritdoc}
   */
  public function getParent(EntityInterface $entity) {
    $this->verify($entity);

    if ($this->isRoot($entity)) {
      throw new InvalidLeafException('Trying to get the parent of a root leaf.');
    }

    $leaf = $this->wrapLeaf($entity);
    $entity_ids = $this->storage->getQuery()
      ->condition($this->getDepthPropertyName(), $leaf->getDepth() - 1)
      ->condition($this->getLeftPropertyName(), $leaf->getLeft(), '<')
      ->condition($this->getRightPropertyName(), $leaf->getRight(), '>')
      ->condition($this->getTreePropertyName(), $leaf->getTree())
      ->accessCheck(FALSE)
      ->execute();

    assert(count($entity_ids) === 1);
    return $this->storage->load(reset($entity_ids));
  }

  /**
   * {@inheritdoc}
   */
  public function getAncestors(EntityInterface $entity) {
    $this->verify($entity);

    if ($this->isRoot($entity)) {
      throw new InvalidLeafException('Trying to get the ancestors of a root leaf.');
    }

    $leaf = $this->wrapLeaf($entity);
    $entity_ids = $this->storage->getQuery()
      ->condition($this->getLeftPropertyName(), $leaf->getLeft(), '<')
      ->condition($this->getRightPropertyName(), $leaf->getRight(), '>')
      ->condition($this->getTreePropertyName(), $leaf->getTree())
      ->sort($this->getLeftPropertyName())
      ->accessCheck(FALSE)
      ->execute();

    assert(count($entity_ids) >= 1);
    return $this->storage->loadMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(EntityInterface $entity) {
    $this->verify($entity);

    $leaf = $this->wrapLeaf($entity);
    $entity_ids = $this->storage->getQuery()
      ->condition($this->getDepthPropertyName(), $leaf->getDepth() + 1)
      ->condition($this->getLeftPropertyName(), $leaf->getLeft(), '>')
      ->condition($this->getRightPropertyName(), $leaf->getRight(), '<')
      ->condition($this->getTreePropertyName(), $leaf->getTree())
      ->sort($this->getLeftPropertyName())
      ->accessCheck(FALSE)
      ->execute();

    return !empty($entity_ids) ? $this->storage->loadMultiple($entity_ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants(EntityInterface $entity) {
    $this->verify($entity);

    $leaf = $this->wrapLeaf($entity);
    $entity_ids = $this->storage->getQuery()
      ->condition($this->getLeftPropertyName(), $leaf->getLeft(), '>')
      ->condition($this->getRightPropertyName(), $leaf->getRight(), '<')
      ->condition($this->getTreePropertyName(), $leaf->getTree())
      ->sort($this->getLeftPropertyName())
      ->accessCheck(FALSE)
      ->execute();

    return !empty($entity_ids) ? $this->storage->loadMultiple($entity_ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendantCount(EntityInterface $entity) {
    $this->verify($entity);

    $leaf = $this->wrapLeaf($entity);
    return (int) $this->storage->getQuery()
      ->condition($this->getLeftPropertyName(), $leaf->getLeft(), '>')
      ->condition($this->getRightPropertyName(), $leaf->getRight(), '<')
      ->condition($this->getTreePropertyName(), $leaf->getTree())
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function hasDescendants(EntityInterface $entity) {
    return (bool) $this->getDescendantCount($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getTreeCacheTags(EntityInterface $entity) {
    $this->verify($entity);

    if (!$this->isLeaf($entity)) {
      throw new InvalidLeafException('The entity to get the tree cache tags for is not a leaf.');
    }

    $tree_cache_tag = implode(':', [
      'subgroup',
      'tree',
      $this->entityTypeId,
      $this->wrapLeaf($entity)->getTree(),
    ]);

    return [$tree_cache_tag];
  }

}
