<?php

namespace Drupal\flow\Exception;

use Drupal\Core\Entity\EntityInterface;

/**
 * Thrown internally by the Flow engine, when task recursion occurs.
 */
class TaskRecursionException extends \RuntimeException {

  /**
   * The affected task mode.
   *
   * @var string
   */
  protected string $taskMode;

  /**
   * The affected entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * Constructs a new RecursiveSaveException.
   *
   * @param string $task_mode
   *   The affected task mode.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The affected entity.
   * @param string $message
   *   The exception message.
   * @param int $code
   *   The exception code.
   * @param \Throwable|null $previous
   *   The previous throwable.
   */
  public function __construct(string $task_mode, EntityInterface $entity, string $message = "", int $code = 0, ?\Throwable $previous = NULL) {
    if (empty($message)) {
      $message = sprintf("Flow: Task recursion occurred on '%s' task for %s entity with UUID %s.", $task_mode, $entity->getEntityTypeId(), $entity->uuid());
    }
    parent::__construct($message, $code, $previous);
  }

  /**
   * Get the affected task mode.
   *
   * @return string
   *   The afffected task mode.
   */
  public function getTaskMode(): string {
    return $this->taskMode;
  }

  /**
   * Get the affected entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The affected entity.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
