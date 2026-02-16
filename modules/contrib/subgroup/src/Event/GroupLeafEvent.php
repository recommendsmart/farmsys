<?php

namespace Drupal\subgroup\Event;

use Drupal\group\Entity\GroupInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for group leaf status changes.
 *
 * @see \Drupal\subgroup\Event\LeafEvents
 */
class GroupLeafEvent extends Event {

  /**
   * The group that was manipulated.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupLeafEvent object.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group that was manipulated. Must have the original property set and
   *   pointing to the group before it was manipulated.
   */
  public function __construct(GroupInterface $group) {
    if (!isset($group->original)) {
      throw new \InvalidArgumentException('Provided group need to have its "original" property set.');
    }
    $this->group = $group;
  }

  /**
   * Gets the group that was manipulated.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group that was manipulated.
   */
  public function getGroup() {
    return $this->group;
  }

}
