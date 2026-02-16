<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Access\AccessResultForbidden;

/**
 * Tests the general behavior of role inheritance access control handler.
 *
 * @coversDefaultClass \Drupal\subgroup\Entity\RoleInheritanceAccessControlHandler
 * @group subgroup
 */
class RoleInheritanceAccessControlHandlerTest extends SubgroupKernelTestBase {

  /**
   * The access control handler to use in testing.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessControlHandler;

  /**
   * The role inheritance entity to run tests on.
   *
   * @var \Drupal\subgroup\Entity\RoleInheritanceInterface
   */
  protected $roleInheritance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessControlHandler = $this->entityTypeManager->getAccessControlHandler('subgroup_role_inheritance');

    // Set up two group types to form a tree.
    $foo = $this->createGroupType(['id' => 'foo']);
    $bar = $this->createGroupType(['id' => 'bar']);

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($foo);
    $group_type_handler->addLeaf($foo, $bar);

    $this->roleInheritance = $this->entityTypeManager->getStorage('subgroup_role_inheritance')->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]);
    $this->roleInheritance->save();
  }

  /**
   * Tests create access.
   */
  public function testCreateAccess() {
    $access = $this->accessControlHandler->createAccess(NULL, NULL, [], TRUE);
    $this->assertFalse($access->isAllowed());
    $this->setCurrentUser($this->createUser([], ['administer subgroup']));
    $access = $this->accessControlHandler->createAccess(NULL, NULL, [], TRUE);
    $this->assertTrue($access->isAllowed());
  }

  /**
   * Tests update access.
   *
   * @covers ::access
   */
  public function testUpdateAccess() {
    /** @var \Drupal\Core\Access\AccessResultForbidden $access */
    $access = $this->accessControlHandler->access($this->roleInheritance, 'update', NULL, TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access, 'Group create access check returned an AccessResultForbidden.');
    $this->assertEquals('Role inheritance entities may not be updated after creation.', $access->getReason());

    $this->setCurrentUser($this->createUser([], ['administer subgroup']));
    $access = $this->accessControlHandler->access($this->roleInheritance, 'update', NULL, TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access, 'Group create access check returned an AccessResultForbidden.');
    $this->assertEquals('Role inheritance entities may not be updated after creation.', $access->getReason());
  }

  /**
   * Tests delete access.
   *
   * @covers ::access
   */
  public function testDeleteAccess() {
    $access = $this->accessControlHandler->access($this->roleInheritance, 'delete', NULL, TRUE);
    $this->assertFalse($access->isAllowed());

    $this->setCurrentUser($this->createUser([], ['administer subgroup']));
    $access = $this->accessControlHandler->access($this->roleInheritance, 'delete', NULL, TRUE);
    $this->assertTrue($access->isAllowed());
  }

}
