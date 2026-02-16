<?php

namespace Drupal\subgroup\Event;

use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for group type leaf status changes.
 *
 * @see \Drupal\subgroup\Event\LeafEvents
 */
class GroupTypeLeafEvent extends Event {

  /**
   * The group type that was manipulated.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * Constructs a new GroupTypeLeafEvent object.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type that was manipulated. Must have the original property set
   *   and pointing to the group type before it was manipulated.
   */
  public function __construct(GroupTypeInterface $group_type) {
    if (!isset($group_type->original)) {
      throw new \InvalidArgumentException('Provided group type need to have its "original" property set.');
    }
    $this->groupType = $group_type;
  }

  /**
   * Gets the group type that was manipulated.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type that was manipulated.
   */
  public function getGroupType() {
    return $this->groupType;
  }

}
