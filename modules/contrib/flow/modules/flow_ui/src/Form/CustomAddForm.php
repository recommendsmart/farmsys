<?php

namespace Drupal\flow_ui\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Entity\Flow;
use Drupal\flow\FlowTaskMode;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding custom flow.
 */
class CustomAddForm implements FormInterface, ContainerInjectionInterface {

  use EntityTypeManagerTrait;
  use StringTranslationTrait;

  /**
   * The entity type ID.
   *
   * @var string|null
   */
  protected ?string $entityTypeId = NULL;

  /**
   * The entity bundle.
   *
   * @var string|null
   */
  protected ?string $entityBundle = NULL;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flow_custom_add';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setEntityTypeManager($container->get(static::$entityTypeManagerServiceName));
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    if (isset($entity_type_id, $bundle)) {
      $this->entityTypeId = $entity_type_id;
      $this->entityBundle = $bundle;
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Human-readable label'),
      '#description' => $this->t('The label will show up as additional tab in the <em>Manage flow</em> section.'),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#weight' => 10,
    ];
    $form['task_mode'] = [
      '#type' => 'machine_name',
      '#maxlength' => 32,
      '#title' => $this->t('Machine name of task mode'),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#weight' => 20,
      '#required' => TRUE,
    ];
    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $this->entityTypeId,
    ];
    $form['bundle'] = [
      '#type' => 'hidden',
      '#value' => $this->entityBundle,
    ];

    $form['actions']['#weight'] = 100;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save and configure'),
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $this->entityTypeId ?? $form_state->getValue('entity_type');
    $entity_bundle = $this->entityBundle ?? $form_state->getValue('bundle');
    $task_mode = $form_state->getValue('task_mode');
    $label = $form_state->getValue('label');
    Flow::getFlow($entity_type_id, $entity_bundle, $task_mode)
      ->set('custom', [
        'label' => $label,
        'baseMode' => 'save',
        'qualifiers' => [],
      ])
      ->calculateDependencies()
      ->save();

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $t_args = [
      '%label' => $label,
      '%type' => $entity_type->getLabel(),
    ];
    $this->messenger->addStatus($this->t('The custom %label flow configuration for %type has been saved.', $t_args));
    $bundle_type_id = $entity_type->getBundleEntityType() ?: 'bundle';
    $form_state->setRedirect("entity.flow.{$entity_type_id}.task_mode", [
      'entity_type_id' => $entity_type_id,
      $bundle_type_id => $entity_bundle,
      'flow_task_mode' => $task_mode,
    ]);
  }

  /**
   * Exists callback for the task mode lookup.
   *
   * @param string $name
   *   The name of the task mode to check for.
   *
   * @return bool
   *   Returns TRUE if it exists, FALSE otherwise.
   */
  public function exists($name): bool {
    return isset(FlowTaskMode::service()->getAvailableTaskModes()[$name]) || !(Flow::getFlow($this->entityTypeId, $this->entityBundle, $name)->isNew());
  }

  /**
   * Sets the messenger.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function setMessenger(MessengerInterface $messenger): void {
    $this->messenger = $messenger;
  }

}
