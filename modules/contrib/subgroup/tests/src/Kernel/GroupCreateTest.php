<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Access\AccessResultForbidden;

/**
 * Tests the safety measures regarding group creation.
 *
 * @group subgroup
 */
class GroupCreateTest extends SubgroupKernelTestBase {

  /**
   * The access control handler to use in testing.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessControlHandler = $this->entityTypeManager->getAccessControlHandler('group');

    // Set up two group types to form a tree.
    $foo = $this->createGroupType(['id' => 'foo']);
    $bar = $this->createGroupType(['id' => 'bar']);

    // Grant create access for the current user.
    $this->setCurrentUser($this->createUser([], ['create foo group', 'create bar group']));

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($foo);
    $group_type_handler->addLeaf($foo, $bar);
  }

  /**
   * Tests whether regular create access still works.
   */
  public function testRegularCreateAccess() {
    $this->createGroupType(['id' => 'baz']);
    $this->setCurrentUser($this->createUser([], ['create baz group']));
    $this->assertTrue($this->accessControlHandler->createAccess('baz'), 'Group can be created just fine.');
  }

  /**
   * Tests create access for a group whose type is a tree root.
   */
  public function testTreeRootCreateAccess() {
    $this->assertTrue($this->accessControlHandler->createAccess('foo'), 'Group can be created just fine.');
  }

  /**
   * Tests create access for a group whose type is a tree leaf.
   */
  public function testLeafCreateAccess() {
    $access = $this->accessControlHandler->createAccess('bar', NULL, [], TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access, 'Group create access check returned an AccessResultForbidden.');
    /** @var \Drupal\Core\Access\AccessResultForbidden $access */
    $this->assertEquals('Cannot create a group globally if its group type is a non-root leaf of a tree.', $access->getReason());
  }

}
