<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\subgroup\InvalidParentException;
use Drupal\subgroup\InvalidRootException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subgroup handler for Group entities.
 */
class GroupSubgroupHandler extends SubgroupHandlerBase {

  /**
   * The GroupType subgroup handler.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $groupTypeHandler;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->groupTypeHandler = $container->get('entity_type.manager')->getHandler('group_type', 'subgroup');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function writeLeafData(EntityInterface $entity, $depth, $left, $right, $tree) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    $entity
      ->set(SUBGROUP_DEPTH_FIELD, $depth)
      ->set(SUBGROUP_LEFT_FIELD, $left)
      ->set(SUBGROUP_RIGHT_FIELD, $right)
      ->set(SUBGROUP_TREE_FIELD, $tree)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function clearLeafData(EntityInterface $entity, $save) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    $entity
      ->set(SUBGROUP_DEPTH_FIELD, NULL)
      ->set(SUBGROUP_LEFT_FIELD, NULL)
      ->set(SUBGROUP_RIGHT_FIELD, NULL)
      ->set(SUBGROUP_TREE_FIELD, NULL);

    if ($save) {
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doInitTree(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    $group_type = $entity->getGroupType();

    if (!$this->groupTypeHandler->isLeaf($group_type)) {
      throw new InvalidRootException('Trying to initialize a tree for a group whose group type is not part of a tree structure.');
    }
    if (!$this->groupTypeHandler->isRoot($group_type)) {
      throw new InvalidRootException('Trying to initialize a tree for a group whose group type is not configured as a tree root.');
    }

    parent::doInitTree($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doAddLeaf(EntityInterface $parent, EntityInterface $child) {
    // We should never be able to add groups that have existed for a while as
    // leafs as you should only be able to create subgroups, not add existing
    // groups as subgroups. 60 seconds seems plenty for a request that created
    // the group to get to this point of adding it as a subgroup.
    /** @var \Drupal\group\Entity\GroupInterface $child */
    $leaf_lifetime = $this->time->getCurrentTime() - $child->getCreatedTime();
    assert($leaf_lifetime <= 60);

    if ($this->groupTypeHandler->getParent($child->getGroupType())->id() !== $parent->bundle()) {
      throw new InvalidParentException('Provided group cannot be added as a leaf to the parent (incompatible group types).');
    }

    parent::doAddLeaf($parent, $child);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDepthPropertyName() {
    return SUBGROUP_DEPTH_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLeftPropertyName() {
    return SUBGROUP_LEFT_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightPropertyName() {
    return SUBGROUP_RIGHT_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTreePropertyName() {
    return SUBGROUP_TREE_FIELD;
  }

}
