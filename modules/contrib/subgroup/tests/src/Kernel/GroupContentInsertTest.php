<?php

namespace Drupal\Tests\subgroup\Kernel;

/**
 * Tests that adding subgroup group content triggers leaf creation.
 *
 * @group subgroup
 */
class GroupContentInsertTest extends SubgroupKernelTestBase {

  /**
   * The group subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $groupSubgroupHandler;

  /**
   * The group type subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $groupTypeSubgroupHandler;

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
    $this->groupSubgroupHandler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $this->groupTypeSubgroupHandler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $this->storage = $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * Tests whether inserting group content creates a leaf.
   */
  public function testInsertCreatesLeaf() {
    $group_type_parent = $this->createGroupType();
    $group_type_child = $this->createGroupType();
    $this->groupTypeSubgroupHandler->initTree($group_type_parent);
    $this->groupTypeSubgroupHandler->addLeaf($group_type_parent, $group_type_child);

    $group_parent = $this->createGroup(['type' => $group_type_parent->id()]);
    $group_child = $this->createGroup(['type' => $group_type_child->id()]);
    $this->groupSubgroupHandler->initTree($group_parent);

    $this->assertTrue($this->groupSubgroupHandler->isLeaf($group_parent));
    $this->assertFalse($this->groupSubgroupHandler->isLeaf($group_child));

    $this->storage->save(
      $this->storage->createForEntityInGroup(
        $group_child,
        $group_parent,
        'subgroup:' . $group_type_child->id()
      )
    );

    $group_parent = $this->groupStorage->load($group_parent->id());
    $group_child = $this->groupStorage->load($group_child->id());

    $this->assertTrue($this->groupSubgroupHandler->isLeaf($group_parent));
    $this->assertTrue($this->groupSubgroupHandler->isRoot($group_parent));
    $this->assertTrue($this->groupSubgroupHandler->isLeaf($group_child));
    $this->assertSame($group_parent, $this->groupSubgroupHandler->getParent($group_child));
  }

  /**
   * Tests whether inserting group content can also initiate a root.
   */
  public function testInsertInitiatesRoot() {
    $group_type_parent = $this->createGroupType();
    $group_type_child = $this->createGroupType();
    $this->groupTypeSubgroupHandler->initTree($group_type_parent);
    $this->groupTypeSubgroupHandler->addLeaf($group_type_parent, $group_type_child);

    $group_parent = $this->createGroup(['type' => $group_type_parent->id()]);
    $group_child = $this->createGroup(['type' => $group_type_child->id()]);

    $this->assertFalse($this->groupSubgroupHandler->isLeaf($group_parent));
    $this->assertFalse($this->groupSubgroupHandler->isLeaf($group_child));

    $this->storage->save(
      $this->storage->createForEntityInGroup(
        $group_child,
        $group_parent,
        'subgroup:' . $group_type_child->id()
      )
    );

    $group_parent = $this->groupStorage->load($group_parent->id());
    $group_child = $this->groupStorage->load($group_child->id());

    $this->assertTrue($this->groupSubgroupHandler->isLeaf($group_parent));
    $this->assertTrue($this->groupSubgroupHandler->isRoot($group_parent));
    $this->assertTrue($this->groupSubgroupHandler->isLeaf($group_child));
    $this->assertSame($group_parent, $this->groupSubgroupHandler->getParent($group_child));
  }

}
