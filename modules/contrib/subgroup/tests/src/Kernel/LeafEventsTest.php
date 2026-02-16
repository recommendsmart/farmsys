<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\subgroup\Event\LeafEvents;

/**
 * Tests the dispatching of leaf events.
 *
 * @group subgroup
 */
class LeafEventsTest extends SubgroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['subgroup_test_events'];

  /**
   * Tests whether the group update hook dispatches events.
   */
  public function testGroupUpdateEvents() {
    $group_type = $this->createGroupType();

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $subgroup_handler->initTree($group_type);

    $group = $this->createGroup(['type' => $group_type->id()]);

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group', 'subgroup');

    $GLOBALS['group_leaf_events'] = [];
    $subgroup_handler->initTree($group);
    $this->assertCount(1, $GLOBALS['group_leaf_events'], 'Only one event was dispatched when leaf status was toggled on.');
    $this->assertEquals(
      LeafEvents::GROUP_LEAF_ADD . ' dispatched for ' . $group->id(),
      $GLOBALS['group_leaf_events'][0],
      'Leaf add event was dispatched when leaf status was toggled on.'
    );

    $GLOBALS['group_leaf_events'] = [];
    $group->save();
    $this->assertCount(0, $GLOBALS['group_leaf_events'], 'No events were dispatched when leaf status remained unchanged.');

    $GLOBALS['group_leaf_events'] = [];
    $subgroup_handler->removeLeaf($group);
    $this->assertCount(1, $GLOBALS['group_leaf_events'], 'Only one event was dispatched when leaf status was toggled off.');
    $this->assertEquals(
      LeafEvents::GROUP_LEAF_REMOVE . ' dispatched for ' . $group->id(),
      $GLOBALS['group_leaf_events'][0],
      'Leaf remove event was dispatched when leaf status was toggled on.'
    );
  }

  /**
   * Tests whether the group_type update hook dispatches events.
   */
  public function testGroupTypeUpdateEvents() {
    $group_type = $this->createGroupType();

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler */
    $subgroup_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');

    $GLOBALS['group_type_leaf_events'] = [];
    $subgroup_handler->initTree($group_type);
    $this->assertCount(1, $GLOBALS['group_type_leaf_events'], 'Only one event was dispatched when leaf status was toggled on.');
    $this->assertEquals(
      LeafEvents::GROUP_TYPE_LEAF_ADD . ' dispatched for ' . $group_type->id(),
      $GLOBALS['group_type_leaf_events'][0],
      'Leaf add event was dispatched when leaf status was toggled on.'
    );

    $GLOBALS['group_type_leaf_events'] = [];
    $group_type->save();
    $this->assertCount(0, $GLOBALS['group_type_leaf_events'], 'No events were dispatched when leaf status remained unchanged.');

    $GLOBALS['group_type_leaf_events'] = [];
    $subgroup_handler->removeLeaf($group_type);
    $this->assertCount(1, $GLOBALS['group_type_leaf_events'], 'Only one event was dispatched when leaf status was toggled off.');
    $this->assertEquals(
      LeafEvents::GROUP_TYPE_LEAF_REMOVE . ' dispatched for ' . $group_type->id(),
      $GLOBALS['group_type_leaf_events'][0],
      'Leaf remove event was dispatched when leaf status was toggled on.'
    );
  }

}
