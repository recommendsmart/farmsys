<?php

namespace Drupal\subgroup\Event;

/**
 * Defines events for the manipulation of tree leaves.
 */
final class LeafEvents {

  /**
   * Name of the event fired when updating a group to become a leaf.
   *
   * @Event
   *
   * @see \Drupal\subgroup\Event\GroupLeafEvent
   */
  const GROUP_LEAF_ADD = 'subgroup.group_leaf.add';

  /**
   * Name of the event fired when updating a group to stop being a leaf.
   *
   * @Event
   *
   * @see \Drupal\subgroup\Event\GroupLeafEvent
   */
  const GROUP_LEAF_REMOVE = 'subgroup.group_leaf.remove';

  /**
   * Name of the event fired when updating a group type to become a leaf.
   *
   * @Event
   *
   * @see \Drupal\subgroup\Event\GroupTypeLeafEvent
   */
  const GROUP_TYPE_LEAF_ADD = 'subgroup.group_type_leaf.add';

  /**
   * Name of the event fired when importing a group type that is a leaf.
   *
   * @Event
   *
   * @see \Drupal\subgroup\Event\GroupTypeLeafEvent
   */
  const GROUP_TYPE_LEAF_IMPORT = 'subgroup.group_type_leaf.import';

  /**
   * Name of the event fired when updating a group type to stop being a leaf.
   *
   * @Event
   *
   * @see \Drupal\subgroup\Event\GroupTypeLeafEvent
   */
  const GROUP_TYPE_LEAF_REMOVE = 'subgroup.group_type_leaf.remove';

}
