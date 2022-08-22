<?php

namespace Drupal\flow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flow\Event\FlowBeginEvent;
use Drupal\flow\Event\FlowEndEvent;
use Drupal\flow\Entity\EntitySaveHandler;
use Drupal\flow\Entity\Flow as Entity;
use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\Event\FlowRuntimeContext;
use Drupal\flow\Exception\TaskRecursionException;
use Drupal\flow\Helpers\EventDispatcherTrait;
use Drupal\flow\Plugin\flow\Subject\Qualified;
use Drupal\flow\Plugin\FlowSubjectCollection;

/**
 * Engine for applying configured flow.
 */
class Flow {

  use EventDispatcherTrait;

  /**
   * A static stack of entities where Flow is being applied on.
   *
   * Keyed by task mode. This stack may be used by plugins, such as the "create"
   * subject plugin.
   *
   * @var \Drupal\Core\Entity\EntityInterface[][]
   */
  public static array $stack = [];

  /**
   * A static collection of entities that need to be saved.
   *
   * When you have an entity that needs to be saved, and you want the Flow
   * engine to properly take care of that, call \Drupal\flow\Flow::needsSave().
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  public static array $save = [];

  /**
   * A filter to use when loading relevant tasks.
   *
   * @var array
   */
  public static array $filter = ['active' => TRUE];

  /**
   * Whether the engine is active and applies existing configurations.
   *
   * @var bool
   *
   * @see \Drupal\flow\Flow::isActive()
   */
  protected static bool $isActive = TRUE;

  /**
   * Get the Flow engine service.
   *
   * @return \Drupal\flow\Flow
   *   The Flow engine service.
   */
  public static function service(): Flow {
    return \Drupal::service('flow');
  }

  /**
   * Returns the Flow configuration entity for the given parameters.
   *
   * This method is just an alias and the same as when calling
   * \Drupal\flow\Entity\Flow::getFlow().
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $task_mode
   *   The task mode.
   *
   * @return \Drupal\flow\Entity\FlowInterface|null
   *   The config entity. Could be a new one. Returns NULL if the entity type
   *   is not supported for having Flow configurations.
   */
  public static function getFlow(string $entity_type_id, string $bundle, string $task_mode): ?FlowInterface {
    return Entity::getFlow($entity_type_id, $bundle, $task_mode);
  }

  /**
   * Tells the Flow engine that an entity needs to be saved.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that needs to be saved.
   * @param object $component
   *   (optional) The component that is telling Flow about this need.
   */
  public static function needsSave(EntityInterface $entity, $component = NULL): void {
    foreach ($entity->referencedEntities() as $referenced) {
      if ($referenced->isNew() && !in_array($referenced, self::$save, TRUE)) {
        array_push(self::$save, $referenced);
      }
    }
    if (($index = array_search($entity, self::$save, TRUE)) !== FALSE) {
      unset(self::$save[$index]);
    }
    array_push(self::$save, $entity);
    EntitySaveHandler::service()->saveIfRequired(self::$save);
  }

  /**
   * Get the info whether the engine is active or not.
   *
   * @return bool
   *   Returns TRUE when the engine active and existing configuration is being
   *   applied, FALSE otherwise.
   */
  public static function isActive(): bool {
    return self::$isActive;
  }

  /**
   * Set the engine to be active or not.
   *
   * @param bool $active
   *   Whether the engine should be active. Default is TRUE.
   */
  public static function setActive(bool $active = TRUE): void {
    self::$isActive = $active;
  }

  /**
   * Applies configured flow on the given entity using the specified task mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity where to apply the flow.
   * @param string $task_mode
   *   The task mode to apply.
   *
   * @throws \Drupal\flow\Exception\TaskRecursionException
   *   When task recursion occurs. Usually the Flow task queue is always
   *   involved when calling this method, and takes care of this exception.
   */
  public function apply(EntityInterface $entity, string $task_mode): void {
    if (!self::isActive() || !($flow = Entity::getFlow($entity->getEntityTypeId(), $entity->bundle(), $task_mode))) {
      return;
    }
    if (!$flow->getStatus()) {
      return;
    }

    // For detecting task recursion, look at all stacked entities, no matter the
    // task mode. For example, when an entity is in the "create" or "delete"
    // stack, we also don't want it to be saved - as it makes no sense to do so.
    foreach (self::$stack as &$stacked_entities) {
      if (in_array($entity, $stacked_entities, TRUE)) {
        throw new TaskRecursionException($task_mode, $entity);
      }
    }

    if (!isset(self::$stack[$task_mode])) {
      self::$stack[$task_mode] = [];
    }
    $stack = &self::$stack[$task_mode];
    array_push($stack, $entity);
    $runtime_context = new FlowRuntimeContext($flow);
    $this->getEventDispatcher()->dispatch(new FlowBeginEvent($entity, $task_mode, $runtime_context), FlowEvents::BEGIN);

    if (self::isActive() && $flow->getStatus()) {
      $queue = FlowTaskQueue::service();

      $prepare_subject = static function (Entity $flow, FlowSubjectCollection $subjects, $i) {
        /** @var \Drupal\flow\Plugin\FlowSubjectInterface $subject */
        $subject = $subjects->get($i);

        // Custom flow includes a mechanic for qualified subjects. Therefore
        // prepare qualifying subjects according to this configuration.
        if ($subject instanceof Qualified) {
          $qualifiers = $flow->getQualifiers(self::$filter);
          /** @var \Drupal\flow\Plugin\FlowSubjectInterface $qualifying */
          foreach ($flow->getQualifyingSubjects(self::$filter) as $k => $qualifying) {
            $qualifier = $qualifiers->get($k);
            if (($subject->getEntityTypeId() === $qualifying->getEntityTypeId()) && ($subject->getEntityBundle() === $qualifying->getEntityBundle())) {
              $subject->addQualifying($qualifying, $qualifier);
            }
          }
        }

        return $subject;
      };

      // Add the tasks with their according subjects.
      $tasks = $flow->getTasks(self::$filter);
      $subjects = $flow->getSubjects(self::$filter);
      foreach ($tasks as $i => $task) {
        $subject = $prepare_subject($flow, $subjects, $i);
        $item = new FlowTaskQueueItem($entity, $task_mode, $task, $subject);
        $queue->add($item);
        $runtime_context->addTaskQueueItem($item);
      }

      // Also add tasks from custom flow, if any.
      if (!$flow->isCustom()) {
        foreach ($flow->getCustomFlow() as $custom_flow) {
          if (!$custom_flow->getStatus()) {
            continue;
          }

          $tasks = $custom_flow->getTasks(self::$filter);
          $subjects = $custom_flow->getSubjects(self::$filter);
          foreach ($tasks as $i => $task) {
            $subject = $prepare_subject($flow, $subjects, $i);
            $item = new FlowTaskQueueItem($entity, $task_mode, $task, $subject);
            $queue->add($item);
            $runtime_context->addTaskQueueItem($item);
          }
        }
      }

      // We now have all imminent tasks, so process them one by one.
      self::setActive(\Drupal::getContainer()->getParameter('flow.allow_nested_flow'));
      try {
        $queue->process($entity, $task_mode);
        // Finally make sure, that changed entities are being saved.
        if (!empty(self::$save)) {
          EntitySaveHandler::service()->ensureSave(self::$save);
        }
      }
      finally {
        self::setActive(TRUE);
      }
    }

    $this->getEventDispatcher()->dispatch(new FlowEndEvent($entity, $task_mode, $runtime_context), FlowEvents::END);
    array_pop($stack);
  }

}
