<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Defines an abstract test base for Subgroup kernel tests.
 */
abstract class SubgroupKernelTestBase extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['subgroup', 'group', 'options', 'entity', 'variationcache'];

  /**
   * The group storage to use in testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $groupStorage;

  /**
   * The group type storage to use in testing.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $groupTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installConfig(['group', 'subgroup']);

    $this->groupStorage = $this->entityTypeManager->getStorage('group');
    $this->groupTypeStorage = $this->entityTypeManager->getStorage('group_type');

    // Make sure we do not use user 1.
    $this->createUser();
    $this->setCurrentUser($this->createUser());
  }

  /**
   * Gets the current user so you can run some checks against them.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function getCurrentUser() {
    return $this->container->get('current_user')->getAccount();
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup(array $values = []) {
    $group = $this->groupStorage->create($values + [
      'type' => 'default',
      'label' => $this->randomString(),
    ]);
    $group->enforceIsNew();
    $this->groupStorage->save($group);
    return $group;
  }

  /**
   * Creates a group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The created group type entity.
   */
  protected function createGroupType(array $values = []) {
    $group_type = $this->groupTypeStorage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $this->groupTypeStorage->save($group_type);
    return $group_type;
  }

  /**
   * Writes the provided leaf data onto the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to write the data onto.
   * @param int $depth
   *   The depth.
   * @param int $left
   *   The left boundary.
   * @param int $right
   *   The right boundary.
   * @param int|string $tree
   *   The tree ID.
   */
  protected function writeGroupLeafData(GroupInterface $group, $depth, $left, $right, $tree) {
    $group
      ->set(SUBGROUP_DEPTH_FIELD, $depth)
      ->set(SUBGROUP_LEFT_FIELD, $left)
      ->set(SUBGROUP_RIGHT_FIELD, $right)
      ->set(SUBGROUP_TREE_FIELD, $tree)
      ->save();
  }

  /**
   * Writes the provided leaf data onto the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to write the data onto.
   * @param int $depth
   *   The depth.
   * @param int $left
   *   The left boundary.
   * @param int $right
   *   The right boundary.
   * @param int|string $tree
   *   The tree ID.
   */
  protected function writeGroupTypeLeafData(GroupTypeInterface $group_type, $depth, $left, $right, $tree) {
    $group_type
      ->setThirdPartySetting('subgroup', SUBGROUP_DEPTH_SETTING, $depth)
      ->setThirdPartySetting('subgroup', SUBGROUP_LEFT_SETTING, $left)
      ->setThirdPartySetting('subgroup', SUBGROUP_RIGHT_SETTING, $right)
      ->setThirdPartySetting('subgroup', SUBGROUP_TREE_SETTING, $tree)
      ->save();
  }

  /**
   * Clears the leaf data from the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to clear the data from.
   */
  protected function clearGroupLeafData(GroupInterface $group) {
    $group
      ->set(SUBGROUP_DEPTH_FIELD, NULL)
      ->set(SUBGROUP_LEFT_FIELD, NULL)
      ->set(SUBGROUP_RIGHT_FIELD, NULL)
      ->set(SUBGROUP_TREE_FIELD, NULL)
      ->save();
  }

  /**
   * Clears the leaf data from the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to clear the data from.
   */
  protected function clearGroupTypeLeafData(GroupTypeInterface $group_type) {
    $group_type
      ->unsetThirdPartySetting('subgroup', SUBGROUP_DEPTH_SETTING)
      ->unsetThirdPartySetting('subgroup', SUBGROUP_LEFT_SETTING)
      ->unsetThirdPartySetting('subgroup', SUBGROUP_RIGHT_SETTING)
      ->unsetThirdPartySetting('subgroup', SUBGROUP_TREE_SETTING)
      ->save();
  }

  /**
   * Toggles the tree leaf status of the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to toggle the status for.
   * @param bool $is_tree
   *   Whether the group type acts as a tree leaf or not.
   */
  protected function toggleTreeStatus(GroupTypeInterface $group_type, $is_tree) {
    if ($is_tree) {
      $this->writeGroupTypeLeafData($group_type, 0, 1, 2, $group_type->id());
    }
    else {
      $this->clearGroupTypeLeafData($group_type);
    }
  }

}
