<?php

namespace Drupal\entity_extra_field\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\entity_extra_field\ExtraFieldTypePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity extra field form.
 */
class EntityExtraFieldForm extends EntityForm {

  /**
   * The cache discovery service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDiscovery;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The extra field type manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $extraFieldTypeManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Define the extra field type manager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_discovery_backend
   *   The cache discovery backend service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $extra_field_type_manager
   *   The extra field type plugin manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_plugin_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(
    CacheBackendInterface $cache_discovery_backend,
    ContextRepositoryInterface $context_repository,
    PluginManagerInterface $extra_field_type_manager,
    PluginManagerInterface $condition_plugin_manager,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->cacheDiscovery = $cache_discovery_backend;
    $this->contextRepository = $context_repository;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->extraFieldTypeManager = $extra_field_type_manager;
    $this->conditionPluginManager = $condition_plugin_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container
  ): EntityExtraFieldForm {
    return new static(
      $container->get('cache.discovery'),
      $container->get('context.repository'),
      $container->get('plugin.manager.extra_field_type'),
      $container->get('plugin.manager.condition'),
      $container->get('cache_tags.invalidator'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $form = parent::form($form, $form_state);

    $form['#parents'] = [];
    $form['#prefix'] = '<div id="entity-extra-field">';
    $form['#suffix'] = '</div>';

    $form_state->setTemporaryValue('gathered_contexts', [
      'entity_extra_field.target_entity' => $entity->getBaseEntityContext(),
    ] + $this->contextRepository->getAvailableContexts());

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Input the extra field name.'),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$entity, 'exists'],
      ],
      '#disabled' => !$entity->isNew(),
      '#default_value' => $entity->name(),
    ];
    $form['display_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Label'),
      '#description' => $this->t('Display the extra field name.'),
      '#default_value' => $entity->displayLabel(),
    ];
    $form['display'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['display']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Type'),
      '#description' => $this->t('Select the extra field display type. <br/>
        The <em>View</em> display will render within the entity view. <br/>
        The <em>Form</em> display will render within the entity edit form.'),
      '#required' => TRUE,
      '#options' => [
        'form' => $this->t('Form'),
        'view' => $this->t('View'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $this->getEntityFormStateValue(
        ['display', 'type'],
        $form_state
      ),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->description(),
    ];
    $field_type_id = $this->getEntityFormStateValue('field_type_id', $form_state);

    $form['field_type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Field Type'),
      '#required' => TRUE,
      '#options' => $this->getExtraFieldTypeOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $field_type_id,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-extra-field',
        'callback' => [$this, 'entityExtraFieldAjax'],
      ],
    ];

    if (isset($field_type_id) && !empty($field_type_id)) {
      $field_type_instance = $this->createFieldTypeInstance(
        $field_type_id, $form_state
      );

      if ($field_type_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => ['field_type_config']];

        $form['field_type_config'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Field Type Configuration'),
          '#tree' => TRUE,
        ];
        $form['field_type_config'] += $field_type_instance->buildConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }

    $this->attachFieldTypeConditionForm(
      $form,
      $form_state,
      EntityContextDefinition::create($this->getExtraFieldBaseEntityTypeId())
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    parent::validateForm($form, $form_state);

    if ($field_type_id = $form_state->getValue('field_type_id')) {
      $field_type_instance = $this->createFieldTypeInstance(
        $field_type_id, $form_state
      );

      if ($field_type_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => ['field_type_config']];

        $field_type_instance->validateConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }

    if ($condition = $form_state->getValue(['field_type_condition'])) {
      foreach ($condition as $plugin_id => $configuration) {
        $subform_parents = array_merge(['field_type_condition'], [$plugin_id]);

        /** @var \Drupal\Core\Condition\ConditionInterface $instance */
        $instance = $this->conditionPluginManager
          ->createInstance($plugin_id, $configuration);

        $subform = ['#parents' => $subform_parents];
        $instance->validateConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    if ($field_type_id = $form_state->getValue('field_type_id')) {
      $field_type_instance = $this->createFieldTypeInstance(
        $field_type_id, $form_state
      );

      if ($field_type_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => ['field_type_config']];

        $field_type_instance->submitConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );

        $form_state->setValue(
          'field_type_config',
          $field_type_instance->getConfiguration()
        );
      }
    }
    $this->submitFieldTypeConditionForm($form, $form_state);

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for entity extra field.
   *
   * @param array $form
   *   An array of form elements.
   *
   * @return array
   *   An array of the form elements.
   */
  public function entityExtraFieldAjax(array $form): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    $this->flushAllCaches();

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(
    RouteMatchInterface $route_match,
    $entity_type_id
  ): EntityInterface {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      $values = [];
      $type_manager = $this->entityTypeManager;

      if ($base_entity_type_id = $route_match->getParameter('entity_type_id')) {
        $values['base_entity_type_id'] = $base_entity_type_id;

        if ($definition = $type_manager->getDefinition($base_entity_type_id)) {
          $bundle_type = $definition->getBundleEntityType();

          if ($base_bundle_type = $route_match->getParameter($bundle_type)) {
            $values['base_bundle_type_id'] = $base_bundle_type->id();
          }
        }
      }

      $entity = $type_manager->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }

  /**
   * Create extra field type plugin instance.
   *
   * @param string $plugin_id
   *   The field type plugin identifier.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return \Drupal\entity_extra_field\ExtraFieldTypePluginInterface
   *   Return the extra field type plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function createFieldTypeInstance(
    string $plugin_id,
    FormStateInterface $form_state
  ): ExtraFieldTypePluginInterface {
    return $this->extraFieldTypeManager->createInstance(
      $plugin_id,
      $this->getEntityFormStateValue('field_type_config', $form_state, [])
    );
  }

  /**
   * Get extra field base entity type identifier.
   *
   * @return string
   *   The extra field base entity type identifier.
   */
  protected function getExtraFieldBaseEntityTypeId(): string {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity_extra_field */
    $entity_extra_field = $this->entity;

    return $entity_extra_field->getBaseEntityTypeId();
  }

  /**
   * Get condition definitions by context.
   *
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context
   *   The context definition.
   *
   * @return array
   *   An array of condition definition based on the given context.
   */
  protected function getConditionDefinitionsByContext(
    ContextDefinitionInterface $context
  ): array {
    return $this->conditionPluginManager->getDefinitionsForContexts([
      new Context($context),
    ]);
  }

  /**
   * Attach field type condition form.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context
   *   The context definition.
   *
   * @return \Drupal\entity_extra_field\Form\EntityExtraFieldForm
   *   The entity extra field form object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function attachFieldTypeConditionForm(
    array &$form,
    FormStateInterface $form_state,
    ContextDefinitionInterface $context
  ): self {
    $parents = ['field_type_condition'];

    $form['conditions'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Field Type Conditions'),
    ];
    $form['field_type_condition']['#tree'] = TRUE;

    foreach ($this->getConditionDefinitionsByContext($context) as $plugin_id => $definition) {
      $form['field_type_condition'][$plugin_id] = [
        '#type' => 'details',
        '#title' => $definition['label'],
        '#group' => 'conditions',
      ];
      $subform_parents = array_merge($parents, [$plugin_id]);

      $configuration = $this->getEntityFormStateValue(
        $subform_parents, $form_state, []
      );

      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionPluginManager
        ->createInstance($plugin_id, $configuration);

      $subform = ['#parents' => $subform_parents];
      $form['field_type_condition'][$plugin_id] += $condition->buildConfigurationForm(
        $subform,
        SubformState::createForSubform($subform, $form, $form_state)
      );
      // @todo Remove workaround once
      // https://www.drupal.org/project/drupal/issues/2783897 is fixed.
      if ($plugin_id === 'current_theme') {
        $form['field_type_condition'][$plugin_id]['theme']['#empty_option'] = $this->t('- None -');
      }
    }
    $form['field_conditions_all_pass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All Conditions Must Pass'),
      '#description' => $this->t('If checked, then all conditions must evaluate true.'),
      '#default_value' => $this->getEntityFormStateValue(
        'field_conditions_all_pass',
        $form_state
      ),
    ];

    return $this;
  }

  /**
   * Submit field type condition form.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return \Drupal\entity_extra_field\Form\EntityExtraFieldForm
   *   The entity extra field form object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function submitFieldTypeConditionForm(
    array &$form,
    FormStateInterface $form_state
  ): self {
    $parents = ['field_type_condition'];

    if ($condition = $form_state->getValue($parents)) {
      foreach ($condition as $plugin_id => $configuration) {
        $subform_parents = array_merge($parents, [$plugin_id]);

        /** @var \Drupal\Core\Condition\ConditionInterface $instance */
        $instance = $this->conditionPluginManager
          ->createInstance($plugin_id, $configuration);

        $subform = ['#parents' => $subform_parents];
        $instance->submitConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );

        $form_state->setValue(
          $subform_parents,
          $instance->getConfiguration()
        );
      }
    }

    return $this;
  }

  /**
   * Flush all caches related to this form.
   */
  protected function flushAllCaches(): self {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $this->cacheDiscovery->invalidateAll();

    $this->cacheTagsInvalidator->invalidateTags([
      $entity->getCacheRenderTag(),
    ]);

    return $this;
  }

  /**
   * Get extra field type options.
   *
   * @return array
   *   An array of extra field type options.
   */
  protected function getExtraFieldTypeOptions(): array {
    $options = [];

    foreach ($this->extraFieldTypeManager->getDefinitions() as $plugin_id => $definition) {
      if (!isset($definition['label'])) {
        continue;
      }
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }

  /**
   * Get the form state value.
   *
   * @param string|array $key
   *   The element key.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param mixed $default
   *   The default value if nothing is found.
   *
   * @return mixed|null
   *   The form value; otherwise FALSE if the value can't be found.
   */
  protected function getEntityFormStateValue(
    $key,
    FormStateInterface $form_state,
    $default = NULL
  ) {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $key = !is_array($key) ? [$key] : $key;

    $inputs = [
      $form_state->cleanValues()->getValues(),
    ];

    if ($entity->id() !== NULL) {
      $inputs[] = $entity->toArray();
    }

    foreach ($inputs as $input) {
      $value = NestedArray::getValue($input, $key, $key_exists);

      if (!isset($value) && !$key_exists) {
        continue;
      }

      return $value;
    }

    return $default;
  }

}
