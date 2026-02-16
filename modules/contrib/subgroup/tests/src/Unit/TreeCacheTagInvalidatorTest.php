<?php

namespace Drupal\Tests\subgroup\Unit;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\subgroup\Entity\SubgroupHandlerInterface;
use Drupal\subgroup\Event\GroupLeafEvent;
use Drupal\subgroup\Event\GroupTypeLeafEvent;
use Drupal\subgroup\Event\LeafEvents;
use Drupal\subgroup\EventSubscriber\TreeCacheTagInvalidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the functionality of the tree cache tag invalidator.
 *
 * @coversDefaultClass \Drupal\subgroup\EventSubscriber\TreeCacheTagInvalidator
 * @group subgroup
 */
class TreeCacheTagInvalidatorTest extends UnitTestCase {

  /**
   * The event subscriber to test.
   *
   * @var \Drupal\subgroup\EventSubscriber\TreeCacheTagInvalidator
   */
  protected $eventSubscriber;

  /**
   * The entity type manager to use in testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The group subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $groupSubgroupHandler;

  /**
   * The group type subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $groupTypeSubgroupHandler;

  /**
   * The cache tags invalidator to use in testing.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->groupSubgroupHandler = $this->prophesize(SubgroupHandlerInterface::class);
    $this->groupTypeSubgroupHandler = $this->prophesize(SubgroupHandlerInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getHandler('group', 'subgroup')->willReturn($this->groupSubgroupHandler->reveal());
    $this->entityTypeManager->getHandler('group_type', 'subgroup')->willReturn($this->groupTypeSubgroupHandler->reveal());
    $this->cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $this->eventSubscriber = new TreeCacheTagInvalidator(
      $this->entityTypeManager->reveal(),
      $this->cacheTagsInvalidator->reveal()
    );
  }

  /**
   * Tests the getSubscribedEvents() method.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $subscribed = TreeCacheTagInvalidator::getSubscribedEvents();
    $this->assertCount(4, $subscribed);
    $this->assertArrayHasKey(LeafEvents::GROUP_LEAF_ADD, $subscribed);
    $this->assertArrayHasKey(LeafEvents::GROUP_LEAF_REMOVE, $subscribed);
    $this->assertArrayHasKey(LeafEvents::GROUP_TYPE_LEAF_ADD, $subscribed);
    $this->assertArrayHasKey(LeafEvents::GROUP_TYPE_LEAF_REMOVE, $subscribed);
  }

  /**
   * Tests the onAddGroupLeaf() method.
   *
   * @covers ::onAddGroupLeaf
   */
  public function testOnAddGroupLeaf() {
    $group = $this->prophesize(GroupInterface::class)->reveal();
    $group->original = $this->prophesize(GroupInterface::class)->reveal();
    $this->groupSubgroupHandler->getTreeCacheTags($group)->willReturn(['foo', 'bar']);
    $this->cacheTagsInvalidator->invalidateTags(['foo', 'bar'])->shouldBeCalled();
    $this->eventSubscriber->onAddGroupLeaf(new GroupLeafEvent($group));
  }

  /**
   * Tests the onRemoveGroupLeaf() method.
   *
   * @covers ::onRemoveGroupLeaf
   */
  public function testOnRemoveGroupLeaf() {
    $group = $this->prophesize(GroupInterface::class)->reveal();
    $group->original = $this->prophesize(GroupInterface::class)->reveal();
    $this->groupSubgroupHandler->getTreeCacheTags($group->original)->willReturn(['foo', 'bar']);
    $this->cacheTagsInvalidator->invalidateTags(['foo', 'bar'])->shouldBeCalled();
    $this->eventSubscriber->onRemoveGroupLeaf(new GroupLeafEvent($group));
  }

  /**
   * Tests the onAddGroupTypeLeaf() method.
   *
   * @covers ::onAddGroupTypeLeaf
   */
  public function testOnAddGroupTypeLeaf() {
    $group_type = $this->prophesize(GroupTypeInterface::class)->reveal();
    $group_type->original = $this->prophesize(GroupTypeInterface::class)->reveal();
    $this->groupTypeSubgroupHandler->getTreeCacheTags($group_type)->willReturn(['foo', 'bar']);
    $this->cacheTagsInvalidator->invalidateTags(['foo', 'bar'])->shouldBeCalled();
    $this->eventSubscriber->onAddGroupTypeLeaf(new GroupTypeLeafEvent($group_type));
  }

  /**
   * Tests the onRemoveGroupTypeLeaf() method.
   *
   * @covers ::onRemoveGroupTypeLeaf
   */
  public function testOnRemoveGroupTypeLeaf() {
    $group_type = $this->prophesize(GroupTypeInterface::class)->reveal();
    $group_type->original = $this->prophesize(GroupTypeInterface::class)->reveal();
    $this->groupTypeSubgroupHandler->getTreeCacheTags($group_type->original)->willReturn(['foo', 'bar']);
    $this->cacheTagsInvalidator->invalidateTags(['foo', 'bar'])->shouldBeCalled();
    $this->eventSubscriber->onRemoveGroupTypeLeaf(new GroupTypeLeafEvent($group_type));
  }

}
