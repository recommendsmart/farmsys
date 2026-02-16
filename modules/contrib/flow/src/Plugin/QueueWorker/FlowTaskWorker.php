<?php

namespace Drupal\flow\Plugin\QueueWorker;

use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\flow\Entity\EntitySaveHandler;
use Drupal\flow\Event\FlowBeginEvent;
use Drupal\flow\Event\FlowEndEvent;
use Drupal\flow\Event\FlowTaskWorkerRuntimeContext;
use Drupal\flow\Flow;
use Drupal\flow\FlowEvents;
use Drupal\flow\FlowTaskQueue;
use Drupal\flow\FlowTaskQueueItem;
use Drupal\flow\Helpers\EntityRepositoryTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\EventDispatcherTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes enqueued Flow tasks.
 *
 * @QueueWorker(
 *   id = "flow_task",
 *   title = @Translation("Flow tasks"),
 *   cron = {"time" = 60}
 * )
 */
class FlowTaskWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use EventDispatcherTrait;
  use EntityTypeManagerTrait;
  use EntityRepositoryTrait;

  /**
   * The Flow task queue service.
   *
   * @var \Drupal\flow\FlowTaskQueue
   */
  protected FlowTaskQueue $taskQueue;

  /**
   * Constructs a FlowTaskWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flow\FlowTaskQueue $task_queue
   *   The task queue service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlowTaskQueue $task_queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->taskQueue = $task_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flow.task.queue')
    );
    $instance->setEventDispatcher($container->get(static::$eventDispatcherServiceName));
    $instance->setEntityTypeManager($container->get(static::$entityTypeManagerServiceName));
    $instance->setEntityRepository($container->get(static::$entityRepositoryServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    if (!($item instanceof FlowTaskQueueItem)) {
      return;
    }
    $entity = $item->getEntity();
    $entity_type_id = $entity->getEntityTypeId();

    // Get the current state of the Flow-related entity. If it doesn't exist
    // anymore due to a missing type definition, then skip the processing.
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return;
    }
    if ($entity->uuid() && ($loaded = $this->entityRepository->loadEntityByUuid($entity_type_id, $entity->uuid()))) {
      if ($loaded->language()->getId() == $entity->language()->getId()) {
        $entity = $loaded;
      }
      elseif (($loaded instanceof TranslatableInterface) && $loaded->hasTranslation($entity->language()->getId())) {
        $entity = $loaded->getTranslation($entity->language()->getId());
      }
    }

    $task_mode = $item->getTaskMode();

    // Refresh the current state of the queued item.
    /** @var \Drupal\flow\Plugin\FlowTaskBase $task */
    $task = $item->getTask();
    // Handle the task to be executed immediately.
    $task->configuration()['execution']['start'] = 'now';
    $subject = $item->getSubject();
    $item = new FlowTaskQueueItem($entity, $task_mode, $task, $subject);

    if (!isset(Flow::$stack[$task_mode])) {
      Flow::$stack[$task_mode] = [];
    }
    $stack = &Flow::$stack[$task_mode];
    array_push($stack, $entity);
    $runtime_context = new FlowTaskWorkerRuntimeContext($item);
    $this->getEventDispatcher()->dispatch(new FlowBeginEvent($entity, $task_mode, $runtime_context), FlowEvents::BEGIN);
    $entity_needs_save = FALSE;

    if (Flow::isActive()) {
      $queue = $this->taskQueue;
      $queue->add($item);

      Flow::setActive(\Drupal::getContainer()->getParameter('flow.allow_nested_flow'));
      try {
        $queue->process($entity, $task_mode);
        if (!empty(Flow::$save)) {
          $entity_needs_save = $loaded && $task_mode !== 'delete' && (in_array($entity, Flow::$save, TRUE) || array_filter(Flow::$save, function ($stacked) use ($entity) {return $entity->uuid() && ($stacked->uuid() === $entity->uuid());}));
          EntitySaveHandler::service()->ensureSave(Flow::$save);
        }
      }
      finally {
        Flow::setActive(TRUE);
      }
    }

    $this->getEventDispatcher()->dispatch(new FlowEndEvent($entity, $task_mode, $runtime_context), FlowEvents::END);
    array_pop($stack);
    if ($entity_needs_save) {
      $flow_is_active = Flow::isActive();
      Flow::setActive(FALSE);
      try {
        $entity->save();
      }
      finally {
        Flow::setActive($flow_is_active);
      }
    }
  }

}
