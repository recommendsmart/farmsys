<?php

namespace Drupal\entity_inherit\EntityInheritQueue;

/**
 * A queue processor.
 */
abstract class EntityInheritQueueProcessor implements EntityInheritQueueProcessorInterface {

  /**
   * The queue.
   *
   * @var \Drupal\entity_inherit\EntityInheritQueue\EntityInheritQueue
   */
  protected $queue;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInheritQueue\EntityInheritQueue $queue
   *   The queue.
   */
  public function __construct(EntityInheritQueue $queue) {
    $this->queue = $queue;
  }

}
