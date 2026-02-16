<?php

namespace Drupal\flow_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\flow\Entity\Flow;
use Drupal\flow\FlowCompatibility;
use Drupal\flow\FlowTaskMode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for managing flow configurations via UI.
 */
class FlowUiController extends ControllerBase {

  /**
   * Returns a form for a Flow configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   */
  public function flowForm(string $entity_type_id, string $bundle, string $flow_task_mode): array {
    $flow = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    return [
      'form' => $this->entityFormBuilder()->getForm($flow, 'default'),
    ];
  }

  /**
   * Returns a form for deleting a Flow configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   */
  public function flowDeleteForm(string $entity_type_id, string $bundle, string $flow_task_mode): array {
    $flow = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    if (!$flow->isNew() && !$flow->getTasks()->count()) {
      return [
        'form' => $this->entityFormBuilder()->getForm($flow, 'delete'),
      ];
    }

    throw new NotFoundHttpException();
  }

  /**
   * Returns a form for adding a new task to a Flow configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param string $flow_task_plugin
   *   The ID that identifies the type of task plugin to use.
   * @param string $flow_subject_plugin
   *   The ID that identifies the type of subject plugin to use.
   */
  public function taskAddForm(string $entity_type_id, string $bundle, string $flow_task_mode, string $flow_task_plugin, string $flow_subject_plugin): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    /** @var \Drupal\flow\Plugin\FlowTaskManager $task_manager */
    $task_manager = \Drupal::service('plugin.manager.flow.task');
    if (!$task_manager->hasDefinition($flow_task_plugin)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\flow\Plugin\FlowSubjectManager $subject_manager */
    $subject_manager = \Drupal::service('plugin.manager.flow.subject');
    if (!$subject_manager->hasDefinition($flow_subject_plugin)) {
      throw new NotFoundHttpException();
    }

    $flow_keys = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'task_mode' => $flow_task_mode,
    ];
    $task = $task_manager->createInstance($flow_task_plugin, $flow_keys);
    $subject = $subject_manager->createInstance($flow_subject_plugin, $flow_keys);

    // We don't allow incompatible components.
    if (!FlowCompatibility::validate($config, $task, $subject)) {
      throw new NotFoundHttpException();
    }

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskForm', $config, $task, $subject, $config->getTasks()->count()),
    ];
  }

  /**
   * Returns a form for editing a task plugin configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskEditForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for enabling a configured task plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskEnableForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskEnableForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for disabling a configured task plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskDisableForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskDisableForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for deleting a configured task plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskDeleteForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskDeleteForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for adding custom flow.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   */
  public function customAddForm(string $entity_type_id, string $bundle): array {
    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\CustomAddForm', $entity_type_id, $bundle),
    ];
  }

  /**
   * Access callback to support globally available task modes plus custom flow.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $flow_task_mode
   *   The task mode.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public static function customFlowAccess(AccountInterface $account, string $entity_type_id, string $flow_task_mode, RouteMatchInterface $route_match) {
    if ($bundle_entity_type_id = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getBundleEntityType()) {
      $bundle = $route_match->getRawParameter($bundle_entity_type_id);
    }
    if (!isset($bundle)) {
      $bundle = $route_match->getRawParameter('bundle') ?? $entity_type_id;
    }
    if (!($flow = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode))) {
      return AccessResult::forbidden(sprintf("The entity type %s does not support Flow configurations.", $entity_type_id));
    }
    // Grant access when the user has either common or entity type specific
    // permissions, and only grant access when flow is possible.
    // Flow is possible either when a general task mode exists (defined by the
    // globally available service), or when custom flow is configured.
    $task_modes = FlowTaskMode::service()->getAvailableTaskModes();
    return $flow->access($flow->isNew() ? 'create' : 'update', $account, TRUE)
      ->andIf(AccessResult::allowedIf(isset($task_modes[$flow_task_mode]) || !$flow->isNew()));
  }

  /**
   * Returns a form for adding a new qualifier to custom flow.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param string $flow_qualifier_plugin
   *   The ID that identifies the type of qualifier plugin to use.
   * @param string $flow_subject_plugin
   *   The ID that identifies the type of subject plugin to use.
   */
  public function qualifierAddForm(string $entity_type_id, string $bundle, string $flow_task_mode, string $flow_qualifier_plugin, string $flow_subject_plugin): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    if (!$config->isCustom()) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\flow\Plugin\FlowQualifierManager $qualifier_manager */
    $qualifier_manager = \Drupal::service('plugin.manager.flow.qualifier');
    if (!$qualifier_manager->hasDefinition($flow_qualifier_plugin)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\flow\Plugin\FlowSubjectManager $subject_manager */
    $subject_manager = \Drupal::service('plugin.manager.flow.subject');
    if (!$subject_manager->hasDefinition($flow_subject_plugin)) {
      throw new NotFoundHttpException();
    }

    $flow_keys = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'task_mode' => $flow_task_mode,
    ];
    $qualifier = $qualifier_manager->createInstance($flow_qualifier_plugin, $flow_keys);
    $subject = $subject_manager->createInstance($flow_subject_plugin, $flow_keys);

    // We don't allow incompatible components.
    if (!FlowCompatibility::validate($config, $qualifier, $subject)) {
      throw new NotFoundHttpException();
    }

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\QualifierForm', $config, $qualifier, $subject, $config->getQualifiers()->count()),
    ];
  }

  /**
   * Returns a form for editing a qualifier plugin configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_qualifier_index
   *   The index that is the position of the qualifier plugin in the config.
   */
  public function qualifierEditForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_qualifier_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $qualifiers = $config->getQualifiers();
    $subjects = $config->getQualifyingSubjects();
    if (!$qualifiers->has($flow_qualifier_index) || !$subjects->has($flow_qualifier_index)) {
      throw new NotFoundHttpException();
    }
    $qualifier = $qualifiers->get($flow_qualifier_index);
    $subject = $subjects->get($flow_qualifier_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\QualifierForm', $config, $qualifier, $subject, $flow_qualifier_index),
    ];
  }

  /**
   * Returns a form for deleting a configured qualifier plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the qualifier plugin in the config.
   */
  public function qualifierDeleteForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_qualifier_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $qualifiers = $config->getQualifiers();
    $subjects = $config->getQualifyingSubjects();
    if (!$qualifiers->has($flow_qualifier_index) || !$subjects->has($flow_qualifier_index)) {
      throw new NotFoundHttpException();
    }
    $qualifier = $qualifiers->get($flow_qualifier_index);
    $subject = $subjects->get($flow_qualifier_index);

    foreach ($config->getSubjects() as $subject) {
      if (FlowCompatibility::validate($config, $subject, $qualifier)) {
        throw new NotFoundHttpException();
      }
    }

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\QualifierDeleteForm', $config, $qualifier, $subject, $flow_qualifier_index),
    ];
  }

}
