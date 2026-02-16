<?php

namespace Drupal\entity_inherit\Utilities;

/**
 * Manage Friends.
 *
 * See https://stackoverflow.com/a/317903/1207752.
 */
trait FriendTrait {

  /**
   * Throw an exception if the 2nd-level caller is not a friend.
   *
   * @param array $friends
   *   A list of friends such as [ClassA::class, ClassB::class].
   *
   * @throws \Exception
   *   An exception if the 2nd-level caller is not a friend.
   */
  protected function friendAccess(array $friends) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    $class = isset($trace[2]['class']) ? $trace[2]['class'] : 'not a class';
    if (!in_array($class, $friends)) {
      throw new \Exception('Only the following classes, not ' . $class . ', have access to this function.');
    }
  }

}
