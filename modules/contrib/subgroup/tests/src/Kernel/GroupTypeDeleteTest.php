<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests the safety measures regarding group type deletion.
 *
 * @group subgroup
 */
class GroupTypeDeleteTest extends SubgroupKernelTestBase {

  /**
   * The subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->subgroupHandler = $this->entityTypeManager->getHandler('group_type', 'subgroup');

    // Grant delete access for the current user.
    $this->setCurrentUser($this->createUser([], ['administer group']));
  }

  /**
   * Tests whether regular delete access still works.
   */
  public function testRegularDeleteAccess() {
    $group_type = $this->createGroupType();
    $this->assertTrue($group_type->access('delete'), 'Group type can be deleted just fine.');
  }

  /**
   * Tests delete access for a group leaf without descendants.
   */
  public function testLeafWithoutDescendantsDeleteAccess() {
    $group_type = $this->createGroupType();
    $this->subgroupHandler->initTree($group_type);
    $this->assertTrue($group_type->access('delete'), 'Group type can be deleted just fine.');
  }

  /**
   * Tests delete access for a group leaf without descendants.
   */
  public function testLeafWithDescendantsDeleteAccess() {
    $parent = $this->createGroupType();
    $child = $this->createGroupType();
    $this->subgroupHandler->initTree($parent);
    $this->subgroupHandler->addLeaf($parent, $child);

    /** @var \Drupal\Core\Access\AccessResultForbidden $access */
    $access = $parent->access('delete', NULL, TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access, 'Group type delete access check returned an AccessResultForbidden.');
    $this->assertEquals('Cannot delete a leaf that still has descendants.', $access->getReason());
  }

  /**
   * Tests whether regular deletes still work.
   */
  public function testRegularDelete() {
    $group_type = $this->createGroupType();
    $group_type->delete();
    $this->assertNull($this->groupTypeStorage->load($group_type->id()), 'Group type was deleted just fine.');
  }

  /**
   * Tests the deletion of a group leaf without descendants.
   */
  public function testLeafWithoutDescendantsDelete() {
    $group_type = $this->createGroupType();
    $this->subgroupHandler->initTree($group_type);
    $group_type->delete();
    $this->assertNull($this->groupTypeStorage->load($group_type->id()), 'Group type was deleted just fine.');
  }

  /**
   * Tests the deletion of a group leaf without descendants.
   */
  public function testLeafWithDescendantsDelete() {
    $parent = $this->createGroupType();
    $child = $this->createGroupType();
    $this->subgroupHandler->initTree($parent);
    $this->subgroupHandler->addLeaf($parent, $child);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot delete a leaf that still has descendants.');
    $parent->delete();
  }

}
