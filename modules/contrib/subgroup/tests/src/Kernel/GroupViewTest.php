<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Tests that group viewing works as expected.
 *
 * @group subgroup
 */
class GroupViewTest extends SubgroupKernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['subgroup_test_views', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('subgroup_test_views');

    // Set up two group types to form a tree.
    $foo = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $bar = $this->createGroupType(['id' => 'bar', 'creator_membership' => FALSE]);

    // Grant delete access for members.
    $foo->getMemberRole()->grantPermission('view group')->save();
    $bar->getMemberRole()->grantPermission('view group')->save();

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($foo);
    $group_type_handler->addLeaf($foo, $bar);
  }

  /**
   * Tests whether regular view access still works.
   */
  public function testRegularViewAccess() {
    $group_type = $this->createGroupType();
    $group_type->getMemberRole()->grantPermission('view group')->save();
    $group = $this->createGroup(['type' => $group_type->id()]);
    $this->assertTrue($group->access('view'), 'Group can be viewed just fine.');
    $this->assertQueryAccessResult([$group->id()], 'Group can be viewed just fine.');
  }

  /**
   * Tests view access for a group that could be a leaf, but isn't.
   */
  public function testNoLeafViewAccess() {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group');

    $group = $this->createGroup(['type' => 'foo']);
    $this->assertFalse($group->access('view'), 'Group cannot be viewed without access.');
    $this->assertQueryAccessResult([], 'Queries show no groups without access.');

    $group->addMember($this->getCurrentUser());
    $access_control_handler->resetCache();
    $this->assertTrue($group->access('view'), 'Group can be viewed just fine.');
    $this->assertQueryAccessResult([$group->id()], 'Group can be viewed just fine.');
  }

  /**
   * Tests view access for a group that is part of a tree.
   */
  public function testLeafViewAccess() {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group');

    $parent = $this->createGroup(['type' => 'foo']);
    $child = $this->createGroup(['type' => 'bar']);
    $parent->addContent($child, 'subgroup:bar');

    $this->assertFalse($parent->access('view'), 'Parent group cannot be viewed.');
    $this->assertFalse($child->access('view'), 'Child group cannot be viewed.');
    $this->assertQueryAccessResult([], 'Neither group can be viewed.');

    $parent->addMember($this->getCurrentUser());
    $access_control_handler->resetCache();
    $this->assertTrue($parent->access('view'), 'Parent group can be viewed.');
    $this->assertFalse($child->access('view'), 'Child group cannot be viewed.');
    $this->assertQueryAccessResult([$parent->id()], 'Parent group can be viewed, but child group is not visible.');

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));
    $access_control_handler->resetCache();
    $this->assertTrue($parent->access('view'), 'Parent group can be viewed.');
    $this->assertTrue($child->access('view'), 'Child group can be viewed.');
    $this->assertQueryAccessResult([$parent->id(), $child->id()], 'Both groups are visible.');
  }

  /**
   * Asserts that the query and view returns the expected results.
   *
   * @param int[] $expected
   *   The expected test entity IDs.
   * @param string $message
   *   The message for the assertion.
   */
  protected function assertQueryAccessResult(array $expected, $message) {
    $ids = $this->groupStorage->getQuery()->execute();
    $this->assertEqualsCanonicalizing($expected, array_keys($ids), $message);

    $views_expected = [];
    foreach ($expected as $value) {
      $views_expected[] = ['id' => $value];
    }
    $view = Views::getView('group_list');
    $view->execute();
    $this->assertIdenticalResultsetHelper($view, $views_expected, ['id' => 'id'], 'assertEqualsCanonicalizing', $message);
  }

}
