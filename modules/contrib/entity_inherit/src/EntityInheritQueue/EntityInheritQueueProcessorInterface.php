<?php

namespace Drupal\entity_inherit\EntityInheritQueue;

/**
 * A queue.
 */
interface EntityInheritQueueProcessorInterface {

  /**
   * Process the queue associated to this processor.
   */
  public function process();

  /**
   * Process a single item in the batch.
   *
   * @param array $context
   *   The batch context array.
   */
  public function batchOperation(array &$context);

  /**
   * Batch finish handler.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   Contains the operations that remained unprocessed.
   */
  public function batchFinished(bool $success, array $results, array $operations);

}
