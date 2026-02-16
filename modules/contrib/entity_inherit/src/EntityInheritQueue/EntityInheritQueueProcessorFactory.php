<?php

namespace Drupal\entity_inherit\EntityInheritQueue;

use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\Utilities\FriendTrait;

/**
 * A queue.
 */
class EntityInheritQueueProcessorFactory {

  use FriendTrait;

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The application singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->friendAccess([EntityInherit::class]);
    $this->app = $app;
  }

  /**
   * Obtain an appropriate processor.
   *
   * @param \Drupal\entity_inherit\EntityInheritQueue\EntityInheritQueue $queue
   *   The queue.
   *
   * @return \Drupal\entity_inherit\EntityInheritQueue\EntityInheritQueueProcessorInterface
   *   A processor.
   */
  public function processor(EntityInheritQueue $queue) : EntityInheritQueueProcessorInterface {
    switch (php_sapi_name()) {
      case 'cli':
        return new EntityInheritQueueProcessorNoBatch($queue);

      default:
        return new EntityInheritQueueProcessorBatch($queue);
    }
  }

}
