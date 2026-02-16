<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\subgroup\InvalidLeafException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subgroup handler for GroupType entities.
 */
class GroupTypeSubgroupHandler extends SubgroupHandlerBase {

  /**
   * The Group storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $groupStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->groupStorage = $container->get('entity_type.manager')->getStorage('group');
    return $instance;
  }

  /**
   * Checks whether there are groups of a given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to run the check for.
   *
   * @return bool
   *   Whether there are groups for the group type.
   */
  protected function groupTypeHasGroups(GroupTypeInterface $group_type) {
    return (bool) $this->groupStorage
      ->getQuery()
      ->condition('type', $group_type->id())
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function writeLeafData(EntityInterface $entity, $depth, $left, $right, $tree) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $entity */
    $entity
      ->setThirdPartySetting('subgroup', SUBGROUP_DEPTH_SETTING, $depth)
      ->setThirdPartySetting('subgroup', SUBGROUP_LEFT_SETTING, $left)
      ->setThirdPartySetting('subgroup', SUBGROUP_RIGHT_SETTING, $right)
      ->setThirdPartySetting('subgroup', SUBGROUP_TREE_SETTING, $tree)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function clearLeafData(EntityInterface $entity, $save) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $entity */
    $entity
      ->unsetThirdPartySetting('subgroup', SUBGROUP_DEPTH_SETTING)
      ->unsetThirdPartySetting('subgroup', SUBGROUP_LEFT_SETTING)
      ->unsetThirdPartySetting('subgroup', SUBGROUP_RIGHT_SETTING)
      ->unsetThirdPartySetting('subgroup', SUBGROUP_TREE_SETTING);

    if ($save) {
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doAddLeaf(EntityInterface $parent, EntityInterface $child) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $child */
    if ($this->groupTypeHasGroups($child)) {
      throw new InvalidLeafException('Cannot use a group type that already has groups as a leaf.');
    }

    parent::doAddLeaf($parent, $child);
  }

  /**
   * {@inheritdoc}
   */
  protected function doRemoveLeaf(EntityInterface $entity, $save) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $entity */
    if (!$this->isRoot($entity) && $this->groupTypeHasGroups($entity)) {
      throw new InvalidLeafException('Cannot remove leaf status from a group type that still has groups.');
    }

    parent::doRemoveLeaf($entity, $save);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDepthPropertyName() {
    return 'third_party_settings.subgroup.' . SUBGROUP_DEPTH_SETTING;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLeftPropertyName() {
    return 'third_party_settings.subgroup.' . SUBGROUP_LEFT_SETTING;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightPropertyName() {
    return 'third_party_settings.subgroup.' . SUBGROUP_RIGHT_SETTING;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTreePropertyName() {
    return 'third_party_settings.subgroup.' . SUBGROUP_TREE_SETTING;
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
      ->accessCheck(FALSE)
      ->execute();

    assert(count($entity_ids) >= 1);
    return $this->sortByLeftProperty($entity_ids);
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
      ->accessCheck(FALSE)
      ->execute();

    return $this->sortByLeftProperty($entity_ids);
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
      ->accessCheck(FALSE)
      ->execute();

    return $this->sortByLeftProperty($entity_ids);
  }

  /**
   * Work-around for a core bug regarding dotted path sorting.
   *
   * @param string[] $entity_ids
   *   Group type IDs to load and sort by left property.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface[]
   *   A list of sorted group types.
   *
   * @todo Remove this along with ::getAncestors(), ::getChildren() and
   * ::getDescendants() as soon as the core fix is released.
   *
   * @see https://www.drupal.org/project/drupal/issues/2942569
   */
  private function sortByLeftProperty(array $entity_ids) {
    $group_types = [];

    if (!empty($entity_ids)) {
      $group_types = $this->storage->loadMultiple($entity_ids);
      uasort($group_types, function (GroupTypeInterface $a, GroupTypeInterface $b) {
        return $a->getThirdPartySetting('subgroup', 'left') <=> $b->getThirdPartySetting('subgroup', 'left');
      });
    }

    return $group_types;
  }

}
