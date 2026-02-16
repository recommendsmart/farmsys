<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\subgroup\InvalidLeafException;

/**
 * Tests the subgroup handler for GroupType entities.
 *
 * @coversDefaultClass \Drupal\subgroup\Entity\GroupTypeSubgroupHandler
 * @group subgroup
 */
class GroupTypeSubgroupHandlerTest extends SubgroupKernelTestBase {

  /**
   * The subgroup handler to run tests on.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * The root group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeRoot;

  /**
   * The left child group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeChildLeft;

  /**
   * The middle child group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeChildMiddle;

  /**
   * The right child group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeChildRight;

  /**
   * The left grandchild group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeGrandchildLeft;

  /**
   * The right grandchild group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeGrandchildRight;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->subgroupHandler = $this->entityTypeManager->getHandler('group_type', 'subgroup');

    $this->groupTypeRoot = $this->createGroupType(['id' => 'root']);
    $this->groupTypeChildLeft = $this->createGroupType(['id' => 'child_left']);
    $this->groupTypeChildMiddle = $this->createGroupType(['id' => 'child_middle']);
    $this->groupTypeChildRight = $this->createGroupType(['id' => 'child_right']);
    $this->groupTypeGrandchildLeft = $this->createGroupType(['id' => 'grandchild_left']);
    $this->groupTypeGrandchildRight = $this->createGroupType(['id' => 'grandchild_right']);

    $this->writeGroupTypeLeafData($this->groupTypeRoot, 0, 1, 12, 'root');
    $this->writeGroupTypeLeafData($this->groupTypeChildLeft, 1, 2, 5, 'root');
    $this->writeGroupTypeLeafData($this->groupTypeGrandchildLeft, 2, 3, 4, 'root');
    $this->writeGroupTypeLeafData($this->groupTypeChildMiddle, 1, 6, 7, 'root');
    $this->writeGroupTypeLeafData($this->groupTypeChildRight, 1, 8, 11, 'root');
    $this->writeGroupTypeLeafData($this->groupTypeGrandchildRight, 2, 9, 10, 'root');
  }

  /**
   * Tests the handler-specific details of initTree().
   *
   * @covers ::initTree
   * @covers ::writeLeafData
   */
  public function testInitTree() {
    $group_type = $this->createGroupType();

    // For extra hardening we check the fields directly.
    $root_field_data = [
      SUBGROUP_DEPTH_SETTING => 0,
      SUBGROUP_LEFT_SETTING => 1,
      SUBGROUP_RIGHT_SETTING => 2,
      SUBGROUP_TREE_SETTING => $group_type->id(),
    ];

    $this->assertFalse($this->subgroupHandler->isLeaf($group_type));
    foreach ($root_field_data as $field_name => $field_data) {
      $this->assertNull($group_type->getThirdPartySetting('subgroup', $field_name));
    }

    $this->subgroupHandler->initTree($group_type);

    $this->assertTrue($this->subgroupHandler->isLeaf($group_type));
    $this->assertTrue($this->subgroupHandler->isRoot($group_type));
    foreach ($root_field_data as $field_name => $field_data) {
      $this->assertEquals($field_data, $group_type->getThirdPartySetting('subgroup', $field_name));
    }
  }

  /**
   * Tests the handler-specific details of addLeaf().
   *
   * @covers ::addLeaf
   * @covers ::writeLeafData
   */
  public function testAddLeaf() {
    $grandparent = $this->subgroupHandler->wrapLeaf($this->groupTypeRoot);
    $left_uncle = $this->subgroupHandler->wrapLeaf($this->groupTypeChildLeft);
    $left_cousin = $this->subgroupHandler->wrapLeaf($this->groupTypeGrandchildLeft);
    $right_uncle = $this->subgroupHandler->wrapLeaf($this->groupTypeChildRight);
    $right_cousin = $this->subgroupHandler->wrapLeaf($this->groupTypeGrandchildRight);

    $parent = $this->subgroupHandler->wrapLeaf($this->groupTypeChildMiddle);
    $original_parent_depth = $parent->getDepth();
    $original_parent_left = $parent->getLeft();
    $original_parent_right = $parent->getRight();
    $original_parent_tree = $parent->getTree();

    $group_type = $this->createGroupType(['id' => 'grandchild_middle']);
    $this->subgroupHandler->addLeaf($this->groupTypeChildMiddle, $group_type);
    $child = $this->subgroupHandler->wrapLeaf($group_type);

    $grandparent_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeRoot->id()));
    $left_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildLeft->id()));
    $left_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeGrandchildLeft->id()));
    $right_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildRight->id()));
    $right_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeGrandchildRight->id()));

    $this->assertEquals($grandparent->getDepth(), $grandparent_reloaded->getDepth(), 'Grandparent depth remained untouched.');
    $this->assertEquals($grandparent->getLeft(), $grandparent_reloaded->getLeft(), 'Grandparent left bound remained untouched.');
    $this->assertEquals($grandparent->getRight() + 2, $grandparent_reloaded->getRight(), 'Grandparent right bound was incremented by 2.');
    $this->assertEquals($grandparent->getTree(), $grandparent_reloaded->getTree(), 'Grandparent tree ID remained untouched.');

    $this->assertEquals($left_uncle->getDepth(), $left_uncle_reloaded->getDepth(), 'Left uncle depth remained untouched.');
    $this->assertEquals($left_uncle->getLeft(), $left_uncle_reloaded->getLeft(), 'Left uncle left bound remained untouched.');
    $this->assertEquals($left_uncle->getRight(), $left_uncle_reloaded->getRight(), 'Left uncle right bound remained untouched.');
    $this->assertEquals($left_uncle->getTree(), $left_uncle_reloaded->getTree(), 'Left uncle tree ID remained untouched.');

    $this->assertEquals($left_cousin->getDepth(), $left_cousin_reloaded->getDepth(), 'Left cousin depth remained untouched.');
    $this->assertEquals($left_cousin->getLeft(), $left_cousin_reloaded->getLeft(), 'Left cousin left bound remained untouched.');
    $this->assertEquals($left_cousin->getRight(), $left_cousin_reloaded->getRight(), 'Left cousin right bound remained untouched.');
    $this->assertEquals($left_cousin->getTree(), $left_cousin_reloaded->getTree(), 'Left cousin tree ID remained untouched.');

    $this->assertEquals($original_parent_depth, $parent->getDepth(), 'Parent depth remained untouched.');
    $this->assertEquals($original_parent_left, $parent->getLeft(), 'Parent left bound remained untouched.');
    $this->assertEquals($original_parent_right + 2, $parent->getRight(), 'Parent right bound was incremented by 2.');
    $this->assertEquals($original_parent_tree, $parent->getTree(), 'Parent tree ID remained untouched.');

    $this->assertEquals($right_uncle->getDepth(), $right_uncle_reloaded->getDepth(), 'Right uncle depth remained untouched.');
    $this->assertEquals($right_uncle->getLeft() + 2, $right_uncle_reloaded->getLeft(), 'Right uncle left bound was incremented by 2.');
    $this->assertEquals($right_uncle->getRight() + 2, $right_uncle_reloaded->getRight(), 'Right uncle right bound was incremented by 2.');
    $this->assertEquals($right_uncle->getTree(), $right_uncle_reloaded->getTree(), 'Right uncle tree ID remained untouched.');

    $this->assertEquals($right_cousin->getDepth(), $right_cousin_reloaded->getDepth(), 'Right cousin depth remained untouched.');
    $this->assertEquals($right_cousin->getLeft() + 2, $right_cousin_reloaded->getLeft(), 'Right cousin left bound was incremented by 2.');
    $this->assertEquals($right_cousin->getRight() + 2, $right_cousin_reloaded->getRight(), 'Right cousin right bound was incremented by 2.');
    $this->assertEquals($right_cousin->getTree(), $right_cousin_reloaded->getTree(), 'Right cousin tree ID remained untouched.');

    $this->assertEquals($original_parent_depth + 1, $child->getDepth(), 'Child inherited parent depth incremented by 1.');
    $this->assertEquals($original_parent_right, $child->getLeft(), 'Child left bound inherited parent right bound.');
    $this->assertEquals($original_parent_right + 1, $child->getRight(), 'Child right bound inherited parent right bound incremented by 1.');
    $this->assertEquals($original_parent_tree, $child->getTree(), 'Child inherited parent tree.');
  }

  /**
   * Tests that addLeaf() installs a plugin on the parent group type.
   *
   * Already tested by the tests for GroupTypeLeafEvent and the subscriber for
   * those events. Because it's a crucial part of this module we're also kernel
   * testing it here, even though it actually has little to do with the actual
   * subgroup handler. But calling addLeaf() eventually triggers it, so here we
   * go.
   *
   * @covers ::addLeaf
   */
  public function testAddLeafInstallsPlugin() {
    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.group_content_enabler');

    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');

    $this->assertNull($plugin_manager->getDefinition('subgroup:grandchild_middle', FALSE), 'No plugin exists yet before adding the group type as a leaf.');
    $installed = $storage->loadByContentPluginId('subgroup:grandchild_middle');
    $this->assertEmpty($installed, 'Subgroup plugin was not installed before adding the group type as a leaf.');

    $group_type = $this->createGroupType(['id' => 'grandchild_middle']);
    $this->subgroupHandler->addLeaf($this->groupTypeChildMiddle, $group_type);

    $this->assertNotNull($plugin_manager->getDefinition('subgroup:grandchild_middle', FALSE), 'Plugin exists after adding the group type as a leaf.');
    $installed = $storage->loadByContentPluginId('subgroup:grandchild_middle');
    $this->assertCount(1, $installed, 'Subgroup plugin was installed after adding the group type as a leaf.');
  }

  /**
   * Tests the exception thrown when the group type still has groups.
   *
   * @covers ::doAddLeaf
   */
  public function testAddGroupTypeWithGroupsException() {
    $group_type = $this->createGroupType();
    $this->createGroup(['type' => $group_type->id()]);
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('Cannot use a group type that already has groups as a leaf.');
    $this->subgroupHandler->addLeaf($this->groupTypeChildMiddle, $group_type);
  }

  /**
   * Tests the handler-specific details of removeLeaf().
   *
   * @covers ::removeLeaf
   * @covers ::clearLeafData
   */
  public function testRemoveLeaf() {
    $group_type = $this->createGroupType(['id' => 'grandchild_middle']);
    $this->subgroupHandler->addLeaf($this->groupTypeChildMiddle, $group_type);

    $grandparent = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeRoot->id()));
    $left_uncle = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildLeft->id()));
    $left_cousin = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeGrandchildLeft->id()));
    $parent = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildMiddle->id()));
    $right_uncle = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildRight->id()));
    $right_cousin = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeGrandchildRight->id()));

    // Reset the cache so that the entity pointers are different from the ones
    // being manipulated in removeLeaf(). This will greatly facilitate checking
    // the before vs after values.
    $this->groupTypeStorage->resetCache();

    $this->subgroupHandler->removeLeaf($group_type, FALSE);

    $grandparent_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeRoot->id()));
    $left_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildLeft->id()));
    $left_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeGrandchildLeft->id()));
    $parent_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildMiddle->id()));
    $right_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeChildRight->id()));
    $right_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupTypeStorage->load($this->groupTypeGrandchildRight->id()));

    $this->assertEquals($grandparent->getDepth(), $grandparent_reloaded->getDepth(), 'Grandparent depth remained untouched.');
    $this->assertEquals($grandparent->getLeft(), $grandparent_reloaded->getLeft(), 'Grandparent left bound remained untouched.');
    $this->assertEquals($grandparent->getRight() - 2, $grandparent_reloaded->getRight(), 'Grandparent right bound was decreased by 2.');
    $this->assertEquals($grandparent->getTree(), $grandparent_reloaded->getTree(), 'Grandparent tree ID remained untouched.');

    $this->assertEquals($left_uncle->getDepth(), $left_uncle_reloaded->getDepth(), 'Left uncle depth remained untouched.');
    $this->assertEquals($left_uncle->getLeft(), $left_uncle_reloaded->getLeft(), 'Left uncle left bound remained untouched.');
    $this->assertEquals($left_uncle->getRight(), $left_uncle_reloaded->getRight(), 'Left uncle right bound remained untouched.');
    $this->assertEquals($left_uncle->getTree(), $left_uncle_reloaded->getTree(), 'Left uncle tree ID remained untouched.');

    $this->assertEquals($left_cousin->getDepth(), $left_cousin_reloaded->getDepth(), 'Left cousin depth remained untouched.');
    $this->assertEquals($left_cousin->getLeft(), $left_cousin_reloaded->getLeft(), 'Left cousin left bound remained untouched.');
    $this->assertEquals($left_cousin->getRight(), $left_cousin_reloaded->getRight(), 'Left cousin right bound remained untouched.');
    $this->assertEquals($left_cousin->getTree(), $left_cousin_reloaded->getTree(), 'Left cousin tree ID remained untouched.');

    $this->assertEquals($parent->getDepth(), $parent_reloaded->getDepth(), 'Parent depth remained untouched.');
    $this->assertEquals($parent->getLeft(), $parent_reloaded->getLeft(), 'Parent left bound remained untouched.');
    $this->assertEquals($parent->getRight() - 2, $parent_reloaded->getRight(), 'Parent right bound was decreased by 2.');
    $this->assertEquals($parent->getTree(), $parent_reloaded->getTree(), 'Parent tree ID remained untouched.');

    $this->assertEquals($right_uncle->getDepth(), $right_uncle_reloaded->getDepth(), 'Right uncle depth remained untouched.');
    $this->assertEquals($right_uncle->getLeft() - 2, $right_uncle_reloaded->getLeft(), 'Right uncle left bound was decreased by 2.');
    $this->assertEquals($right_uncle->getRight() - 2, $right_uncle_reloaded->getRight(), 'Right uncle right bound was decreased by 2.');
    $this->assertEquals($right_uncle->getTree(), $right_uncle_reloaded->getTree(), 'Right uncle tree ID remained untouched.');

    $this->assertEquals($right_cousin->getDepth(), $right_cousin_reloaded->getDepth(), 'Right cousin depth remained untouched.');
    $this->assertEquals($right_cousin->getLeft() - 2, $right_cousin_reloaded->getLeft(), 'Right cousin left bound was decreased by 2.');
    $this->assertEquals($right_cousin->getRight() - 2, $right_cousin_reloaded->getRight(), 'Right cousin right bound was decreased by 2.');
    $this->assertEquals($right_cousin->getTree(), $right_cousin_reloaded->getTree(), 'Right cousin tree ID remained untouched.');

    $this->assertNull($group_type->getThirdPartySetting('subgroup', SUBGROUP_DEPTH_SETTING), 'Child no longer has a depth value.');
    $this->assertNull($group_type->getThirdPartySetting('subgroup', SUBGROUP_LEFT_SETTING), 'Child no longer has a left bound value.');
    $this->assertNull($group_type->getThirdPartySetting('subgroup', SUBGROUP_RIGHT_SETTING), 'Child no longer has a right bound value.');
    $this->assertNull($group_type->getThirdPartySetting('subgroup', SUBGROUP_TREE_SETTING), 'Child no longer has a tree ID value.');
  }

  /**
   * Tests that removeLeaf() uninstalls a plugin from the parent group type.
   *
   * Already tested by the tests for GroupTypeLeafEvent and the subscriber for
   * those events. Because it's a crucial part of this module we're also kernel
   * testing it here, even though it actually has little to do with the actual
   * subgroup handler. But calling addLeaf() eventually triggers it, so here we
   * go.
   *
   * @covers ::removeLeaf
   * @depends testAddLeafInstallsPlugin
   */
  public function testRemoveLeafUninstallsPlugin() {
    $group_type = $this->createGroupType(['id' => 'grandchild_middle']);
    $this->subgroupHandler->addLeaf($this->groupTypeChildMiddle, $group_type);
    $this->subgroupHandler->removeLeaf($group_type);

    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.group_content_enabler');

    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');

    $this->assertNull($plugin_manager->getDefinition('subgroup:grandchild_middle', FALSE), 'No plugin exists any more after removing the group type as a leaf.');
    $installed = $storage->loadByContentPluginId('subgroup:grandchild_middle');
    $this->assertEmpty($installed, 'Subgroup plugin was not found after removing the group type as a leaf.');
  }

  /**
   * Tests the removal of the last leaf.
   *
   * @covers ::removeLeaf
   */
  public function testRemoveLastLeaf() {
    $root = $this->createGroupType(['id' => 'cat']);
    $child = $this->createGroupType(['id' => 'dog']);
    $cousin = $this->createGroupType(['id' => 'mouse']);

    $this->subgroupHandler->initTree($root);
    $this->subgroupHandler->addLeaf($root, $child);
    $this->subgroupHandler->addLeaf($root, $cousin);

    $this->subgroupHandler->removeLeaf($child);
    $root = $this->groupTypeStorage->load('cat');
    $cousin = $this->groupTypeStorage->load('mouse');
    $this->assertTrue($this->subgroupHandler->isLeaf($root));

    $this->subgroupHandler->removeLeaf($cousin);
    $root = $this->groupTypeStorage->load('cat');
    $this->assertFalse($this->subgroupHandler->isLeaf($root));
  }

  /**
   * Tests the exception thrown when the group type still has descendants.
   *
   * @covers ::removeLeaf
   */
  public function testRemoveLeafWithDescendantsException() {
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('Cannot remove a leaf that still has descendants.');
    $this->subgroupHandler->removeLeaf($this->groupTypeChildLeft);
  }

  /**
   * Tests the removal of a root group type that still has groups.
   *
   * @covers ::removeLeaf
   * @depends testInitTree
   */
  public function testRemoveRootWithGroups() {
    $root = $this->createGroupType(['id' => 'cat']);
    $this->createGroup(['type' => 'cat']);

    $this->subgroupHandler->initTree($root);
    $this->subgroupHandler->removeLeaf($root);

    $root = $this->groupTypeStorage->load('cat');
    $this->assertFalse($this->subgroupHandler->isLeaf($root));
  }

  /**
   * Tests the exception thrown when the group type still has groups.
   *
   * @covers ::doRemoveLeaf
   */
  public function testRemoveNonRootWithGroupsException() {
    $this->createGroup(['type' => $this->groupTypeGrandchildLeft->id()]);
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('Cannot remove leaf status from a group type that still has groups.');
    $this->subgroupHandler->removeLeaf($this->groupTypeGrandchildLeft);
  }

  /**
   * Tests the handler-specific details of getParent().
   *
   * @covers ::getParent
   * @covers ::getDepthPropertyName
   * @covers ::getLeftPropertyName
   * @covers ::getRightPropertyName
   * @covers ::getTreePropertyName
   */
  public function testGetParent() {
    $this->assertEquals($this->groupTypeRoot->id(), $this->subgroupHandler->getParent($this->groupTypeChildLeft)->id());
    $this->assertEquals($this->groupTypeRoot->id(), $this->subgroupHandler->getParent($this->groupTypeChildMiddle)->id());
    $this->assertEquals($this->groupTypeRoot->id(), $this->subgroupHandler->getParent($this->groupTypeChildRight)->id());
    $this->assertEquals($this->groupTypeChildLeft->id(), $this->subgroupHandler->getParent($this->groupTypeGrandchildLeft)->id());
    $this->assertEquals($this->groupTypeChildRight->id(), $this->subgroupHandler->getParent($this->groupTypeGrandchildRight)->id());
  }

  /**
   * Tests the handler-specific details of getAncestors().
   *
   * @covers ::getAncestors
   */
  public function testGetAncestors() {
    $ancestors = $this->subgroupHandler->getAncestors($this->groupTypeChildLeft);
    $this->assertCount(1, $ancestors);
    $this->assertEquals([$this->groupTypeRoot->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupTypeChildMiddle);
    $this->assertCount(1, $ancestors);
    $this->assertEquals([$this->groupTypeRoot->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupTypeChildRight);
    $this->assertCount(1, $ancestors);
    $this->assertEquals([$this->groupTypeRoot->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupTypeGrandchildLeft);
    $this->assertCount(2, $ancestors);
    $this->assertEquals([$this->groupTypeRoot->id(), $this->groupTypeChildLeft->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupTypeGrandchildRight);
    $this->assertCount(2, $ancestors);
    $this->assertEquals([$this->groupTypeRoot->id(), $this->groupTypeChildRight->id()], array_keys($ancestors));
  }

  /**
   * Tests the handler-specific details of getChildren().
   *
   * @covers ::getChildren
   */
  public function testGetChildren() {
    $children = $this->subgroupHandler->getChildren($this->groupTypeRoot);
    $this->assertCount(3, $children);
    $this->assertEquals([$this->groupTypeChildLeft->id(), $this->groupTypeChildMiddle->id(), $this->groupTypeChildRight->id()], array_keys($children));

    $children = $this->subgroupHandler->getChildren($this->groupTypeChildLeft);
    $this->assertCount(1, $children);
    $this->assertEquals([$this->groupTypeGrandchildLeft->id()], array_keys($children));

    $children = $this->subgroupHandler->getChildren($this->groupTypeChildMiddle);
    $this->assertCount(0, $children);

    $children = $this->subgroupHandler->getChildren($this->groupTypeChildRight);
    $this->assertCount(1, $children);
    $this->assertEquals([$this->groupTypeGrandchildRight->id()], array_keys($children));

    $children = $this->subgroupHandler->getChildren($this->groupTypeGrandchildLeft);
    $this->assertCount(0, $children);

    $children = $this->subgroupHandler->getChildren($this->groupTypeGrandchildRight);
    $this->assertCount(0, $children);
  }

  /**
   * Tests the handler-specific details of getDescendants().
   *
   * @covers ::getDescendants
   */
  public function testGetDescendants() {
    $descendants = $this->subgroupHandler->getDescendants($this->groupTypeRoot);
    $this->assertCount(5, $descendants);
    $this->assertEquals([$this->groupTypeChildLeft->id(), $this->groupTypeGrandchildLeft->id(), $this->groupTypeChildMiddle->id(), $this->groupTypeChildRight->id(), $this->groupTypeGrandchildRight->id()], array_keys($descendants));

    $descendants = $this->subgroupHandler->getDescendants($this->groupTypeChildLeft);
    $this->assertCount(1, $descendants);
    $this->assertEquals([$this->groupTypeGrandchildLeft->id()], array_keys($descendants));

    $descendants = $this->subgroupHandler->getDescendants($this->groupTypeChildMiddle);
    $this->assertCount(0, $descendants);

    $descendants = $this->subgroupHandler->getDescendants($this->groupTypeChildRight);
    $this->assertCount(1, $descendants);
    $this->assertEquals([$this->groupTypeGrandchildRight->id()], array_keys($descendants));

    $descendants = $this->subgroupHandler->getDescendants($this->groupTypeGrandchildLeft);
    $this->assertCount(0, $descendants);

    $descendants = $this->subgroupHandler->getDescendants($this->groupTypeGrandchildRight);
    $this->assertCount(0, $descendants);
  }

  /**
   * Tests the handler-specific details of getDescendantCount().
   *
   * @covers ::getDescendantCount
   */
  public function testGetDescendantCount() {
    $this->assertSame(5, $this->subgroupHandler->getDescendantCount($this->groupTypeRoot));
    $this->assertSame(1, $this->subgroupHandler->getDescendantCount($this->groupTypeChildLeft));
    $this->assertSame(0, $this->subgroupHandler->getDescendantCount($this->groupTypeChildMiddle));
    $this->assertSame(1, $this->subgroupHandler->getDescendantCount($this->groupTypeChildRight));
    $this->assertSame(0, $this->subgroupHandler->getDescendantCount($this->groupTypeGrandchildLeft));
    $this->assertSame(0, $this->subgroupHandler->getDescendantCount($this->groupTypeGrandchildRight));
  }

  /**
   * Tests the handler-specific details of hasDescendants().
   *
   * @covers ::hasDescendants
   */
  public function testHasDescendants() {
    $this->assertTrue($this->subgroupHandler->hasDescendants($this->groupTypeRoot));
    $this->assertTrue($this->subgroupHandler->hasDescendants($this->groupTypeChildLeft));
    $this->assertFalse($this->subgroupHandler->hasDescendants($this->groupTypeChildMiddle));
    $this->assertTrue($this->subgroupHandler->hasDescendants($this->groupTypeChildRight));
    $this->assertFalse($this->subgroupHandler->hasDescendants($this->groupTypeGrandchildLeft));
    $this->assertFalse($this->subgroupHandler->hasDescendants($this->groupTypeGrandchildRight));
  }

}
