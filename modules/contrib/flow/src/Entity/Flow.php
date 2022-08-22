<?php

namespace Drupal\flow\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\flow\FlowTaskMode;
use Drupal\flow\Plugin\FlowQualifierCollection;
use Drupal\flow\Plugin\FlowSubjectCollection;
use Drupal\flow\Plugin\FlowTaskCollection;

/**
 * Defines the Flow config entity.
 *
 * @ConfigEntityType(
 *   id = "flow",
 *   label = @Translation("Flow"),
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status",
 *     "uuid" = "uuid",
 *     "label" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "status",
 *     "targetEntityType",
 *     "targetBundle",
 *     "taskMode",
 *     "tasks",
 *     "custom"
 *   },
 *   lookup_keys = {
 *     "targetEntityType",
 *     "targetBundle",
 *     "custom.BaseMode"
 *   }
 * )
 */
class Flow extends ConfigEntityBase implements FlowInterface {

  /**
   * The entity type for which this flow is used.
   *
   * @var string
   */
  protected string $targetEntityType;

  /**
   * The entity bundle for which this flow is used.
   *
   * @var string
   */
  protected string $targetBundle;

  /**
   * The task mode, ususally one of "create", "save" and "delete".
   *
   * @var string
   */
  protected string $taskMode;

  /**
   * List of settings for tasks, sorted by weight.
   *
   * @var array
   */
  protected array $tasks = [];

  /**
   * Custom flow settings.
   *
   * @var array
   */
  protected array $custom = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->targetEntityType . '.' . $this->targetBundle . '.' . $this->taskMode;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->isCustom() ? $this->custom['label'] : (FlowTaskMode::service()->getAvailableTaskModes()[$this->taskMode] ?? $this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): bool {
    return (bool) $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status): FlowInterface {
    $this->status = (bool) $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): string {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle(): string {
    return $this->targetBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskMode(): string {
    return $this->taskMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getTasks(?array $filter = NULL): FlowTaskCollection {
    $configs = [];
    $flow_keys = [
      'entity_type_id' => $this->getTargetEntityTypeId(),
      'bundle' => $this->getTargetBundle(),
      'task_mode' => $this->getTaskMode(),
    ];
    if ($this->isCustom()) {
      $flow_keys['custom_task_mode'] = $flow_keys['task_mode'];
      $flow_keys['task_mode'] = $this->custom['baseMode'];
    }
    foreach ($this->tasks as $i => $task) {
      if (isset($filter) && !($task == NestedArray::mergeDeep($task, $filter))) {
        continue;
      }
      $configs[$i] = $task + $flow_keys;
    }
    return new FlowTaskCollection(\Drupal::service('plugin.manager.flow.task'), $configs);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjects(?array $filter = NULL): FlowSubjectCollection {
    $configs = [];
    $flow_keys = [
      'entity_type_id' => $this->getTargetEntityTypeId(),
      'bundle' => $this->getTargetBundle(),
      'task_mode' => $this->getTaskMode(),
    ];
    if ($this->isCustom()) {
      $flow_keys['custom_task_mode'] = $flow_keys['task_mode'];
      $flow_keys['task_mode'] = $this->custom['baseMode'];
    }
    foreach ($this->tasks as $i => $task) {
      if (isset($filter) && !($task == NestedArray::mergeDeep($task, $filter))) {
        continue;
      }
      $subject = $task['subject'];
      unset($task['subject']);
      $configs[$i] = $subject + $flow_keys + ['task' => $task];
    }
    return new FlowSubjectCollection(\Drupal::service('plugin.manager.flow.subject'), $configs);
  }

  /**
   * {@inheritdoc}
   */
  public function setTasks(array $tasks): FlowInterface {
    uasort($tasks, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    $this->tasks = array_values($tasks);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'task.plugins' => $this->getTasks(),
      'subject.plugins' => $this->getSubjects(),
      'qualifier.plugins' => $this->getQualifiers(),
      'qualifying.plugins' => $this->getQualifyingSubjects(),
    ];
  }

  /**
   * Returns the Flow configuration entity for the given parameters.
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
    $entity_type_manager = \Drupal::entityTypeManager();
    if (!($entity_type = $entity_type_manager->getDefinition($entity_type_id, FALSE))) {
      return NULL;
    }
    if (!($entity_type->entityClassImplements(ContentEntityInterface::class))) {
      return NULL;
    }
    if (!($entity_type->hasKey('uuid'))) {
      return NULL;
    }

    $storage = $entity_type_manager->getStorage('flow');
    $id = "$entity_type_id.$bundle.$task_mode";

    if (!($config = $storage->load($id))) {
      $config = $storage->create([
        'id' => $id,
        'status' => TRUE,
        'targetEntityType' => $entity_type_id,
        'targetBundle' => $bundle,
        'taskMode' => $task_mode,
        'tasks' => [],
        'custom' => [],
      ]);
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $etm = \Drupal::entityTypeManager();
    $entity_type = $etm->getDefinition($this->getTargetEntityTypeId());
    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      if ($bundle_config = $etm->getStorage($bundle_entity_type_id)->load($this->getTargetBundle())) {
        $this->dependencies[$bundle_config->getConfigDependencyKey()][] = $bundle_config->getConfigDependencyName();
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCustom(): bool {
    return !empty($this->custom);
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomFlow(): array {
    return \Drupal::entityTypeManager()->getStorage('flow')->loadByProperties([
      'targetEntityType' => $this->getTargetEntityTypeId(),
      'targetBundle' => $this->getTargetBundle(),
      'custom.baseMode' => $this->getTaskMode(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQualifiers(?array $filter = NULL): FlowQualifierCollection {
    $configs = [];
    $flow_keys = [
      'entity_type_id' => $this->getTargetEntityTypeId(),
      'bundle' => $this->getTargetBundle(),
      'task_mode' => $this->getTaskMode(),
    ];
    if ($this->isCustom()) {
      $flow_keys['custom_task_mode'] = $flow_keys['task_mode'];
      $flow_keys['task_mode'] = $this->custom['baseMode'];
    }
    if (!empty($this->custom['qualifiers'])) {
      foreach ($this->custom['qualifiers'] as $i => $qualifier) {
        if (isset($filter) && !($qualifier == NestedArray::mergeDeep($qualifier, $filter))) {
          continue;
        }
        $configs[$i] = $qualifier + $flow_keys;
      }
    }
    return new FlowQualifierCollection(\Drupal::service('plugin.manager.flow.qualifier'), $configs);
  }

  /**
   * {@inheritdoc}
   */
  public function getQualifyingSubjects(?array $filter = NULL): FlowSubjectCollection {
    $configs = [];
    $flow_keys = [
      'entity_type_id' => $this->getTargetEntityTypeId(),
      'bundle' => $this->getTargetBundle(),
      'task_mode' => $this->getTaskMode(),
    ];
    if ($this->isCustom()) {
      $flow_keys['custom_task_mode'] = $flow_keys['task_mode'];
      $flow_keys['task_mode'] = $this->custom['baseMode'];
    }
    if (!empty($this->custom['qualifiers'])) {
      foreach ($this->custom['qualifiers'] as $i => $qualifier) {
        if (isset($filter) && !($qualifier == NestedArray::mergeDeep($qualifier, $filter))) {
          continue;
        }
        $subject = $qualifier['subject'];
        unset($qualifier['subject']);
        $configs[$i] = $subject + $flow_keys + ['qualifier' => $qualifier];
      }
    }
    return new FlowSubjectCollection(\Drupal::service('plugin.manager.flow.subject'), $configs);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // Clear cached definitions of Flow plugins, as some of them may need
    // information from configured flow.
    \Drupal::service('plugin.manager.flow.subject')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.flow.task')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.flow.qualifier')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.action')->clearCachedDefinitions();
  }

}
