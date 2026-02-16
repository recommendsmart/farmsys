<?php

namespace Drupal\entity_inherit\EntityInheritQueue;

/**
 * A queue processor for the command line.
 */
class EntityInheritQueueProcessorNoBatch extends EntityInheritQueueProcessor {

  /**
   * {@inheritdoc}
   */
  public function process() {
    while ($next = $this->queue->processNext()) {
      print_r('----> Finished processing ' . $next . '.' . PHP_EOL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function batchOperation(array &$context) {
    print_r('This is meant to be called from the GUI, not the command line.');
  }

  /**
   * {@inheritdoc}
   */
  public function batchFinished(bool $success, array $results, array $operations) {
    print_r('This is meant to be called from the GUI, not the command line.');
  }

}
