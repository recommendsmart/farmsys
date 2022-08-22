<?php

namespace Drupal\flow_ui\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Entity\Flow;
use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\FlowCompatibility;
use Drupal\flow\FlowTaskMode;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Drupal\flow\Plugin\FlowQualifierInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring a Flow qualifier plugin.
 */
class QualifierForm implements FormInterface, ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The Flow config entity.
   *
   * @var \Drupal\flow\Entity\FlowInterface|null
   */
  protected ?FlowInterface $flow;

  /**
   * The Flow qualifier plugin.
   *
   * @var \Drupal\flow\Plugin\FlowQualifierInterface|null
   */
  protected ?FlowQualifierInterface $qualifier;

  /**
   * The Flow subject plugin.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectInterface|null
   */
  protected ?FlowSubjectInterface $subject;

  /**
   * The position of the qualifier within the list of the Flow config.
   *
   * @var int|null
   */
  protected ?int $qualifierIndex;

  /**
   * The target entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected EntityTypeInterface $targetEntityType;

  /**
   * This flag indicates whether a new qualifier has been saved.
   *
   * @var bool
   */
  protected bool $savedNewQualifier = FALSE;

  /**
   * The QualifierForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\flow\Entity\FlowInterface|null $flow
   *   The Flow config entity.
   * @param \Drupal\flow\Plugin\FlowQualifierInterface|null $qualifier
   *   The Flow qualifier plugin.
   * @param \Drupal\flow\Plugin\FlowSubjectInterface|null $subject
   *   The Flow subject plugin.
   * @param int|null $qualifier_index
   *   The position of the Flow qualifier within the list of the Flow config.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ?FlowInterface $flow = NULL, ?FlowQualifierInterface $qualifier = NULL, ?FlowSubjectInterface $subject = NULL, ?int $qualifier_index = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->flow = $flow;
    $this->qualifier = $qualifier;
    $this->subject = $subject;
    $this->qualifierIndex = $qualifier_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, ?FlowInterface $flow = NULL, ?FlowQualifierInterface $qualifier = NULL, ?FlowSubjectInterface $subject = NULL, ?int $qualifier_index = NULL) {
    $instance = new static($container->get('entity_type.manager'), $container->get('messenger'), $flow, $qualifier, $subject, $qualifier_index);
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flow_qualifier_plugin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlowInterface $flow = NULL, ?FlowQualifierInterface $qualifier = NULL, ?FlowSubjectInterface $subject = NULL, ?int $qualifier_index = NULL) {
    $form['#tree'] = TRUE;
    $form['#process'][] = '::processForm';
    $form['#after_build'][] = '::afterBuild';
    $this->initProperties($form, $form_state, $flow, $qualifier, $subject, $qualifier_index);
    $qualifier = $this->qualifier;
    $subject = $this->subject;
    $qualifier_definition = $qualifier->getPluginDefinition();
    $qualifier_config = $qualifier->getConfiguration();

    $weight = 0;
    $qualifier_is_new = !($this->qualifierIndex < $this->flow->getQualifiers()->count());

    $weight += 10;
    $subject_definition = $this->subject->getPluginDefinition();
    $form['subject'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@type: @name', [
        '@type' => $this->t('Subject'),
        '@name' => $subject_definition['label'],
      ]),
      '#weight' => $weight++,
    ];
    if ($subject instanceof PluginFormInterface) {
      $form['subject']['settings'] = [];
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $form['subject']['settings'] = $subject->buildConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    else {
      $form['subject']['no_settings'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This subject does not provide any settings.'),
      ];
    }

    $form['qualifier'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@type: @name', [
        '@type' => $this->t('Qualifier'),
        '@name' => $qualifier_definition['label'],
      ]),
      '#weight' => $weight++,
    ];
    if ($qualifier instanceof PluginFormInterface) {
      $form['qualifier']['settings'] = [];
      $qualifier_form_state = SubformState::createForSubform($form['qualifier']['settings'], $form, $form_state);
      $form['qualifier']['settings'] = $qualifier->buildConfigurationForm($form['qualifier']['settings'], $qualifier_form_state);
    }
    else {
      $form['qualifier']['no_settings'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This qualifier does not provide any settings.'),
      ];
    }

    $weight += 100;
    $form['actions']['#weight'] = $weight++;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save', '::redirectAfterSave'],
      '#weight' => 10,
    ];
    if (!$qualifier_is_new && !$this->flow->getTasks()->count()) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#access' => $this->flow->access('delete'),
        '#submit' => ['::delete'],
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#button_type' => 'danger',
        '#weight' => 20,
      ];
    }

    $form['config'] = ['#tree' => TRUE, '#weight' => $weight++];
    $form['config']['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $this->flow->getTargetEntityTypeId(),
    ];
    $form['config']['bundle'] = [
      '#type' => 'hidden',
      '#value' => $this->flow->getTargetBundle(),
    ];
    $form['config']['task_mode'] = [
      '#type' => 'hidden',
      '#value' => $this->flow->getTaskMode(),
    ];
    $form['config']['qualifier_plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $this->qualifier->getPluginId(),
    ];
    $form['config']['subject_plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $this->subject->getPluginId(),
    ];
    $form['config']['qualifier_index'] = [
      '#type' => 'hidden',
      '#value' => $this->qualifierIndex,
    ];

    return $form;
  }

  /**
   * Process callback.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $this->flow = $form_state->get('flow');
    $this->qualifier = $form_state->get('qualifier');
    $this->subject = $form_state->get('subject');
    $this->qualifierIndex = $form_state->get('qualifier_index');
    return $element;
  }

  /**
   * After build callback.
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    $subject = $this->subject;
    $qualifier = $this->qualifier;

    // Prevent Inline Entity Form from saving nested data.
    // @todo Find a better way to prevent submit handlers from saving data.
    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#ief_submit_trigger']) && !empty($triggering_element['#submit']) && is_array($triggering_element['#submit'])) {
        foreach ($triggering_element['#submit'] as $i => $submit_handler) {
          if (is_array($submit_handler) && (reset($submit_handler) === 'Drupal\\inline_entity_form\\ElementSubmit') && end($submit_handler) === 'trigger') {
            unset($triggering_element['#submit'][$i]);
          }
        }
      }
    }

    if ($form_state->hasValue(['qualifier', 'settings']) && $qualifier instanceof PluginFormInterface) {
      $values = $form_state->getValue(['qualifier', 'settings']);
      array_walk_recursive($values, function (&$value) {
        if ($value === '_none') {
          $value = NULL;
        }
      });
      $form_state->setValue(['qualifier', 'settings'], $values);
      $qualifier_form_state = SubformState::createForSubform($form['qualifier']['settings'], $form, $form_state);
      $qualifier->submitConfigurationForm($form['qualifier']['settings'], $qualifier_form_state);
    }
    if ($form_state->hasValue(['subject', 'settings']) && $subject instanceof PluginFormInterface) {
      $values = $form_state->getValue(['subject', 'settings']);
      array_walk_recursive($values, function (&$value) {
        if ($value === '_none') {
          $value = NULL;
        }
      });
      $form_state->setValue(['subject', 'settings'], $values);
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $subject->submitConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      $form_state->setError($form, $this->t('You don\'t have permission to manage this configuration.'));
    }

    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#parents']) && reset($triggering_element['#parents']) !== 'actions') {
        return;
      }
    }

    $subject = $this->subject;
    $qualifier = $this->qualifier;
    if ($subject instanceof PluginFormInterface) {
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $subject->validateConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    if ($qualifier instanceof PluginFormInterface) {
      $qualifier_form_state = SubformState::createForSubform($form['qualifier']['settings'], $form, $form_state);
      $qualifier->validateConfigurationForm($form['qualifier']['settings'], $qualifier_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      return;
    }
    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#parents']) && reset($triggering_element['#parents']) !== 'actions') {
        return;
      }
    }

    $subject = $this->subject;
    $qualifier = $this->qualifier;
    if (isset($form['subject']['settings']) && $subject instanceof PluginFormInterface) {
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $subject->submitConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    if (isset($form['qualifier']['settings']) && $qualifier instanceof PluginFormInterface) {
      $qualifier_form_state = SubformState::createForSubform($form['qualifier']['settings'], $form, $form_state);
      $qualifier->submitConfigurationForm($form['qualifier']['settings'], $qualifier_form_state);
    }
  }

  /**
   * Redirect after save submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function redirectAfterSave(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      return;
    }

    $config = $this->flow;
    $qualifier_modes = FlowTaskMode::service()->getAvailableTaskModes();

    $t_args = [
      '%label' => $config->isCustom() ? $config->get('custom')['label'] : $qualifier_modes[$config->getTaskMode()],
      '%type' =>$this->entityTypeManager->getDefinition($config->getTargetEntityTypeId())->getLabel(),
    ];
    $message = $config->isCustom() ? $this->t('The custom %label flow configuration for %type has been saved.', $t_args)
      : $this->t('The %label flow configuration for %type has been saved.', $t_args);

    $this->messenger->addStatus($message);

    $bundle_type_id = $this->targetEntityType->getBundleEntityType() ?: 'bundle';

    $form_state->setRedirect("entity.flow.{$this->targetEntityType->id()}.task_mode", [
      'entity_type_id' => $this->targetEntityType->id(),
      $bundle_type_id => $this->flow->getTargetBundle(),
      'flow_task_mode' => $this->flow->getTaskMode(),
    ]);
  }

  /**
   * Save submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function save(array &$form, FormStateInterface $form_state): void {
    if (!$this->flow->access('update')) {
      return;
    }
    $flow = $this->flow;
    $qualifier = $this->qualifier;
    $subject = $this->subject;
    $custom = $flow->get('custom');
    $qualifiers_array = $custom['qualifiers'] ?? [];
    $qualifier_is_new = !($this->qualifierIndex < $this->flow->getQualifiers()->count());
    $qualifiers_array[$this->qualifierIndex] = [
      'id' => $qualifier->getPluginId(),
      'type' => $qualifier->getBaseId(),
      'weight' => $this->qualifierIndex,
      'active' => TRUE,
      'subject' => [
        'id' => $subject->getPluginId(),
        'type' => $subject->getBaseId(),
        'settings' => $subject->getSettings(),
        'third_party_settings' => [],
      ],
      'settings' => $qualifier->getSettings(),
      'third_party_settings' => [],
    ];
    foreach ($qualifier->getThirdPartyProviders() as $provider) {
      $qualifiers_array[$this->qualifierIndex]['third_party_settings'][$provider] = $qualifier->getThirdPartySettings($provider);
    }
    foreach ($subject->getThirdPartyProviders() as $provider) {
      $qualifiers_array[$this->qualifierIndex]['subject']['third_party_settings'][$provider] = $subject->getThirdPartySettings($provider);
    }
    $this->filterRuntimeSettings($qualifiers_array);
    $custom['qualifiers'] = $qualifiers_array;
    $flow->set('custom', $custom);
    $flow->save();
    $this->savedNewQualifier = $qualifier_is_new;
  }

  /**
   * Delete submission callback that redirects to the qualifier delete form.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function delete(array &$form, FormStateInterface $form_state): void {
    $flow = $this->flow;
    $target_type = $this->targetEntityType;
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';
    $form_state->setRedirect("flow.qualifier.{$target_type->id()}.delete", [
      'entity_type_id' => $target_type->id(),
      $bundle_type_id => $flow->getTargetBundle(),
      'flow_task_mode' => $flow->getTaskMode(),
      'flow_qualifier_index' => $this->qualifierIndex,
    ]);
  }

  /**
   * Initializes the form object properties.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\flow\Entity\FlowInterface|null $flow
   *   The Flow config entity.
   * @param \Drupal\flow\Plugin\FlowQualifierInterface|null $qualifier
   *   The Flow qualifier plugin.
   * @param \Drupal\flow\Plugin\FlowSubjectInterface|null $subject
   *   The Flow subject plugin.
   * @param int|null $qualifier_index
   *   The position of the Flow qualifier within the list of the Flow config.
   */
  protected function initProperties(array &$form, FormStateInterface $form_state, ?FlowInterface $flow = NULL, ?FlowQualifierInterface $qualifier = NULL, ?FlowSubjectInterface $subject = NULL, ?int $qualifier_index = NULL): void {
    if ($form_state->has('flow')) {
      $this->flow = $form_state->get('flow');
      $this->qualifier = $form_state->get('qualifier');
      $this->subject = $form_state->get('subject');
      $this->qualifierIndex = $form_state->get('qualifier_index');
    }
    elseif (isset($flow, $qualifier, $subject, $qualifier_index)) {
      $this->flow = $flow;
      $this->qualifier = $qualifier;
      $this->subject = $subject;
      $this->qualifierIndex = $qualifier_index;
    }
    elseif ($config_values = $form_state->getValue('config')) {
      $config_values = $form_state->getValue('config');
      $this->flow = Flow::getFlow($config_values['entity_type'], $config_values['bundle'], $config_values['task_mode']);
      $qualifiers = $this->flow->getQualifiers();
      $subjects = $this->flow->getSubjects();
      if ($qualifiers->has($config_values['qualifier_index'])) {
        $this->qualifier = $qualifiers->get($config_values['qualifier_index']);
        $this->subject = $subjects->get($config_values['qualifier_index']);
      }
      else {
        $flow_keys = [
          'entity_type_id' => $this->flow->getTargetEntityTypeId(),
          'bundle' => $this->flow->getTargetBundle(),
          'task_mode' => $this->flow->getTaskMode(),
        ];
        /** @var \Drupal\flow\Plugin\FlowQualifierManager $qualifier_manager */
        $qualifier_manager = \Drupal::service('plugin.manager.flow.qualifier');
        $this->qualifier = $qualifier_manager->createInstance($config_values['qualifier_plugin_id'], $flow_keys);
        /** @var \Drupal\flow\Plugin\FlowSubjectManager $subject_manager */
        $subject_manager = \Drupal::service('plugin.manager.flow.subject');
        $this->subject = $subject_manager->createInstance($config_values['subject_plugin_id'], $flow_keys);
      }
      $this->qualifierIndex = $config_values['qualifier_index'];
    }
    else {
      throw new \InvalidArgumentException("Form build error: The Flow qualifier plugin form cannot be built without any information about according configuration.");
    }
    if (!FlowCompatibility::validate($this->flow, $this->qualifier, $this->subject)) {
      throw new \InvalidArgumentException('Form build error: The Flow qualifier form cannot not be built with incompatible components.');
    }
    $this->targetEntityType = $this->entityTypeManager->getDefinition($this->flow->getTargetEntityTypeId());
    $form_state->set('flow', $this->flow);
    $form_state->set('qualifier', $this->qualifier);
    $form_state->set('subject', $this->subject);
    $form_state->set('qualifier_index', $this->qualifierIndex);
  }

  /**
   * Filters runtime settings from the given array.
   *
   * @param array &$array
   *   The array.
   */
  protected function filterRuntimeSettings(&$array) {
    foreach ($array as $k => $v) {
      if ($k === 'target_for' || $k === 'subject_for') {
        unset($array[$k]);
      }
      elseif (is_array($v)) {
        $this->filterRuntimeSettings($array[$k]);
      }
    }
  }

}
