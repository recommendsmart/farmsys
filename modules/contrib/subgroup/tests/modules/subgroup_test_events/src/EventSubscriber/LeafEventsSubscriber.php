<?php

namespace Drupal\subgroup_test_events\EventSubscriber;

use Drupal\subgroup\Event\GroupLeafEvent;
use Drupal\subgroup\Event\GroupTypeLeafEvent;
use Drupal\subgroup\Event\LeafEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to group type leaf status changes.
 */
class LeafEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LeafEvents::GROUP_LEAF_ADD] = 'onAddGroupLeaf';
    $events[LeafEvents::GROUP_LEAF_REMOVE] = 'onRemoveGroupLeaf';
    $events[LeafEvents::GROUP_TYPE_LEAF_ADD] = 'onAddGroupTypeLeaf';
    $events[LeafEvents::GROUP_TYPE_LEAF_REMOVE] = 'onRemoveGroupTypeLeaf';
    return $events;
  }

  /**
   * Handles the group add leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupLeafEvent $event
   *   The add group leaf event.
   */
  public function onAddGroupLeaf(GroupLeafEvent $event) {
    $GLOBALS['group_leaf_events'][] = LeafEvents::GROUP_LEAF_ADD . ' dispatched for ' . $event->getGroup()->id();
  }

  /**
   * Handles the group remove leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupLeafEvent $event
   *   The remove group leaf event.
   */
  public function onRemoveGroupLeaf(GroupLeafEvent $event) {
    $GLOBALS['group_leaf_events'][] = LeafEvents::GROUP_LEAF_REMOVE . ' dispatched for ' . $event->getGroup()->id();
  }

  /**
   * Handles the group type add leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The add group type leaf event.
   */
  public function onAddGroupTypeLeaf(GroupTypeLeafEvent $event) {
    $GLOBALS['group_type_leaf_events'][] = LeafEvents::GROUP_TYPE_LEAF_ADD . ' dispatched for ' . $event->getGroupType()->id();
  }

  /**
   * Handles the group type remove leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The remove group type leaf event.
   */
  public function onRemoveGroupTypeLeaf(GroupTypeLeafEvent $event) {
    $GLOBALS['group_type_leaf_events'][] = LeafEvents::GROUP_TYPE_LEAF_REMOVE . ' dispatched for ' . $event->getGroupType()->id();
  }

}
