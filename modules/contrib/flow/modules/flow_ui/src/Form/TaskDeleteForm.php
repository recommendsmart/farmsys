<?php

namespace Drupal\flow_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\Plugin\FlowTaskInterface;
use Drupal\flow\Plugin\FlowSubjectInterface;

/**
 * Form for deleting a configured task from a Flow configuration.
 */
class TaskDeleteForm extends TaskForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlowInterface $flow = NULL, ?FlowTaskInterface $task = NULL, ?FlowSubjectInterface $subject = NULL, ?int $task_index = NULL) {
    $form = parent::buildForm($form, $form_state, $flow, $task, $subject, $task_index);

    $form['#attributes']['class'][] = 'confirmation';
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('You are about to delete the task <em>@task</em> on <em>@subject</em> from the Flow configuration of <em>@content</em>.', [
        '@index' => $this->taskIndex,
        '@task' => $this->task->getPluginDefinition()['label'],
        '@subject' => $this->subject->getPluginDefinition()['label'],
        '@content' => $this->t('@bundle @type', [
          '@bundle' => $this->flow->getTargetBundle(),
          '@type' => $this->targetEntityType->getLabel(),
        ]),
      ]) . '</h2>',
    ];
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This action cannot be undone.') . '</p>',
    ];

    unset($form['execution'], $form['subject'], $form['task'], $form['actions']['delete']);

    $form['actions']['submit']['#value'] = $this->t('Confirm');
    $form['actions']['submit']['#submit'] = ['::delete', '::redirectAfterSave'];
    $form['actions']['submit']['#button_type'] = 'danger';
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
  public function delete(array &$form, FormStateInterface $form_state): void {
    if (!$this->flow->access('delete')) {
      return;
    }
    $flow = $this->flow;
    $tasks_array = $flow->get('tasks');
    unset($tasks_array[$this->taskIndex]);
    if (!empty($tasks_array) || $flow->isCustom()) {
      $flow->setTasks($tasks_array);
      $flow->save();
    }
    else {
      $flow->delete();
    }
    $this->messenger->addStatus($this->t('The task has been successfully removed.'));
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
    $flow = $this->flow;
    $target_type = $this->targetEntityType;
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';
    $form_state->setRedirect("entity.flow.{$target_type->id()}.task_mode", [
      'entity_type_id' => $target_type->id(),
      $bundle_type_id => $flow->getTargetBundle(),
      'flow_task_mode' => $flow->getTaskMode(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('delete')) {
      $form_state->setError($form, $this->t('You don\'t have permission to manage this configuration.'));
    }
  }

}
