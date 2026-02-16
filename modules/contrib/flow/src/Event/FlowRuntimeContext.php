<?php

namespace Drupal\flow\Event;

use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\FlowTaskQueueItem;

/**
 * Runtime context available when Flow engine applies configured flow.
 *
 * Flow configuration is initially being applied by the Flow engine via
 * \Drupal\flow\Flow::apply(). Whether tasks are to be executed immediately
 * or delayed e.g. by being enqueued first, is being decided by the engine.
 */
class FlowRuntimeContext extends RuntimeContextBase {

  /**
   * The Flow configuration in scope.
   *
   * @var \Drupal\flow\Entity\FlowInterface
   */
  protected FlowInterface $flow;

  /**
   * Task queue items that were added on runtime.
   *
   * @var \Drupal\flow\FlowTaskQueueItem[]|null
   */
  protected ?array $addedTaskQueueItems = NULL;

  /**
   * Constructs a new FlowRuntimeContext object.
   *
   * @param \Drupal\flow\Entity\FlowInterface $flow
   *   The Flow configuration in scope.
   */
  public function __construct(FlowInterface $flow) {
    $this->flow = $flow;
  }

  /**
   * Get the Flow configuration in scope.
   *
   * @return \Drupal\flow\Entity\FlowInterface
   *   The Flow configuration.
   */
  public function getFlow(): FlowInterface {
    return $this->flow;
  }

  /**
   * Get added task queue items.
   *
   * Added task queue items are only available after configured flow got
   * applied. For example when listening on the event object of type
   * \Drupal\flow\Event\FlowEndEvent. It is definetly *not* available before
   * flow begins to get applied, e.g. when listening on
   * \Drupal\flow\Event\FlowBeginEvent.
   *
   * @return \Drupal\flow\FlowTaskQueueItem[]|null
   *   The added task queue items. May be an empty array if not item was added.
   *   Returns NULL if no added task queue items are available because it's too
   *   early to ask for them.
   */
  public function getAddedTaskQueueItems(): ?array {
    return $this->addedTaskQueueItems;
  }

  /**
   * Registers a task queue item that got added on runtime.
   *
   * @internal
   *   This method is only relevant for the Flow engine and nothing else.
   *
   * @param \Drupal\flow\FlowTaskQueueItem $item
   *   The task queue item.
   *
   * @return $this
   */
  public function addTaskQueueItem(FlowTaskQueueItem $item): FlowRuntimeContext {
    if (!isset($this->addedTaskQueueItems)) {
      $this->addedTaskQueueItems = [];
    }
    $this->addedTaskQueueItems[] = $item;
    return $this;
  }

}
