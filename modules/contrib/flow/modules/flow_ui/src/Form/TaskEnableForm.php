<?php

namespace Drupal\flow_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\Plugin\FlowTaskInterface;
use Drupal\flow\Plugin\FlowSubjectInterface;

/**
 * Form for setting a configured task to be active.
 */
class TaskEnableForm extends TaskForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlowInterface $flow = NULL, ?FlowTaskInterface $task = NULL, ?FlowSubjectInterface $subject = NULL, ?int $task_index = NULL) {
    $form = parent::buildForm($form, $form_state, $flow, $task, $subject, $task_index);

    $form['#attributes']['class'][] = 'confirmation';
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('You are about to enable the task <em>@task</em> on <em>@subject</em> from the Flow configuration of <em>@content</em>.', [
        '@index' => $this->taskIndex,
        '@task' => $this->task->getPluginDefinition()['label'],
        '@subject' => $this->subject->getPluginDefinition()['label'],
        '@content' => $this->t('@bundle @type', [
          '@bundle' => $this->flow->getTargetBundle(),
          '@type' => $this->targetEntityType->getLabel(),
        ]),
      ]) . '</h2>',
    ];

    unset($form['subject'], $form['task'], $form['actions']['delete']);

    $form['actions']['submit']['#value'] = $this->t('Confirm');
    $form['actions']['submit']['#submit'] = ['::enable', '::redirectAfterSave'];
    $weight = $form['actions']['submit']['#weight'];
    $weight += 10;
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => $weight++,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(array &$form, FormStateInterface $form_state): void {
    if (!$this->flow->access('update')) {
      return;
    }
    $flow = $this->flow;
    $tasks_array = $flow->get('tasks');
    $tasks_array[$this->taskIndex]['active'] = TRUE;
    $tasks_array[$this->taskIndex]['execution'] = $form_state->getValue('execution', ['start' => 'now']);
    $flow->setTasks($tasks_array);
    $flow->save();
    $this->messenger->addStatus($this->t('The task is now active.'));
  }

  /**
   * Cancel submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $target_type = $this->targetEntityType;
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';
    $form_state->setRedirect("entity.flow.{$this->targetEntityType->id()}.task_mode", [
      'entity_type_id' => $this->targetEntityType->id(),
      $bundle_type_id => $this->flow->getTargetBundle(),
      'flow_task_mode' => $this->flow->getTaskMode(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      $form_state->setError($form, $this->t('You don\'t have permission to manage this configuration.'));
    }
  }

}
