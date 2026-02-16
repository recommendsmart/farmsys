<?php

namespace Drupal\Tests\subgroup\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\subgroup\Entity\RoleInheritanceStorageInterface;
use Drupal\subgroup\Entity\SubgroupHandlerInterface;
use Drupal\subgroup\Event\GroupTypeLeafEvent;
use Drupal\subgroup\Event\LeafEvents;
use Drupal\subgroup\EventSubscriber\GroupTypeLeafSubscriber;
use Drupal\subgroup\LeafInterface;
use Drupal\subgroup\SubgroupFieldManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the functionality of the group type leaf event subscriber.
 *
 * @coversDefaultClass \Drupal\subgroup\EventSubscriber\GroupTypeLeafSubscriber
 * @group subgroup
 */
class GroupTypeLeafSubscriberTest extends UnitTestCase {

  /**
   * The event subscriber to test.
   *
   * @var \Drupal\subgroup\EventSubscriber\GroupTypeLeafSubscriber
   */
  protected $eventSubscriber;

  /**
   * The entity type manager to run tests on.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The subgroup handler for group types to run tests on.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $subgroupHandler;

  /**
   * The group content enabler plugin manager to run tests on.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $pluginManager;

  /**
   * The subgroup field manager to run tests on.
   *
   * @var \Drupal\subgroup\SubgroupFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->subgroupHandler = $this->prophesize(SubgroupHandlerInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getHandler('group_type', 'subgroup')->willReturn($this->subgroupHandler->reveal());
    $this->pluginManager = $this->prophesize(GroupContentEnablerManagerInterface::class);
    $this->fieldManager = $this->prophesize(SubgroupFieldManagerInterface::class);

    $this->eventSubscriber = new GroupTypeLeafSubscriber(
      $this->entityTypeManager->reveal(),
      $this->pluginManager->reveal(),
      $this->fieldManager->reveal()
    );
  }

  /**
   * Tests the getSubscribedEvents() method.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $subscribed = GroupTypeLeafSubscriber::getSubscribedEvents();
    $this->assertCount(3, $subscribed);
    $this->assertArrayHasKey(LeafEvents::GROUP_TYPE_LEAF_ADD, $subscribed);
    $this->assertArrayHasKey(LeafEvents::GROUP_TYPE_LEAF_REMOVE, $subscribed);
  }

  /**
   * Tests the onAddLeaf() method for a root leaf.
   *
   * @covers ::onAddLeaf
   */
  public function testOnAddLeafRoot() {
    $original = $this->prophesize(GroupTypeInterface::class)->reveal();
    $group_type = $this->prophesize(GroupTypeInterface::class);
    $group_type->id()->willReturn('foobar');
    $group_type = $group_type->reveal();
    $group_type->original = $original;

    $this->subgroupHandler->isRoot($group_type)->willReturn(TRUE);

    $this->fieldManager->installFields('foobar')->shouldBeCalledOnce();
    $this->pluginManager->clearCachedDefinitions()->shouldBeCalledOnce();
    $this->eventSubscriber->onAddLeaf(new GroupTypeLeafEvent($group_type));
  }

  /**
   * Tests the onAddLeaf() method for a non-root leaf.
   *
   * @covers ::onAddLeaf
   */
  public function testOnAddLeafNonRoot() {
    $original = $this->prophesize(GroupTypeInterface::class)->reveal();
    $group_type = $this->prophesize(GroupTypeInterface::class);
    $group_type->id()->willReturn('foobar');
    $group_type = $group_type->reveal();
    $group_type->original = $original;

    $this->subgroupHandler->isRoot($group_type)->willReturn(FALSE);

    $parent = $this->prophesize(GroupTypeInterface::class)->reveal();
    $this->subgroupHandler->getParent($group_type)->shouldBeCalledOnce();
    $this->subgroupHandler->getParent($group_type)->willReturn($parent);

    $group_content_type = $this->prophesize(GroupContentTypeInterface::class)->reveal();
    $storage = $this->prophesize(GroupContentTypeStorageInterface::class);
    $storage->createFromPlugin($parent, 'subgroup:foobar')->shouldBeCalledOnce();
    $storage->createFromPlugin($parent, 'subgroup:foobar')->willReturn($group_content_type);
    $storage->save($group_content_type)->shouldBeCalledOnce();
    $this->entityTypeManager->getStorage('group_content_type')->willReturn($storage->reveal());

    $this->fieldManager->installFields('foobar')->shouldBeCalledOnce();
    $this->pluginManager->clearCachedDefinitions()->shouldBeCalledOnce();
    $this->eventSubscriber->onAddLeaf(new GroupTypeLeafEvent($group_type));
  }

  /**
   * Tests the onRemoveLeaf() method.
   *
   * @covers ::onRemoveLeaf
   */
  public function testOnRemoveLeaf() {
    $original = $this->prophesize(GroupTypeInterface::class)->reveal();

    $leaf = $this->prophesize(LeafInterface::class);
    $leaf->getTree()->willReturn('some_tree');
    $this->subgroupHandler->wrapLeaf($original)->willReturn($leaf->reveal());

    $group_type = $this->prophesize(GroupTypeInterface::class);
    $group_type->id()->willReturn('foobar');
    $group_type = $group_type->reveal();
    $group_type->original = $original;

    $role_inheritance_storage = $this->prophesize(RoleInheritanceStorageInterface::class);
    $role_inheritance_storage->deleteForGroupType($group_type, 'some_tree')->shouldBeCalledOnce();
    $this->entityTypeManager->getStorage('subgroup_role_inheritance')->willReturn($role_inheritance_storage->reveal());

    $group_content_type = $this->prophesize(GroupContentTypeInterface::class)->reveal();
    $storage = $this->prophesize(GroupContentTypeStorageInterface::class);
    $storage->loadByContentPluginId('subgroup:foobar')->shouldBeCalledOnce();
    $storage->loadByContentPluginId('subgroup:foobar')->willReturn([$group_content_type]);
    $storage->delete([$group_content_type])->shouldBeCalledOnce();
    $this->entityTypeManager->getStorage('group_content_type')->willReturn($storage->reveal());

    $this->fieldManager->deleteFields('foobar')->shouldBeCalledOnce();
    $this->pluginManager->clearCachedDefinitions()->shouldBeCalledOnce();
    $this->eventSubscriber->onRemoveLeaf(new GroupTypeLeafEvent($group_type));
  }

}
