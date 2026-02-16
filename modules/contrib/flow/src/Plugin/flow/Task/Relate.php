<?php

namespace Drupal\flow\Plugin\flow\Task;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\flow\Flow;
use Drupal\flow\FlowCompatibility;
use Drupal\flow\Helpers\EntityFieldManagerTrait;
use Drupal\flow\Helpers\EntityTypeBundleInfoTrait;
use Drupal\flow\Helpers\EntityTypeManagerTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Helpers\SingleTaskOperationTrait;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Drupal\flow\Plugin\FlowSubjectManager;
use Drupal\flow\Plugin\FlowTaskBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Task for relating content.
 *
 * @FlowTask(
 *   id = "relate",
 *   label = @Translation("Relate with content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Task\RelateDeriver"
 * )
 */
class Relate extends FlowTaskBase implements PluginFormInterface {

  use EntityFieldManagerTrait;
  use EntityTypeBundleInfoTrait;
  use EntityTypeManagerTrait;
  use ModuleHandlerTrait;
  use SingleTaskOperationTrait;
  use StringTranslationTrait;

  /**
   * The subject plugin instance used as the target of the reference.
   *
   * Can be NULL if the subject plugin has not yet been defined and configured.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectInterface|null
   */
  protected ?FlowSubjectInterface $target = NULL;

  /**
   * The Flow subject manager for retreiving other plugin definitions.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectManager
   */
  protected FlowSubjectManager $subjectManager;

  /**
   * Some internal stateful flags when working on large lists.
   *
   * @var array
   */
  protected array $flags = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    [, $target] = explode('::', $plugin_id);
    [$target_entity_type_id, $target_bundle] = explode('.', $target);

    /** @var \Drupal\flow\Plugin\flow\Task\Relate $instance */
    $instance = parent::create($container, ['target_entity_type_id' => $target_entity_type_id, 'target_bundle' => $target_bundle] + $configuration, $plugin_id, $plugin_definition);
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setEntityFieldManager($container->get(self::$entityFieldManagerServiceName));
    $instance->setEntityTypeBundleInfo($container->get(self::$entityTypeBundleInfoServiceName));
    $instance->setEntityTypeManager($container->get(self::$entityTypeManagerServiceName));
    $instance->setSubjectManager($container->get('plugin.manager.flow.subject'));
    $instance->initTargetSubject();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->target = NULL;
    $this->initTargetSubject();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings): void {
    parent::setSettings($settings);
    $this->target = NULL;
    $this->initTargetSubject();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#task'] = $this;

    $source_definition = $this->getPluginDefinition();
    $source_entity_type_id = $source_definition['entity_type'];
    $source_bundle = $source_definition['bundle'];
    $source_entity_type = $this->entityTypeManager->getDefinition($source_entity_type_id);
    $source_bundle_info = $this->entityTypeBundleInfo->getBundleInfo($source_entity_type_id)[$source_bundle];
    $bundle_label = $source_bundle_info['label'] instanceof TranslatableMarkup ? $source_bundle_info['label'] : new TranslatableMarkup($source_bundle_info['label']);
    $source_label = $source_entity_type->getBundleEntityType() ? $this->t('@bundle item (@type)', [
      '@bundle' => $bundle_label,
      '@type' => $source_entity_type->getLabel(),
    ]) : $this->t('@type item', ['@type' => $bundle_label]);

    $target_entity_type_id = $this->configuration['target_entity_type_id'];
    $target_bundle = $this->configuration['target_bundle'];
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $target_bundle_info = $this->entityTypeBundleInfo->getBundleInfo($target_entity_type_id)[$target_bundle];
    $bundle_label = $target_bundle_info['label'] instanceof TranslatableMarkup ? $target_bundle_info['label'] : new TranslatableMarkup($target_bundle_info['label']);
    $target_label = $target_entity_type->getBundleEntityType() ? $this->t('@bundle item (@type)', [
      '@bundle' => $bundle_label,
      '@type' => $target_entity_type->getLabel(),
    ]) : $this->t('@type item', ['@type' => $bundle_label]);

    $select_widget = $this->moduleHandler->moduleExists('select2') ? 'select2' : 'select';
    $weight = 0;

    $weight += 10;
    $field_name_options = [
      '_none' => $this->t('- Select -'),
    ];
    foreach ($this->entityFieldManager->getFieldDefinitions($source_entity_type_id, $source_bundle) as $field_definition) {
      $field_storage_definition = $field_definition->getFieldStorageDefinition();
      if (!(strpos($field_storage_definition->getType(), 'entity_reference') === 0)) {
        continue;
      };
      $field_target_type = $field_storage_definition->getSetting('target_type');
      if ($field_target_type !== $target_entity_type_id) {
        continue;
      }
      $handler_settings = $field_definition->getConfig($source_bundle)->getSetting('handler_settings');
      if (!empty($handler_settings['target_bundles']) && !in_array($target_bundle, $handler_settings['target_bundles'])) {
        continue;
      }
      $field_name_options[$field_storage_definition->getName()] = $this->t('@source: @field', [
        '@source' => $source_label,
        '@field' => $field_definition->getLabel(),
      ]);
    }
    $form['field_name'] = [
      '#type' => $select_widget,
      '#title' => $this->t('Reference field'),
      '#description' => $this->t('Select the field of the subject that will hold the reference to the @target.', [
        '@target' => $target_label,
      ]),
      '#options' => $field_name_options,
      '#default_value' => $this->settings['field_name'] ?? '_none',
      '#required' => TRUE,
      '#empty_value' => '_none',
      '#weight' => $weight++,
    ];

    $weight += 10;
    $reference_method_options = [
      '_none' => $this->t('- Select -'),
      'append:not_full' => $this->t('Append when list is not full yet'),
      'append:drop_first' => $this->t('Append and drop first item on a full list'),
      'append:drop_last' => $this->t('Append and drop last item on a full list'),
      'prepend:not_full' => $this->t('Prepend when list is not full yet'),
      'prepend:drop_first' => $this->t('Prepend and drop first item on a full list'),
      'prepend:drop_last' => $this->t('Prepend and drop last item on a full list'),
      'set:not_set' => $this->t('Set when no other reference was set before'),
      'set:clear' => $this->t('Set and clear any previously set reference'),
      'remove' => $this->t('Remove the reference instead of adding it'),
    ];
    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference method'),
      '#description' => $this->t('Select how the relationship will be defined, using the above selected reference field.'),
      '#options' => $reference_method_options,
      '#default_value' => $this->settings['method'] ?? '_none',
      '#required' => TRUE,
      '#empty_value' => '_none',
      '#weight' => $weight++,
    ];

    $field_name_options = [];
    foreach ($this->entityFieldManager->getFieldDefinitions($target_entity_type_id, $target_bundle) as $field_definition) {
      $field_storage_definition = $field_definition->getFieldStorageDefinition();
      if (!(strpos($field_storage_definition->getType(), 'entity_reference') === 0)) {
        continue;
      };
      $field_target_type = $field_storage_definition->getSetting('target_type');
      if ($field_target_type !== $source_entity_type_id) {
        continue;
      }
      $handler_settings = $field_definition->getConfig($source_bundle)->getSetting('handler_settings');
      if (!empty($handler_settings['target_bundles']) && !in_array($source_bundle, $handler_settings['target_bundles'])) {
        continue;
      }
      $field_name_options[$field_storage_definition->getName()] = $this->t('@source: @field', [
        '@source' => $target_label,
        '@field' => $field_definition->getLabel(),
      ]);
    }
    if ($field_name_options) {
      $weight += 10;
      $field_name_options = ['_none' => $this->t('- None -')] + $field_name_options;
      $reverse_wrapper_id = Html::getUniqueId('flow-reverse-reference');
      $form['reverse'] = [
        '#type' => 'container',
        '#attributes' => ['id' => $reverse_wrapper_id],
        '#weight' => $weight++,
      ];
      $form['reverse']['field_name'] = [
        '#type' => $select_widget,
        '#title' => $this->t('Reverse reference field'),
        '#description' => $this->t('Optionally you may define the reverse relationship, by selecting the field of the target that will hold the reference back to the @source.', [
          '@source' => $source_label,
        ]),
        '#options' => $field_name_options,
        '#default_value' => $this->settings['reverse']['field_name'] ?? '_none',
        '#required' => FALSE,
        '#empty_value' => '_none',
        '#weight' => 10,
        '#ajax' => [
          'callback' => [$this, 'selectAjax'],
          'wrapper' => $reverse_wrapper_id,
        ],
        '#submit' => [[$this, 'selectSubmitAjax']],
        '#executes_submit_callback' => TRUE,
        '#limit_validation_errors' => [],
      ];
      $weight += 10;
      if (!empty($this->settings['reverse']['field_name'])) {
        $form['reverse']['method'] = [
          '#type' => 'select',
          '#title' => $this->t('Reverse reference method'),
          '#description' => $this->t('Select how the reverse relationship will be defined, using the above selected reverse reference field.'),
          '#options' => $reference_method_options,
          '#default_value' => $this->settings['reverse']['method'] ?? '_none',
          '#required' => TRUE,
          '#empty_value' => '_none',
          '#weight' => 20,
        ];
      }
    }

    $weight += 10;
    $target_wrapper_id = Html::getUniqueId('flow-target-subject');
    $form['target'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $target_wrapper_id],
      '#weight' => $weight++,
    ];
    $flow = Flow::getFlow($this->configuration['entity_type_id'], $this->configuration['bundle'], $this->configuration['task_mode']);
    $target_plugin_options = [
      '_none' => $this->t('- Select -'),
    ];
    foreach ($this->subjectManager->getDefinitions() as $target_plugin_id => $target_plugin_definition) {
      $subject = $this->subjectManager->createInstance($target_plugin_id, array_intersect_key($this->configuration, [
        'entity_type_id' => TRUE,
        'bundle' => TRUE,
        'task_mode' => TRUE,
      ]));
      if ($target_plugin_definition['entity_type'] !== $target_entity_type_id) {
        continue;
      }
      if ($target_plugin_definition['bundle'] !== $target_bundle) {
        continue;
      }
      if (!FlowCompatibility::validate($flow, $subject)) {
        continue;
      }
      $target_plugin_options[$target_plugin_id] = $target_plugin_definition['label'];
    }
    $form['target']['id'] = [
      '#type' => $select_widget,
      '#title' => $this->t('Reference target'),
      '#description' => $this->t('Select how the @target will be identified.', [
        '@target' => $target_label,
      ]),
      '#options' => $target_plugin_options,
      '#default_value' => $this->settings['target']['id'] ?? '_none',
      '#required' => TRUE,
      '#empty_value' => '_none',
      '#weight' => 10,
      '#ajax' => [
        'callback' => [$this, 'selectAjax'],
        'wrapper' => $target_wrapper_id,
      ],
      '#submit' => [[$this, 'selectSubmitAjax']],
      '#executes_submit_callback' => TRUE,
      '#limit_validation_errors' => [],
    ];
    if ($target = $this->getTargetSubject()) {
      if ($target instanceof PluginFormInterface) {
        $form['target']['settings'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('@type: @name', [
            '@type' => $this->t('Target'),
            '@name' => $target_label,
          ]),
          '#weight' => 20,
        ];
        $target_form_state = SubformState::createForSubform($form['target']['settings'], $form, $form_state);
        $form['target']['settings'] += $target->buildConfigurationForm($form['target']['settings'], $target_form_state);
        if (isset($form['target']['settings']['fallback']['#title'])) {
          $form['target']['settings']['fallback']['#title'] = $this->t('When the target could not be loaded');
        }
      }
      else {
        $form['target']['no_settings'] = [
          '#type' => 'markup',
          '#markup' => $this->t('This target does not provide any settings.'),
        ];
      }
    }

    return $form;
  }

  /**
   * Ajax submit callback for select widgets.
   *
   * @param array &$form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   */
  public function selectSubmitAjax(array &$form, FormStateInterface $form_state): void {
    $button = $form_state->getTriggeringElement();
    $button_parents = $button['#array_parents'];
    while ($element = &NestedArray::getValue($form, $button_parents)) {
      foreach (Element::children($element) as $child) {
        if (isset($element[$child]['#value'])) {
          $value = $element[$child]['#value'] === '_none' ? NULL : $element[$child]['#value'];
          $form_state->setValueForElement($element[$child], $value);
        }
      }
      if (isset($element['#task']) && $element['#task'] === $this) {
        break;
      }
      array_pop($button_parents);
    }
    $subform_state = SubformState::createForSubform($element, $form, $form_state);
    $this->settings['field_name'] = $subform_state->getValue(['field_name']);
    $this->settings['method'] = $subform_state->getValue(['method']);
    $this->settings['reverse'] = $subform_state->getValue(['reverse']);
    $this->settings['target']['id'] = $subform_state->getValue(['target', 'id']);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for setting up the identifier of the target.
   *
   * @param array &$form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The part of the form that got refreshed via Ajax.
   */
  public function selectAjax(array &$form, FormStateInterface $form_state): array {
    $button = &$form_state->getTriggeringElement();
    $element = &NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $target = $this->getTargetSubject();
    if (isset($form['target']['settings']) && $target instanceof PluginFormInterface) {
      $target_form_state = SubformState::createForSubform($form['target']['settings'], $form, $form_state);
      $target->validateConfigurationForm($form['target']['settings'], $target_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->settings['field_name'] = $form_state->getValue(['field_name']);
    $this->settings['method'] = $form_state->getValue(['method']);
    $this->settings['reverse'] = $form_state->getValue(['reverse']);
    $this->settings['target']['id'] = $form_state->getValue(['target', 'id']);

    $this->target = NULL;
    $this->initTargetSubject();
    if ($target = $this->getTargetSubject()) {
      if (isset($form['target']['settings']) && $form_state->hasValue(['target', 'settings']) && $target instanceof PluginFormInterface) {
        $target_form_state = SubformState::createForSubform($form['target']['settings'], $form, $form_state);
        $target->submitConfigurationForm($form['target']['settings'], $target_form_state);
      }
      $this->settings['target'] = [
        'id' => $target->getPluginId(),
        'type' => $target->getBaseId(),
        'settings' => $target->getSettings(),
        'third_party_settings' => [],
      ];
      foreach ($target->getThirdPartyProviders() as $provider) {
        $this->settings['target']['third_party_settings'][$provider] = $target->getThirdPartySettings($provider);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doOperate(ContentEntityInterface $entity): void {
    $field_name = $this->settings['field_name'];
    if (!$entity->hasField($field_name)) {
      return;
    }
    $item_list = $entity->get($field_name);
    if (!($item_list instanceof EntityReferenceFieldItemListInterface)) {
      return;
    }
    $method_settings = explode(':', $this->settings['method']);
    $reverse_field_name = $this->settings['reverse']['field_name'] ?? NULL;
    $reverse_method_settings = !empty($this->settings['reverse']['method']) ? explode(':', $this->settings['reverse']['method']) : NULL;

    if (empty($this->flags)) {
      $this->flags = [
        'count' => 0,
        'cardinality' => $item_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality(),
      ];
    }
    $cardinality = $this->flags['cardinality'];
    $unlimited = FieldStorageConfigInterface::CARDINALITY_UNLIMITED;

    foreach ($this->getTargetSubject()->getSubjectItems() as $target) {
      if (($cardinality === $unlimited) || ($this->flags['count'] < $cardinality)) {
        $this->relate($item_list, $target, $method_settings, $this->flags);
        $this->flags['count']++;
      }
      else {
        break;
      }

      if (isset($reverse_field_name) && $target->hasField($reverse_field_name)) {
        $reverse_item_list = $target->get($reverse_field_name);
        if ($reverse_item_list instanceof EntityReferenceFieldItemListInterface) {
          $target_flags = [
            'cardinality' => $reverse_item_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality(),
          ];
          $this->relate($reverse_item_list, $entity, $reverse_method_settings, $target_flags);
        }
      }
    }

    $this->flags = [];
  }

  /**
   * Relates content using its item list with a specific target.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $item_list
   *   The field item list holding current entity references.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target
   *   The target to relate with.
   * @param array &$method_settings
   *   The settings that specify the reference method.
   * @param array &$flags
   *   Stateful flags that may be reused on multiple relate calls. Must be
   *   initialized with the configured field cardinality of the item list.
   */
  public function relate(EntityReferenceFieldItemListInterface $item_list, ContentEntityInterface $target, array &$method_settings, array &$flags): void {
    if (!isset($flags['cardinality'])) {
      throw new \InvalidArgumentException("The flags argument was not properly initialized.");
    }

    $cardinality = $flags['cardinality'];
    $unlimited = FieldStorageConfigInterface::CARDINALITY_UNLIMITED;
    $referenced_entities = $item_list->referencedEntities();

    $is_full = !($cardinality === $unlimited) && !(count($referenced_entities) < $cardinality);
    $is_referenced = (bool) (static::getReferenceIndex($referenced_entities, $target) !== FALSE);
    $can_add = FALSE;
    $entity_needs_save = FALSE;
    foreach ($method_settings as $method_setting) {
      switch ($method_setting) {

        case 'drop_first':
          if (!$is_referenced) {
            while ($is_full) {
              array_shift($referenced_entities);
              $entity_needs_save = TRUE;
              $is_full = !($cardinality === $unlimited) && !(count($referenced_entities) < $cardinality);
            }
            $can_add = TRUE;
          }
          break;

        case 'drop_last':
          if (!$is_referenced) {
            while ($is_full) {
              array_pop($referenced_entities);
              $entity_needs_save = TRUE;
              $is_full = !($cardinality === $unlimited) && !(count($referenced_entities) < $cardinality);
            }
            $can_add = TRUE;
          }
          break;

        case 'not_full':
          $can_add = !$is_referenced && !$is_full;
          break;

        case 'not_set':
          if (!$is_referenced) {
            if (empty($flags['not_set'])) {
              $flags['not_set'] = TRUE;
              $flags['can_add'] = empty($referenced_entities);
            }
            $can_add = !empty($flags['can_add']);
          }
          break;

        case 'clear':
          if (!$is_referenced) {
            if (empty($flags['cleared'])) {
              if (!empty($referenced_entities)) {
                $referenced_entities = [];
                $entity_needs_save = TRUE;
                $is_referenced = FALSE;
                $is_full = FALSE;
              }
              $can_add = TRUE;
              $flags['cleared'] = TRUE;
            }
          }
          break;

        case 'remove':
          if ($is_referenced) {
            $index = static::getReferenceIndex($referenced_entities, $target);
            while ($index !== FALSE) {
              unset($referenced_entities[$index]);
              $entity_needs_save = TRUE;
              $index = static::getReferenceIndex($referenced_entities, $target);
              $is_full = !($cardinality === $unlimited) && !(count($referenced_entities) < $cardinality);
            }
            $is_referenced = FALSE;
          }
          break;

      }
    }
    if ($can_add) {
      foreach ($method_settings as $method_setting) {
        switch ($method_setting) {

          case 'append':
            array_push($referenced_entities, $target);
            $entity_needs_save = TRUE;
            $is_referenced = TRUE;
            break;

          case 'prepend':
            array_unshift($referenced_entities, $target);
            $entity_needs_save = TRUE;
            $is_referenced = TRUE;
            break;

          case 'set':
            if (empty($flags['cleared'])) {
              if (!empty($referenced_entities)) {
                $referenced_entities = [];
                $is_referenced = FALSE;
              }
              $flags['cleared'] = TRUE;
            }
            $referenced_entities[] = $target;
            $entity_needs_save = TRUE;
            $is_referenced = TRUE;
            break;

        }
      }
    }
    if ($entity_needs_save) {
      $item_list->setValue(array_values($referenced_entities));
      Flow::needsSave($item_list->getEntity(), $this);
    }
  }

  /**
   * Set the Flow subject plugin manager.
   *
   * @param \Drupal\flow\Plugin\FlowSubjectManager $subject_manager
   *   The subject plugin manager.
   */
  public function setSubjectManager(FlowSubjectManager $subject_manager): void {
    $this->subjectManager = $subject_manager;
  }

  /**
   * Initializes the target subject.
   */
  public function initTargetSubject(): void {
    // Initialize the plugin instance of the target subject, if configured.
    $settings = $this->getSettings();
    if (empty($settings['target']['id'])) {
      return;
    }
    $definition = $this->getPluginDefinition();
    $target_configuration = $settings['target'] ?? [];
    $target_configuration += array_intersect_key($this->configuration,
      ['entity_type_id' => TRUE, 'bundle' => TRUE, 'task_mode' => TRUE]);
    // Attach a runtime setting, which describes for which type of reference
    // this target is being used for.
    $target_configuration['settings']['target_for'] = [
      'entity_type' => $definition['entity_type'],
      'bundle' => $definition['bundle'],
    ];
    if (isset($settings['field_name'])) {
      $target_configuration['settings']['target_for']['field'] = $settings['field_name'];
    }
    if (isset($settings['reverse']['field_name'])) {
      $target_configuration['settings']['target_for']['reverse_field'] = $settings['reverse']['field_name'];
    }
    $this->setTargetSubject($this->subjectManager->createInstance($settings['target']['id'], $target_configuration));
  }

  /**
   * Get the target subject, if defined.
   *
   * @return \Drupal\flow\Plugin\FlowSubjectInterface
   *   The target subject, or NULL if the subject has not yet been configured.
   */
  public function getTargetSubject(): ?FlowSubjectInterface {
    if (!isset($this->target)) {
      $this->initTargetSubject();
    }
    return $this->target;
  }

  /**
   * Set a subject plugin instance as the target subject.
   *
   * @param \Drupal\flow\Plugin\FlowSubjectInterface $subject
   *   The target subject.
   */
  public function setTargetSubject(FlowSubjectInterface $subject): void {
    $this->target = $subject;
  }

  /**
   * Returns the delta when the entity is in the list of referenced entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] &$referenced_entities
   *   The list of referenced entities.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check for.
   *
   * @return int|bool
   *   Returns the delta index if the entity is referenced, FALSE otherwise.
   *   Please note that the index may be 0.
   */
  public static function getReferenceIndex(&$referenced_entities, ContentEntityInterface $entity) {
    foreach ($referenced_entities as $i => $referenced) {
      if (($referenced === $entity) || ($referenced->uuid() === $entity->uuid()) || (($referenced->getEntityTypeId() === $entity->getEntityTypeId()) && ($referenced->id() === $entity->id()))) {
        return (int) $i;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $etm = \Drupal::entityTypeManager();
    $source_definition = $this->getPluginDefinition();
    if (isset($this->settings['field_name'])) {
      if ($field_storage_config = $etm->getStorage('field_storage_config')->load($source_definition['entity_type'] . '.' . $this->settings['field_name'])) {
        $dependencies[$field_storage_config->getConfigDependencyKey()][] = $field_storage_config->getConfigDependencyName();
      }
      if ($field_config = $etm->getStorage('field_config')->load($source_definition['entity_type'] . '.' . $source_definition['bundle'] . '.' . $this->settings['field_name'])) {
        $dependencies[$field_config->getConfigDependencyKey()][] = $field_config->getConfigDependencyName();
      }
    }
    $target_entity_type_id = $this->configuration['target_entity_type_id'] ?? NULL;
    $target_bundle = $this->configuration['target_bundle'] ?? NULL;
    if ($target_entity_type_id && $target_bundle && !empty($this->settings['reverse']['field_name'])) {
      if ($field_storage_config = $etm->getStorage('field_storage_config')->load($target_entity_type_id . '.' . $this->settings['reverse']['field_name'])) {
        $dependencies[$field_storage_config->getConfigDependencyKey()][] = $field_storage_config->getConfigDependencyName();
      }
      if ($field_config = $etm->getStorage('field_config')->load($target_entity_type_id . '.' . $target_bundle . '.' . $this->settings['reverse']['field_name'])) {
        $dependencies[$field_config->getConfigDependencyKey()][] = $field_config->getConfigDependencyName();
      }
    }
    /** @var \Drupal\flow\FlowSubjectBase $target */
    if ($target = $this->getTargetSubject()) {
      foreach ($target->calculateDependencies() as $key => $source_dependencies) {
        if (!isset($dependencies[$key])) {
          $dependencies[$key] = $source_dependencies;
        }
        else {
          $dependencies[$key] = array_unique(array_merge($dependencies[$key], $source_dependencies));
        }
      }
    }

    return $dependencies;
  }

}
