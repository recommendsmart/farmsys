<?php

namespace Drupal\flow\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class for any Flow-related event.
 */
class FlowEvent extends Event {

  /**
   * The entity.
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
   * The runtime context.
   *
   * @var \Drupal\flow\Event\RuntimeContextInterface
   */
  protected RuntimeContextInterface $runtimeContext;

  /**
   * Constructs a new FlowEvent object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which flow is being applied.
   * @param string $task_mode
   *   The applied task mode.
   * @param \Drupal\flow\Event\RuntimeContextInterface $runtime_context
   *   The runtime context.
   */
  public function __construct(EntityInterface $entity, string $task_mode, RuntimeContextInterface $runtime_context) {
    $this->entity = $entity;
    $this->taskMode = $task_mode;
    $this->runtimeContext = $runtime_context;
  }

  /**
   * Get the entity.
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
   * Get the runtime context.
   *
   * @return \Drupal\flow\Event\RuntimeContextInterface
   *   The runtime context.
   */
  public function getRuntimeContext(): RuntimeContextInterface {
    return $this->runtimeContext;
  }

}
