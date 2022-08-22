<?php

namespace Drupal\flow\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flow\Flow;
use Drupal\flow\FlowTaskMode;
use Drupal\flow\Helpers\EntityRepositoryTrait;
use Drupal\flow\Helpers\FlowEngineTrait;
use Drupal\flow\Plugin\flow\Subject\Action;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action to execute flow.
 *
 * @Action(
 *   id = "flow",
 *   label = @Translation("Flow"),
 *   deriver = "Drupal\flow\Plugin\Action\FlowActionDeriver"
 * )
 */
class FlowAction extends ActionBase implements ContainerFactoryPluginInterface {

  use FlowEngineTrait;
  use EntityRepositoryTrait;

  /**
   * Initialized list of available task modes.
   *
   * @var string[]|null
   */
  protected static ?array $taskModes = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->setFlowEngine($container->get(self::$flowEngineServiceName));
    $instance->setEntityRepository($container->get(self::$entityRepositoryServiceName));
    $instance::$taskModes = $instance::$taskModes ?? array_keys([
      'save' => 1,
      'create' => 1,
      'delete' => 1,
    ] + FlowTaskMode::service()->getAvailableTaskModes());
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $previous = Action::$items;
    Action::$items = $entities;
    [, $task_mode] = explode('.', $this->getDerivativeId());
    foreach (Action::$items as &$entity) {
      if (!($entity instanceof ContentEntityInterface)) {
        continue;
      }

      $this->doExecute($entity, $task_mode);
    }
    unset($entity);
    Action::$items = $previous;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $previous = Action::$items;
    Action::$items = [];
    [, $task_mode] = explode('.', $this->getDerivativeId());
    foreach (Action::$items as &$entity) {
      if (!($entity instanceof ContentEntityInterface)) {
        continue;
      }

      $this->doExecute($entity, $task_mode);
    }
    unset($entity);
    Action::$items = $previous;
  }

  /**
   * Implementation detail of the action execution.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to operate on.
   * @param string $task_mode
   *   The task mode.
   */
  protected function doExecute(ContentEntityInterface $entity, string $task_mode): void {
    $this->flowEngine->apply($entity, $task_mode);
    // If permitted, invoke a save call, as flow is being directly applied and
    // saving the entity is ususally part of the automated process.
    if ((($task_mode !== 'delete') && !$entity->isNew()) || !in_array($task_mode, static::$taskModes, TRUE)) {
      $flow_is_active = Flow::isActive();
      Flow::setActive(FALSE);
      try {
        $entity->save();
      }
      finally {
        Flow::setActive($flow_is_active);
      }
    }
    _flow_process_after_task($entity, $task_mode);
  }

}
