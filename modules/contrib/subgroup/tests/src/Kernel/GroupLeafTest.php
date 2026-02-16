<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\subgroup\GroupLeaf;
use Drupal\subgroup\MalformedLeafException;

/**
 * Tests the leaf wrapper for Group entities.
 *
 * @coversDefaultClass \Drupal\subgroup\GroupLeaf
 * @group subgroup
 */
class GroupLeafTest extends SubgroupKernelTestBase {

  /**
   * The group leaf to run tests on.
   *
   * @var \Drupal\subgroup\GroupLeaf
   */
  protected $groupLeaf;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $group_type = $this->createGroupType();
    $this->toggleTreeStatus($group_type, TRUE);

    $group = $this->createGroup([
      'type' => $group_type->id(),
      SUBGROUP_DEPTH_FIELD => 0,
      SUBGROUP_LEFT_FIELD => 1,
      SUBGROUP_RIGHT_FIELD => 2,
      SUBGROUP_TREE_FIELD => 9000,
    ]);
    $this->groupLeaf = new GroupLeaf($group);
  }

  /**
   * Tests the constructor missing field exception.
   */
  public function testConstructorFieldException() {
    $group_type = $this->createGroupType();
    $group = $this->createGroup(['type' => $group_type->id()]);
    $this->expectException(MalformedLeafException::class);
    $this->expectExceptionMessage(sprintf('Trying to create a group leaf but the "%s" field is missing', SUBGROUP_DEPTH_FIELD));
    new GroupLeaf($group);
  }

  /**
   * Tests the constructor missing value exception.
   */
  public function testConstructorValueException() {
    $group_type = $this->createGroupType();
    $this->toggleTreeStatus($group_type, TRUE);
    $group = $this->createGroup(['type' => $group_type->id()]);
    $this->expectException(MalformedLeafException::class);
    $this->expectExceptionMessage(sprintf('Trying to create a group leaf but the "%s" value is missing', SUBGROUP_DEPTH_FIELD));
    new GroupLeaf($group);
  }

  /**
   * Tests the depth getter.
   *
   * @covers ::getDepth
   */
  public function testGetDepth() {
    $this->assertSame(0, $this->groupLeaf->getDepth());
  }

  /**
   * Tests the left bound getter.
   *
   * @covers ::getLeft
   */
  public function testGetLeft() {
    $this->assertSame(1, $this->groupLeaf->getLeft());
  }

  /**
   * Tests the right bound getter.
   *
   * @covers ::getRight
   */
  public function testGetRight() {
    $this->assertSame(2, $this->groupLeaf->getRight());
  }

  /**
   * Tests the tree ID getter.
   *
   * @covers ::getTree
   */
  public function testGetTree() {
    $this->assertSame(9000, $this->groupLeaf->getTree());
  }

}
