<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flow\Flow;

/**
 * Trait for components that use the current entity from the Flow stack.
 */
trait EntityFromStackTrait {

  /**
   * The entity in scope from the Flow stack.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|null
   */
  protected ?ContentEntityInterface $entityFromStack;

  /**
   * Get the entity in scope from the Flow stack.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity, or NULL if not set.
   */
  public function getEntityFromStack(): ?ContentEntityInterface {
    return $this->entityFromStack;
  }

  /**
   * Initializes the property for the entity in scope from the Flow stack.
   */
  public function initEntityFromStack(): void {
    $task_mode = $this->configuration['task_mode'] ?? $this->getBaseId();
    if (isset($task_mode)) {
      $stack = Flow::$stack[$task_mode] ?? [];
    }
    $this->entityFromStack = !empty($stack) ? end($stack) : NULL;
  }

}
