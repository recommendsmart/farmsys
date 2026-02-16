<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\subgroup\InvalidLeafException;
use Drupal\subgroup\InvalidParentException;
use Drupal\subgroup\InvalidRootException;

/**
 * Tests the subgroup handler for Group entities.
 *
 * @coversDefaultClass \Drupal\subgroup\Entity\GroupSubgroupHandler
 * @group subgroup
 */
class GroupSubgroupHandlerTest extends SubgroupKernelTestBase {

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
   * The child group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeChild;

  /**
   * The grandchild group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeGrandchild;

  /**
   * The root group to run tests on.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupRoot;

  /**
   * The left child group to run tests on.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupChildLeft;

  /**
   * The middle child group to run tests on.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupChildMiddle;

  /**
   * The right child group to run tests on.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupChildRight;

  /**
   * The left grandchild group to run tests on.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupGrandchildLeft;

  /**
   * The right grandchild group to run tests on.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupGrandchildRight;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->subgroupHandler = $this->entityTypeManager->getHandler('group', 'subgroup');

    $this->groupTypeRoot = $this->createGroupType(['id' => 'foo']);
    $this->groupTypeChild = $this->createGroupType(['id' => 'bar']);
    $this->groupTypeGrandchild = $this->createGroupType(['id' => 'baz']);

    $this->writeGroupTypeLeafData($this->groupTypeRoot, 0, 1, 6, 'foo');
    $this->writeGroupTypeLeafData($this->groupTypeChild, 1, 2, 5, 'foo');
    $this->writeGroupTypeLeafData($this->groupTypeGrandchild, 2, 3, 4, 'foo');

    $this->groupRoot = $this->createGroup(['type' => $this->groupTypeRoot->id()]);
    $this->groupChildLeft = $this->createGroup(['type' => $this->groupTypeChild->id()]);
    $this->groupChildMiddle = $this->createGroup(['type' => $this->groupTypeChild->id()]);
    $this->groupChildRight = $this->createGroup(['type' => $this->groupTypeChild->id()]);
    $this->groupGrandchildLeft = $this->createGroup(['type' => $this->groupTypeGrandchild->id()]);
    $this->groupGrandchildRight = $this->createGroup(['type' => $this->groupTypeGrandchild->id()]);

    $this->writeGroupLeafData($this->groupRoot, 0, 1, 12, $this->groupRoot->id());
    $this->writeGroupLeafData($this->groupChildLeft, 1, 2, 5, $this->groupRoot->id());
    $this->writeGroupLeafData($this->groupGrandchildLeft, 2, 3, 4, $this->groupRoot->id());
    $this->writeGroupLeafData($this->groupChildMiddle, 1, 6, 7, $this->groupRoot->id());
    $this->writeGroupLeafData($this->groupChildRight, 1, 8, 11, $this->groupRoot->id());
    $this->writeGroupLeafData($this->groupGrandchildRight, 2, 9, 10, $this->groupRoot->id());
  }

  /**
   * Tests the handler-specific details of initTree().
   *
   * @covers ::initTree
   * @covers ::writeLeafData
   */
  public function testInitTree() {
    $group = $this->createGroup(['type' => $this->groupTypeRoot->id()]);

    // For extra hardening we check the fields directly.
    $root_field_data = [
      SUBGROUP_DEPTH_FIELD => 0,
      SUBGROUP_LEFT_FIELD => 1,
      SUBGROUP_RIGHT_FIELD => 2,
      SUBGROUP_TREE_FIELD => $group->id(),
    ];

    $this->assertFalse($this->subgroupHandler->isLeaf($group));
    foreach ($root_field_data as $field_name => $field_data) {
      $this->assertTrue($group->get($field_name)->isEmpty());
    }

    $this->subgroupHandler->initTree($group);

    $this->assertTrue($this->subgroupHandler->isLeaf($group));
    $this->assertTrue($this->subgroupHandler->isRoot($group));
    foreach ($root_field_data as $field_name => $field_data) {
      $this->assertEquals($field_data, $group->get($field_name)->value);
    }
  }

  /**
   * Tests the exception thrown when the group type is not a leaf.
   *
   * @covers ::doInitTree
   */
  public function testGroupTypeNotALeafException() {
    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);
    $this->expectException(InvalidRootException::class);
    $this->expectExceptionMessage('Trying to initialize a tree for a group whose group type is not part of a tree structure.');
    $this->subgroupHandler->initTree($group);
  }

  /**
   * Tests the exception thrown when the group type is not a root.
   *
   * @covers ::doInitTree
   */
  public function testGroupTypeNotARootException() {
    $group = $this->createGroup(['type' => $this->groupTypeChild->id()]);
    $this->expectException(InvalidRootException::class);
    $this->expectExceptionMessage('Trying to initialize a tree for a group whose group type is not configured as a tree root.');
    $this->subgroupHandler->initTree($group);
  }

  /**
   * Tests the handler-specific details of addLeaf().
   *
   * @covers ::addLeaf
   * @covers ::writeLeafData
   */
  public function testAddLeaf() {
    $grandparent = $this->subgroupHandler->wrapLeaf($this->groupRoot);
    $left_uncle = $this->subgroupHandler->wrapLeaf($this->groupChildLeft);
    $left_cousin = $this->subgroupHandler->wrapLeaf($this->groupGrandchildLeft);
    $right_uncle = $this->subgroupHandler->wrapLeaf($this->groupChildRight);
    $right_cousin = $this->subgroupHandler->wrapLeaf($this->groupGrandchildRight);

    $parent = $this->subgroupHandler->wrapLeaf($this->groupChildMiddle);
    $original_parent_depth = $parent->getDepth();
    $original_parent_left = $parent->getLeft();
    $original_parent_right = $parent->getRight();
    $original_parent_tree = $parent->getTree();

    $group = $this->createGroup(['type' => $this->groupTypeGrandchild->id()]);
    $this->subgroupHandler->addLeaf($this->groupChildMiddle, $group);
    $child = $this->subgroupHandler->wrapLeaf($group);

    $grandparent_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupRoot->id()));
    $left_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildLeft->id()));
    $left_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupGrandchildLeft->id()));
    $right_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildRight->id()));
    $right_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupGrandchildRight->id()));

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
   * Tests the exception thrown when the group types do not match.
   *
   * @covers ::doAddLeaf
   */
  public function testInvalidParentGroupTypeException() {
    $group = $this->createGroup(['type' => $this->groupTypeGrandchild->id()]);
    $this->expectException(InvalidParentException::class);
    $this->expectExceptionMessage('Provided group cannot be added as a leaf to the parent (incompatible group types).');
    $this->subgroupHandler->addLeaf($this->groupRoot, $group);
  }

  /**
   * Tests the handler-specific details of removeLeaf().
   *
   * @covers ::removeLeaf
   * @covers ::clearLeafData
   */
  public function testRemoveLeaf() {
    $group = $this->createGroup(['type' => $this->groupTypeGrandchild->id()]);
    $this->subgroupHandler->addLeaf($this->groupChildMiddle, $group);

    $grandparent = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupRoot->id()));
    $left_uncle = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildLeft->id()));
    $left_cousin = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupGrandchildLeft->id()));
    $parent = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildMiddle->id()));
    $right_uncle = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildRight->id()));
    $right_cousin = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupGrandchildRight->id()));

    // Reset the cache so that the entity pointers are different from the ones
    // being manipulated in removeLeaf(). This will greatly facilitate checking
    // the before vs after values.
    $this->groupStorage->resetCache();

    $this->subgroupHandler->removeLeaf($group, FALSE);

    $grandparent_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupRoot->id()));
    $left_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildLeft->id()));
    $left_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupGrandchildLeft->id()));
    $parent_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildMiddle->id()));
    $right_uncle_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupChildRight->id()));
    $right_cousin_reloaded = $this->subgroupHandler->wrapLeaf($this->groupStorage->load($this->groupGrandchildRight->id()));

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

    $this->assertTrue($group->get(SUBGROUP_DEPTH_FIELD)->isEmpty(), 'Child no longer has a depth value.');
    $this->assertTrue($group->get(SUBGROUP_LEFT_FIELD)->isEmpty(), 'Child no longer has a left bound value.');
    $this->assertTrue($group->get(SUBGROUP_RIGHT_FIELD)->isEmpty(), 'Child no longer has a right bound value.');
    $this->assertTrue($group->get(SUBGROUP_TREE_FIELD)->isEmpty(), 'Child no longer has a tree ID value.');
  }

  /**
   * Tests the removal of the last leaf.
   *
   * @covers ::removeLeaf
   */
  public function testRemoveLastLeaf() {
    $root = $this->createGroup(['type' => $this->groupTypeRoot->id()]);
    $child = $this->createGroup(['type' => $this->groupTypeChild->id()]);
    $cousin = $this->createGroup(['type' => $this->groupTypeChild->id()]);

    $this->subgroupHandler->initTree($root);
    $this->subgroupHandler->addLeaf($root, $child);
    $this->subgroupHandler->addLeaf($root, $cousin);

    $this->subgroupHandler->removeLeaf($child);
    $root = $this->groupStorage->load($root->id());
    $cousin = $this->groupStorage->load($cousin->id());
    $this->assertTrue($this->subgroupHandler->isLeaf($root));

    $this->subgroupHandler->removeLeaf($cousin);
    $root = $this->groupStorage->load($root->id());
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
    $this->subgroupHandler->removeLeaf($this->groupChildLeft);
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
    $this->assertEquals($this->groupRoot->id(), $this->subgroupHandler->getParent($this->groupChildLeft)->id());
    $this->assertEquals($this->groupRoot->id(), $this->subgroupHandler->getParent($this->groupChildMiddle)->id());
    $this->assertEquals($this->groupRoot->id(), $this->subgroupHandler->getParent($this->groupChildRight)->id());
    $this->assertEquals($this->groupChildLeft->id(), $this->subgroupHandler->getParent($this->groupGrandchildLeft)->id());
    $this->assertEquals($this->groupChildRight->id(), $this->subgroupHandler->getParent($this->groupGrandchildRight)->id());
  }

  /**
   * Tests the handler-specific details of getAncestors().
   *
   * @covers ::getAncestors
   */
  public function testGetAncestors() {
    $ancestors = $this->subgroupHandler->getAncestors($this->groupChildLeft);
    $this->assertCount(1, $ancestors);
    $this->assertEquals([$this->groupRoot->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupChildMiddle);
    $this->assertCount(1, $ancestors);
    $this->assertEquals([$this->groupRoot->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupChildRight);
    $this->assertCount(1, $ancestors);
    $this->assertEquals([$this->groupRoot->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupGrandchildLeft);
    $this->assertCount(2, $ancestors);
    $this->assertEquals([$this->groupRoot->id(), $this->groupChildLeft->id()], array_keys($ancestors));

    $ancestors = $this->subgroupHandler->getAncestors($this->groupGrandchildRight);
    $this->assertCount(2, $ancestors);
    $this->assertEquals([$this->groupRoot->id(), $this->groupChildRight->id()], array_keys($ancestors));
  }

  /**
   * Tests the handler-specific details of getChildren().
   *
   * @covers ::getChildren
   */
  public function testGetChildren() {
    $children = $this->subgroupHandler->getChildren($this->groupRoot);
    $this->assertCount(3, $children);
    $this->assertEquals([$this->groupChildLeft->id(), $this->groupChildMiddle->id(), $this->groupChildRight->id()], array_keys($children));

    $children = $this->subgroupHandler->getChildren($this->groupChildLeft);
    $this->assertCount(1, $children);
    $this->assertEquals([$this->groupGrandchildLeft->id()], array_keys($children));

    $children = $this->subgroupHandler->getChildren($this->groupChildMiddle);
    $this->assertCount(0, $children);

    $children = $this->subgroupHandler->getChildren($this->groupChildRight);
    $this->assertCount(1, $children);
    $this->assertEquals([$this->groupGrandchildRight->id()], array_keys($children));

    $children = $this->subgroupHandler->getChildren($this->groupGrandchildLeft);
    $this->assertCount(0, $children);

    $children = $this->subgroupHandler->getChildren($this->groupGrandchildRight);
    $this->assertCount(0, $children);
  }

  /**
   * Tests the handler-specific details of getDescendants().
   *
   * @covers ::getDescendants
   */
  public function testGetDescendants() {
    $descendants = $this->subgroupHandler->getDescendants($this->groupRoot);
    $this->assertCount(5, $descendants);
    $this->assertEquals([$this->groupChildLeft->id(), $this->groupGrandchildLeft->id(), $this->groupChildMiddle->id(), $this->groupChildRight->id(), $this->groupGrandchildRight->id()], array_keys($descendants));

    $descendants = $this->subgroupHandler->getDescendants($this->groupChildLeft);
    $this->assertCount(1, $descendants);
    $this->assertEquals([$this->groupGrandchildLeft->id()], array_keys($descendants));

    $descendants = $this->subgroupHandler->getDescendants($this->groupChildMiddle);
    $this->assertCount(0, $descendants);

    $descendants = $this->subgroupHandler->getDescendants($this->groupChildRight);
    $this->assertCount(1, $descendants);
    $this->assertEquals([$this->groupGrandchildRight->id()], array_keys($descendants));

    $descendants = $this->subgroupHandler->getDescendants($this->groupGrandchildLeft);
    $this->assertCount(0, $descendants);

    $descendants = $this->subgroupHandler->getDescendants($this->groupGrandchildRight);
    $this->assertCount(0, $descendants);
  }

  /**
   * Tests the handler-specific details of getDescendantCount().
   *
   * @covers ::getDescendantCount
   */
  public function testGetDescendantCount() {
    $this->assertSame(5, $this->subgroupHandler->getDescendantCount($this->groupRoot));
    $this->assertSame(1, $this->subgroupHandler->getDescendantCount($this->groupChildLeft));
    $this->assertSame(0, $this->subgroupHandler->getDescendantCount($this->groupChildMiddle));
    $this->assertSame(1, $this->subgroupHandler->getDescendantCount($this->groupChildRight));
    $this->assertSame(0, $this->subgroupHandler->getDescendantCount($this->groupGrandchildLeft));
    $this->assertSame(0, $this->subgroupHandler->getDescendantCount($this->groupGrandchildRight));
  }

  /**
   * Tests the handler-specific details of hasDescendants().
   *
   * @covers ::hasDescendants
   */
  public function testHasDescendants() {
    $this->assertTrue($this->subgroupHandler->hasDescendants($this->groupRoot));
    $this->assertTrue($this->subgroupHandler->hasDescendants($this->groupChildLeft));
    $this->assertFalse($this->subgroupHandler->hasDescendants($this->groupChildMiddle));
    $this->assertTrue($this->subgroupHandler->hasDescendants($this->groupChildRight));
    $this->assertFalse($this->subgroupHandler->hasDescendants($this->groupGrandchildLeft));
    $this->assertFalse($this->subgroupHandler->hasDescendants($this->groupGrandchildRight));
  }

}
