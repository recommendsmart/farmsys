<?php

namespace Drupal\flow\Helpers;

use Drupal\flow\Flow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trait for subject plugins using the most recent entity from the Flow stack.
 */
trait StackSubjectTrait {

  /**
   * The subject items from the Flow stack.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected array $stackItems;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $task_mode = $configuration['task_mode'] ?? $instance->getBaseId();
    if (isset($task_mode)) {
      $stack = Flow::$stack[$task_mode] ?? [];
    }
    $instance->stackItems = !empty($stack) ? [end($stack)] : [];
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectItems(): iterable {
    return $this->stackItems;
  }

}
