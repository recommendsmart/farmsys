<?php

namespace Drupal\flow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\flow\Exception\FlowAfterTaskException;
use Drupal\flow\Exception\FlowBreakException;
use Drupal\flow\Exception\FlowEnqueueException;
use Drupal\flow\Exception\FlowException;
use Drupal\flow\Exception\TaskRecursionException;

/**
 * Service for working with the Flow task queue.
 *
 * Task operation handling usually consists the following order:
 * - A task is assigned to a subject and added to this queue service as item.
 * - The item is handled to be imminent, meaning main operation will be called
 *   within the same process the item was created.
 * - The operation itself might then realize it has to more work to do than one
 *   process could handle, thus throwing an according exception that allows
 *   to serialize the state of the item and enqueue it for later continuation.
 */
class FlowTaskQueue {

  /**
   * The according task queue instance.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * Flag indicating whether the queue is ensured to be created.
   *
   * @var bool
   */
  protected bool $queueCreated = FALSE;

  /**
   * A list of imminent tasks to process.
   *
   * @var \Drupal\flow\FlowTaskQueueItem[]
   */
  protected array $imminent = [];

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  /**
   * Whether task recursion should be logged.
   *
   * @var bool
   */
  public static bool $logTaskRecursion = TRUE;

  /**
   * The FlowTaskQueue constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   The logger channel.
   */
  public function __construct(QueueFactory $queue_factory, LoggerChannelInterface $logger_channel) {
    $this->queue = $queue_factory->get('flow_task', TRUE);
    $this->loggerChannel = $logger_channel;
  }

  /**
   * Get the Flow task queue service.
   *
   * @return \Drupal\flow\FlowTaskQueue
   *   The Flow task queue service.
   */
  public static function service(): FlowTaskQueue {
    return \Drupal::service('flow.task.queue');
  }

  /**
   * Adds an item for imminent processing.
   *
   * @param \Drupal\flow\FlowTaskQueueItem $item
   *   The item to add.
   *
   * @return $this
   */
  public function add(FlowTaskQueueItem $item): FlowTaskQueue {
    array_push($this->imminent, $item);
    return $this;
  }

  /**
   * Processes imminent tasks, optionally enqueues them for later processing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity where Flow is being applied on.
   * @param string $task_mode
   *   The applying task mode.
   * @param bool $task_completed
   *   Whether the task is completed or not. Default is FALSE.
   */
  public function process(EntityInterface $entity, string $task_mode, bool $task_completed = FALSE): void {
    // Fetch the current list of imminent tasks, hold them as a copy and reset
    // the imminent tasks property for the next process call. That way we allow
    // having nested queue processing calls without interferring each other.
    $tasks = $this->imminent;
    $this->imminent = [];
    while ($item = array_shift($tasks)) {
      $item_entity = $item->getEntity();
      if ((($item_entity !== $entity) && ($item_entity->uuid() !== $entity->uuid())) || ($item->getTaskMode() !== $task_mode)) {
        array_push($this->imminent, $item);
        continue;
      }
      // When the UUID matches, but not the entity object instance, the entity
      // most probably got cloned during the process (for example after
      // submitting an entity form). Re-instantiate the item with the currently
      // given entity, as this one should be the current state of its values.
      if (($item_entity !== $entity) && ($entity->uuid() && ($item_entity->uuid() === $entity->uuid()))) {
        $item = new FlowTaskQueueItem($entity, $item->getTaskMode(), $item->getTask(), $item->getSubject());
        $item_entity = $item->getEntity();
      }

      /** @var \Drupal\flow\Plugin\FlowTaskBase $task */
      $task = $item->getTask();
      $start = $task->configuration()['execution']['start'] ?? 'now';
      if (!($start == 'now') && !$task_completed) {
        // Defer the task to be executed after the task was completed. This
        // affects tasks whose execution start is set to "after" and "queue".
        array_push($this->imminent, $item);
        continue;
      }

      $log_context = [
        '@task_mode' => $item->getTaskMode(),
        '@task_label' => $task->getPluginDefinition()['label'],
        '@entity_type' => $item_entity->getEntityType()->getLabel(),
        '@entity_label' => $item_entity->label(),
        '@entity_uuid' => $item_entity->uuid(),
      ];

      if ($start === 'queue') {
        if (!$this->queueCreated) {
          $this->queue->createQueue();
        }
        $this->queue->createItem($item);
        $this->loggerChannel->info('Enqueued "@task_label" for @task_mode regarding @entity_type "@entity_label" (@entity_uuid).', $log_context);
        continue;
      }

      try {
        $item->operate();
        $this->loggerChannel->info('@task_mode task operation "@task_label" completed for @entity_type "@entity_label" (@entity_uuid).', $log_context);
      }
      catch (FlowException $e) {
        if ($e instanceof FlowAfterTaskException) {
          if ($task_completed) {
            throw new \RuntimeException("Cannot put a task to work after task operation, when the task was already completed.");
          }
          $task->configuration()['execution']['start'] = 'after';
          array_push($this->imminent, $item);
        }
        elseif ($e instanceof FlowEnqueueException) {
          if (!$this->queueCreated) {
            $this->queue->createQueue();
          }
          $task->configuration()['execution']['start'] = 'queue';
          $this->queue->createItem($item);
          $this->loggerChannel->info('Enqueued "@task_label" for @task_mode continuation regarding @entity_type "@entity_label" (@entity_uuid).', $log_context);
        }
        elseif ($e instanceof FlowBreakException) {
          $this->loggerChannel->info('@task_mode operation stopped by "@task_label" regarding @entity_type "@entity_label" (@entity_uuid).', $log_context);
          return;
        }
        else {
          $this->loggerChannel->notice('Flow exception occurred on @task_mode operation thrown by "@task_label" regarding @entity_type "@entity_label" (@entity_uuid): @message', $log_context + [
            '@message' => $e->getMessage() ?: 'No exception message.',
          ]);
        }
      }
      catch (TaskRecursionException $e) {
        if (self::$logTaskRecursion) {
          $this->loggerChannel->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Implements magic method __sleep().
   */
  public function __sleep() {
    $vars = array_keys(get_object_vars($this));
    return array_filter($vars, function ($value) {
      return $value !== 'queue';
    });
  }

  /**
   * Implements magic method __wakeup().
   */
  public function __wakeup() {
    $this->queue = \Drupal::queue('flow_task', TRUE);
  }

}
