<?php

namespace Drupal\flow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Drupal\flow\Plugin\FlowTaskInterface;

/**
 * Holds a task, assigned to a subject, that is to be processed via queue.
 */
class FlowTaskQueueItem {

  /**
   * The entity that triggered the flow.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The task mode.
   *
   * @var string
   */
  protected string $taskMode;

  /**
   * The contained task.
   *
   * @var \Drupal\flow\Plugin\FlowTaskInterface
   */
  protected FlowTaskInterface $task;

  /**
   * The contained subject.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectInterface
   */
  protected FlowSubjectInterface $subject;

  /**
   * Whether processing of the operation is complete.
   *
   * @var bool
   */
  protected bool $finished = FALSE;

  /**
   * Constructs a new FlowTaskQueueItem.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the flow.
   * @param string $task_mode
   *   The task mode.
   * @param \Drupal\flow\Plugin\FlowTaskInterface $task
   *   The task.
   * @param \Drupal\flow\Plugin\FlowSubjectInterface $subject
   *   The subject.
   */
  public function __construct(EntityInterface $entity, string $task_mode, FlowTaskInterface $task, FlowSubjectInterface $subject) {
    $this->entity = $entity;
    $this->taskMode = $task_mode;
    $this->task = $task;
    $this->subject = $subject;
  }

  /**
   * Operates the contained task on its assigned subject.
   *
   * @throws \Drupal\flow\Exception\FlowException
   *   When something goes wrong and should be handled by Flow.
   *
   * @see \Drupal\flow\Plugin\FlowTaskInterface::operate()
   */
  public function operate(): void {
    if (!$this->finished) {
      $this->task->operate($this->subject);
    }
    $this->finished = TRUE;
  }

  /**
   * Whether processing of the operation is complete.
   *
   * @return bool
   *   Returns TRUE if operation is complete, FALSE otherwise.
   */
  public function isFinished(): bool {
    return $this->finished;
  }

  /**
   * Get the entity that triggered the flow.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the task mode.
   *
   * @return string
   *   The task mode.
   */
  public function getTaskMode(): string {
    return $this->taskMode;
  }

  /**
   * Get the task.
   *
   * @return \Drupal\flow\Plugin\FlowTaskInterface
   *   The task.
   */
  public function getTask(): FlowTaskInterface {
    return $this->task;
  }

  /**
   * Get the subject.
   *
   * @return \Drupal\flow\Plugin\FlowSubjectInterface
   *   The subject.
   */
  public function getSubject(): FlowSubjectInterface {
    return $this->subject;
  }

}
