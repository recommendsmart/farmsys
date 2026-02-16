<?php

namespace Drupal\flow\Event;

use Drupal\flow\FlowTaskQueueItem;

/**
 * Runtime context available when the task worker processes an enqueued task.
 *
 * If configured, the Flow engine enqueues tasks and the task worker
 * \Drupal\flow\Plugin\QueueWorker\FlowTaskWorker processes them one by one.
 */
class FlowTaskWorkerRuntimeContext extends RuntimeContextBase {

  /**
   * The task queue item.
   *
   * @var \Drupal\flow\FlowTaskQueueItem
   */
  protected FlowTaskQueueItem $taskQueueItem;

  /**
   * Constructs a new FlowTaskWorkerRuntimeContext object.
   *
   * @param \Drupal\flow\FlowTaskQueueItem $item
   *   The task queue item.
   */
  public function __construct(FlowTaskQueueItem $item) {
    $this->taskQueueItem = $item;
  }

  /**
   * Get the task queue item.
   *
   * @return \Drupal\flow\FlowTaskQueueItem
   *   The task queue item.
   */
  public function getTaskQueueItem(): FlowTaskQueueItem {
    return $this->taskQueueItem;
  }

}
