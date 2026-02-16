<?php

namespace Drupal\Tests\subgroup\Kernel;

/**
 * Tests that Subgroup properly hands out group permissions.
 *
 * @group subgroup
 */
class InheritedGroupPermissionCalculatorTest extends SubgroupKernelTestBase {

  /**
   * The account to use in testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up some group types to form a tree.
    $gt1 = $this->createGroupType(['id' => 'gt1']);
    $gt2 = $this->createGroupType(['id' => 'gt2']);
    $gt3 = $this->createGroupType(['id' => 'gt3']);

    $gt1->getMemberRole()->grantPermission('view group')->save();
    $gt2->getMemberRole()->grantPermission('view group')->save();
    $gt3->getMemberRole()->grantPermission('view group')->save();

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($gt1);
    $group_type_handler->addLeaf($gt1, $gt2);
    $group_type_handler->addLeaf($gt2, $gt3);

    $this->account = $this->createUser();
  }

  /**
   * Tests that regular access checks still work.
   */
  public function testRegularAccess() {
    $cat = $this->createGroupType(['id' => 'cat']);
    $dog = $this->createGroupType(['id' => 'dog']);
    $cat->getMemberRole()->grantPermission('view group')->save();
    $dog->getMemberRole()->grantPermission('view group')->save();

    $group_cat = $this->createGroup(['type' => 'cat']);
    $group_dog = $this->createGroup(['type' => 'cat']);
    $group_cat->addMember($this->account);
    $this->assertTrue($group_cat->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_dog->hasPermission('view group', $this->account), 'Regular permissions still apply.');
  }

  /**
   * Tests that regular access checks still work if no inheritance is set up.
   */
  public function testTreeRegularAccess() {
    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);
    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Regular permissions still apply.');
  }

  /**
   * Tests that access checks work for inherited descendant roles.
   */
  public function testInheritedDescendantAccess() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt2-member',
      'target' => 'gt3-member',
    ]));

    // Set up some groups to form a tree as pictured below:
    // . . . . . 1 . . . . .
    // . . . . / . \ . . . .
    // . . . 2 . . . 3 . . .
    // . . / . \ . . . \ . .
    // . 4 . . . 5 . . . 6 .
    $group_1 = $this->createGroup(['type' => 'gt1']);
    $group_2 = $this->createGroup(['type' => 'gt2']);
    $group_3 = $this->createGroup(['type' => 'gt2']);
    $group_4 = $this->createGroup(['type' => 'gt3']);
    $group_5 = $this->createGroup(['type' => 'gt3']);
    $group_6 = $this->createGroup(['type' => 'gt3']);

    $group_1->addContent($group_2, 'subgroup:gt2');
    $group_1->addContent($group_3, 'subgroup:gt2');
    $group_2->addContent($group_4, 'subgroup:gt3');
    $group_2->addContent($group_5, 'subgroup:gt3');
    $group_3->addContent($group_6, 'subgroup:gt3');

    $group_2->addMember($this->account);
    $this->assertTrue($group_2->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_4->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
    $this->assertTrue($group_5->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
    $this->assertFalse($group_6->hasPermission('view group', $this->account), 'Account did not inherit member role from uncle group.');

    $account_2 = $this->createUser();
    $group_4->addMember($account_2);
    $this->assertFalse($group_2->hasPermission('view group', $account_2), 'Direction of inheritance is respected.');
    $this->assertTrue($group_4->hasPermission('view group', $account_2), 'Regular permissions still apply.');
  }

  /**
   * Tests that access checks work for inherited ancestor roles.
   */
  public function testInheritedAncestorAccess() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt3-member',
      'target' => 'gt2-member',
    ]));

    // Set up some groups to form a tree as pictured below:
    // . . . . . 1 . . . . .
    // . . . . / . \ . . . .
    // . . . 2 . . . 3 . . .
    // . . / . \ . . . \ . .
    // . 4 . . . 5 . . . 6 .
    $group_1 = $this->createGroup(['type' => 'gt1']);
    $group_2 = $this->createGroup(['type' => 'gt2']);
    $group_3 = $this->createGroup(['type' => 'gt2']);
    $group_4 = $this->createGroup(['type' => 'gt3']);
    $group_5 = $this->createGroup(['type' => 'gt3']);
    $group_6 = $this->createGroup(['type' => 'gt3']);

    $group_1->addContent($group_2, 'subgroup:gt2');
    $group_1->addContent($group_3, 'subgroup:gt2');
    $group_2->addContent($group_4, 'subgroup:gt3');
    $group_2->addContent($group_5, 'subgroup:gt3');
    $group_3->addContent($group_6, 'subgroup:gt3');

    $group_4->addMember($this->account);
    $this->assertTrue($group_4->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_2->hasPermission('view group', $this->account), 'Account inherited member role from child group.');
    $this->assertFalse($group_3->hasPermission('view group', $this->account), 'Account did not inherit member role from cousin group.');

    $account_2 = $this->createUser();
    $group_2->addMember($account_2);
    $this->assertFalse($group_4->hasPermission('view group', $account_2), 'Direction of inheritance is respected.');
    $this->assertTrue($group_2->hasPermission('view group', $account_2), 'Regular permissions still apply.');
  }

  /**
   * Tests that permissions are updated when a new inheritance is set up.
   */
  public function testNewInheritance() {
    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a member status changes.
   */
  public function testNewMember() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    $this->assertFalse($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $group_gt1->addMember($this->account);

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a group leaf status changes.
   */
  public function testChangedLeaf() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    // This might seem out of place, but it's to circumvent an annoying core
    // issue where entity reference fields become stale.
    // @see https://www.drupal.org/project/drupal/issues/3154443.
    $this->entityTypeManager->getStorage('group_content')->resetCache();

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a new leaf is added to the tree.
   */
  public function testNewLeaf() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_gt1);

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $group_handler->addLeaf($group_gt1, $group_gt2);

    // This might seem out of place, but it's to circumvent an annoying core
    // issue where entity reference fields become stale.
    // @see https://www.drupal.org/project/drupal/issues/3154443.
    $this->entityTypeManager->getStorage('group_content')->resetCache();

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a new role is assigned.
   */
  public function testNewMemberRole() {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $storage->save($storage->create([
      'id' => 'gt1-viewer',
      'label' => 'Viewer',
      'audience' => 'member',
      'group_type' => 'gt1',
    ])->grantPermission('view group'));

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-viewer',
      'target' => 'gt2-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $membership = $group_gt1->getMember($this->account)->getGroupContent();
    $membership->group_roles[] = 'gt1-viewer';
    $membership->save();

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a role is changed.
   */
  public function testChangedRolePermissions() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');

    $storage = $this->entityTypeManager->getStorage('group_role');
    $storage->save($storage->load('gt2-member')->revokePermission('view group'));

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group, but permission was revoked.');
  }

  /**
   * Tests that circular references are not followed and thus fine.
   */
  public function testCircularReference() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt2-member',
      'target' => 'gt1-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that role collisions do not lead to errors or exceptions.
   */
  public function testRoleCollisions() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'gt1-member',
      'target' => 'gt2-member',
    ]));

    $group_gt1 = $this->createGroup(['type' => 'gt1']);
    $group_gt2 = $this->createGroup(['type' => 'gt2']);
    $group_gt1->addMember($this->account);
    $group_gt2->addMember($this->account);
    $group_gt1->addContent($group_gt2, 'subgroup:gt2');

    $this->assertTrue($group_gt1->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_gt2->hasPermission('view group', $this->account), 'Account inherited member role from parent group and has regular access.');
  }

}
