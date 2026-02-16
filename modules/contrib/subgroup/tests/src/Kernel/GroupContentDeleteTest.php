<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests the safety measures regarding group content deletion.
 *
 * @group subgroup
 */
class GroupContentDeleteTest extends SubgroupKernelTestBase {

  /**
   * The subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * The group content storage to use in testing.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->subgroupHandler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $this->storage = $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * Tests whether regular delete access still works.
   */
  public function testRegularDeleteAccess() {
    $group_type = $this->createGroupType();
    $group_type->getMemberRole()->grantPermission('administer members')->save();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $group_content = $this->storage->createForEntityInGroup($this->createUser(), $group, 'group_membership', []);
    $this->storage->save($group_content);

    $this->assertTrue($group_content->access('delete'), 'Group content can be deleted just fine.');
  }

  /**
   * Tests whether deleting group content for a leaf is not allowed.
   */
  public function testLeafDeleteAccess() {
    $group_type_parent = $this->createGroupType();
    $group_type_child = $this->createGroupType();
    $this->subgroupHandler->initTree($group_type_parent);
    $this->subgroupHandler->addLeaf($group_type_parent, $group_type_child);
    $group_parent = $this->createGroup(['type' => $group_type_parent->id()]);
    $group_child = $this->createGroup(['type' => $group_type_child->id()]);

    $group_content = $this->storage->createForEntityInGroup($group_child, $group_parent, 'subgroup:' . $group_type_child->id(), []);
    $this->storage->save($group_content);

    /** @var \Drupal\Core\Access\AccessResultForbidden $access */
    $access = $group_content->access('delete', NULL, TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access, 'Group content delete access check returned an AccessResultForbidden.');
    $this->assertEquals('Cannot delete a subgroup group content entity directly.', $access->getReason());
  }

  /**
   * Tests whether regular deletes still work.
   */
  public function testRegularDelete() {
    $group_type = $this->createGroupType();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $group_content = $this->storage->createForEntityInGroup($this->createUser(), $group, 'group_membership', []);
    $this->storage->save($group_content);
    $this->storage->delete([$group_content]);

    $this->assertNull($this->storage->load($group_content->id()), 'Group content was deleted just fine.');
  }

  /**
   * Tests whether you can delete if the group no longer exists.
   */
  public function testLeafDeleteWithoutGroup() {
    $group_type_parent = $this->createGroupType();
    $group_type_child = $this->createGroupType();
    $this->subgroupHandler->initTree($group_type_parent);
    $this->subgroupHandler->addLeaf($group_type_parent, $group_type_child);
    $group_parent = $this->createGroup(['type' => $group_type_parent->id()]);
    $group_child = $this->createGroup(['type' => $group_type_child->id()]);

    $group_content = $this->storage->createForEntityInGroup($group_child, $group_parent, 'subgroup:' . $group_type_child->id(), []);
    $this->storage->save($group_content);
    $group_child->delete();

    $this->assertNull($this->storage->load($group_content->id()), 'Group content was deleted along with its group just fine.');
  }

  /**
   * Tests whether you cannot delete if the group still exists.
   */
  public function testLeafDeleteWithGroupException() {
    $group_type_parent = $this->createGroupType();
    $group_type_child = $this->createGroupType();
    $this->subgroupHandler->initTree($group_type_parent);
    $this->subgroupHandler->addLeaf($group_type_parent, $group_type_child);
    $group_parent = $this->createGroup(['type' => $group_type_parent->id()]);
    $group_child = $this->createGroup(['type' => $group_type_child->id()]);

    $group_content = $this->storage->createForEntityInGroup($group_child, $group_parent, 'subgroup:' . $group_type_child->id(), []);
    $this->storage->save($group_content);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot delete a subgroup group content entity if its group still exists.');
    $this->storage->delete([$group_content]);
  }

}
