<?php

namespace Drupal\flow;

/**
 * Defines Flow-related events.
 */
final class FlowEvents {

  /**
   * Dispatched before configured flow begins to be applied.
   *
   * @Event
   *
   * @var string
   */
  const BEGIN = 'flow.begin';

  /**
   * Dispatched after configured flow was applied.
   *
   * @Event
   *
   * @var string
   */
  const END = 'flow.end';

}
