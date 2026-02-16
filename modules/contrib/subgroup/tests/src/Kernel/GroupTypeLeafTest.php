<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\subgroup\GroupTypeLeaf;
use Drupal\subgroup\MalformedLeafException;

/**
 * Tests the leaf wrapper for GroupType entities.
 *
 * @coversDefaultClass \Drupal\subgroup\GroupTypeLeaf
 * @group subgroup
 */
class GroupTypeLeafTest extends SubgroupKernelTestBase {

  /**
   * The group type leaf to run tests on.
   *
   * @var \Drupal\subgroup\GroupTypeLeaf
   */
  protected $groupTypeLeaf;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $group_type = $this->createGroupType([
      'third_party_settings' => [
        'subgroup' => [
          SUBGROUP_DEPTH_SETTING => 0,
          SUBGROUP_LEFT_SETTING => 1,
          SUBGROUP_RIGHT_SETTING => 2,
          SUBGROUP_TREE_SETTING => 'foo',
        ],
      ],
    ]);
    $this->groupTypeLeaf = new GroupTypeLeaf($group_type);
  }

  /**
   * Tests the constructor exception.
   */
  public function testConstructorException() {
    $group_type = $this->createGroupType();
    $this->expectException(MalformedLeafException::class);
    $this->expectExceptionMessage('Trying to create a group type leaf but "depth" is missing');
    new GroupTypeLeaf($group_type);
  }

  /**
   * Tests the depth getter.
   *
   * @covers ::getDepth
   */
  public function testGetDepth() {
    $this->assertEquals(0, $this->groupTypeLeaf->getDepth());
  }

  /**
   * Tests the left bound getter.
   *
   * @covers ::getLeft
   */
  public function testGetLeft() {
    $this->assertEquals(1, $this->groupTypeLeaf->getLeft());
  }

  /**
   * Tests the right bound getter.
   *
   * @covers ::getRight
   */
  public function testGetRight() {
    $this->assertEquals(2, $this->groupTypeLeaf->getRight());
  }

  /**
   * Tests the tree ID getter.
   *
   * @covers ::getTree
   */
  public function testGetTree() {
    $this->assertEquals('foo', $this->groupTypeLeaf->getTree());
  }

}
