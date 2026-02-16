<?php

namespace Drupal\entity_inherit\EntityInheritQueue;

/**
 * A queue processor for the GUI.
 */
class EntityInheritQueueProcessorBatch extends EntityInheritQueueProcessor {

  /**
   * {@inheritdoc}
   */
  public function process() {
    $process['operations'] = [];

    $count = count($this->queue);

    for ($i = 0; $i < $count; $i++) {
      $process['operations'][] = [
        'entity_inherit_batch_operation',
        [],
      ];
    }

    $process['finished'] = 'entity_inherit_batch_finished';

    batch_set($process);
  }

  /**
   * {@inheritdoc}
   */
  public function batchOperation(array &$context) {
    $this->queue->processNext();
  }

  /**
   * {@inheritdoc}
   */
  public function batchFinished(bool $success, array $results, array $operations) {
    if (count($this->queue)) {
      $this->process();
    }
  }

}
