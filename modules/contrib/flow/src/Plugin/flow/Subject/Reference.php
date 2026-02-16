<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityFieldManagerTrait;
use Drupal\flow\Helpers\FallbackSubjectTrait;
use Drupal\flow\Helpers\ModuleHandlerTrait;
use Drupal\flow\Plugin\FlowSubjectBase;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subject for content referenced from other content.
 *
 * @FlowSubject(
 *   id = "reference",
 *   label = @Translation("Content that is referenced from other content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\ReferenceDeriver"
 * )
 */
class Reference extends FlowSubjectBase implements PluginFormInterface {

  use EntityFieldManagerTrait;
  use ModuleHandlerTrait;
  use FallbackSubjectTrait;
  use StringTranslationTrait;

  /**
   * The subject plugin instance used as the source of the reference.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectInterface
   */
  protected FlowSubjectInterface $source;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\flow\Plugin\flow\Subject\Reference $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setEntityFieldManager($container->get(self::$entityFieldManagerServiceName));
    $instance->setModuleHandler($container->get(self::$moduleHandlerServiceName));

    // Initialize the plugin instance of the source subject.
    $settings = $instance->getSettings();
    /** @var \Drupal\flow\Plugin\FlowSubjectManager $subject_manager */
    $subject_manager = $container->get('plugin.manager.flow.subject');
    [, $source_plugin_id] = explode('::', $plugin_id, 2);
    $source_configuration = $settings['source'] ?? [];
    $source_configuration += array_intersect_key($configuration,
      ['entity_type_id' => TRUE, 'bundle' => TRUE, 'task_mode' => TRUE]);
    // Attach a runtime setting, which describes for which type of reference
    // this source is being used for.
    $source_configuration['settings']['source_for'] = [
      'entity_type' => $plugin_definition['entity_type'],
      'bundle' => $plugin_definition['bundle'],
    ];
    if (isset($settings['field_name'])) {
      $source_configuration['settings']['source_for']['field'] = $settings['field_name'];
    }
    $instance->setSourceSubject($subject_manager->createInstance($source_plugin_id, $source_configuration));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    if (isset($this->source)) {
      $plugin_definition = $this->getPluginDefinition();
      $source_configuration = $this->configuration['settings']['source'] ?? [];
      $source_configuration += array_intersect_key($this->configuration,
        ['entity_type_id' => TRUE, 'bundle' => TRUE, 'task_mode' => TRUE]);
      $source_configuration['settings']['source_for'] = [
        'entity_type' => $plugin_definition['entity_type'],
        'bundle' => $plugin_definition['bundle'],
      ];
      if (isset($this->configuration['settings']['field_name'])) {
        $source_configuration['settings']['source_for']['field'] = $this->configuration['settings']['field_name'];
      }
      $this->source->setConfiguration($source_configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings): void {
    parent::setSettings($settings);
    if (isset($this->source)) {
      $plugin_definition = $this->getPluginDefinition();
      $source_settings = $this->settings['source']['settings'] ?? [];
      $source_settings['source_for'] = [
        'entity_type' => $plugin_definition['entity_type'],
        'bundle' => $plugin_definition['bundle'],
      ];
      if (isset($this->settings['field_name'])) {
        $source_settings['source_for']['field'] = $this->settings['field_name'];
      }
      $this->source->setSettings($source_settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectItems(): iterable {
    if (!($field_name = $this->settings['field_name'] ?? NULL)) {
      return [];
    }

    $definition = $this->getPluginDefinition();
    $entity_type_id = $definition['entity_type'];
    $bundle = $definition['bundle'];

    $no_yield_at_all = TRUE;
    foreach ($this->getSourceSubject()->getSubjectItems() as $source_item) {
      if (!$source_item->hasField($field_name)) {
        continue;
      }
      $item_list = $source_item->get($field_name);
      if (!($item_list instanceof EntityReferenceFieldItemListInterface)) {
        continue;
      }
      $is_empty = TRUE;
      foreach ($item_list->referencedEntities() as $item) {
        if (($item instanceof ContentEntityInterface) && ($item->getEntityTypeId() === $entity_type_id) && ($item->bundle() === $bundle)) {
          $is_empty = FALSE;
          $no_yield_at_all = FALSE;
          yield $item;
        }
      }
      if ($is_empty) {
        foreach ($this->getFallbackItems() as $item) {
          if (($item instanceof ContentEntityInterface) && ($item->getEntityTypeId() === $entity_type_id) && ($item->bundle() === $bundle)) {
            $is_empty = FALSE;
            $no_yield_at_all = FALSE;
            $item_list->appendItem($item);
            Flow::needsSave($source_item, $this);
            yield $item;
          }
        }
      }
    }

    if ($no_yield_at_all) {
      return [];
    }
  }

  /**
   * Get the source subject plugin instance.
   *
   * @return \Drupal\flow\Plugin\FlowSubjectInterface
   *   The source subject.
   */
  public function getSourceSubject(): FlowSubjectInterface {
    return $this->source;
  }

  /**
   * Set a subject plugin instance as the source subject.
   *
   * @param \Drupal\flow\Plugin\FlowSubjectInterface $subject
   *   The source subject.
   */
  public function setSourceSubject(FlowSubjectInterface $subject): void {
    $this->source = $subject;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $source = $this->getSourceSubject();
    $source_definition = $source->getPluginDefinition();
    $source_entity_type_id = $source_definition['entity_type'];
    $source_bundle = $source_definition['bundle'];

    $target_definition = $this->getPluginDefinition();
    $target_entity_type_id = $target_definition['entity_type'];
    $target_bundle = $target_definition['bundle'];

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
        '@source' => $source_definition['label'],
        '@field' => $field_definition->getLabel(),
      ]);
    }

    $weight = 10;
    $select_widget = $this->moduleHandler->moduleExists('select2') ? 'select2' : 'select';
    $form['field_name'] = [
      '#type' => $select_widget,
      '#title' => $this->t('Source field'),
      '#description' => $this->t('Select the field that identifies the @reference.', [
        '@reference' => $target_definition['label'],
      ]),
      '#options' => $field_name_options,
      '#default_value' => $this->settings['field_name'] ?? '_none',
      '#required' => TRUE,
      '#empty_value' => '_none',
      '#weight' => $weight++,
    ];

    $form['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@type: @name', [
        '@type' => $this->t('Source'),
        '@name' => $source_definition['label'],
      ]),
      '#weight' => $weight++,
    ];
    if ($source instanceof PluginFormInterface) {
      $form['source']['settings'] = [];
      $source_form_state = SubformState::createForSubform($form['source']['settings'], $form, $form_state);
      $form['source']['settings'] = $source->buildConfigurationForm($form['source']['settings'], $source_form_state);
      if (isset($form['source']['settings']['fallback']['#title'])) {
        $form['source']['settings']['fallback']['#title'] = $this->t('When the source could not be loaded');
      }
    }
    else {
      $form['source']['no_settings'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This source does not provide any settings.'),
      ];
    }

    $this->buildFallbackForm($form, $form_state);
    if (isset($form['fallback']['#title'])) {
      $form['fallback']['#title'] = $this->t('When the reference could not be loaded');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $source = $this->getSourceSubject();
    if ($source instanceof PluginFormInterface) {
      $source_form_state = SubformState::createForSubform($form['source']['settings'], $form, $form_state);
      $source->validateConfigurationForm($form['source']['settings'], $source_form_state);
    }
    $this->validateFallbackForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->settings['field_name'] = $form_state->getValue(['field_name']);

    $source = $this->getSourceSubject();
    if (isset($form['source']['settings']) && $source instanceof PluginFormInterface) {
      $source_form_state = SubformState::createForSubform($form['source']['settings'], $form, $form_state);
      $source->submitConfigurationForm($form['source']['settings'], $source_form_state);
    }
    $this->settings['source'] = [
      'id' => $source->getPluginId(),
      'type' => $source->getBaseId(),
      'settings' => $source->getSettings(),
      'third_party_settings' => [],
    ];
    foreach ($source->getThirdPartyProviders() as $provider) {
      $this->settings['source']['third_party_settings'][$provider] = $source->getThirdPartySettings($provider);
    }

    $this->submitFallbackForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    /** @var \Drupal\flow\Plugin\FlowSubjectBase $source */
    $source = $this->getSourceSubject();
    $etm = \Drupal::entityTypeManager();
    if ($source && isset($this->settings['field_name'])) {
      $source_definition = $source->getPluginDefinition();
      if ($field_storage_config = $etm->getStorage('field_storage_config')->load($source_definition['entity_type'] . '.' . $this->settings['field_name'])) {
        $dependencies[$field_storage_config->getConfigDependencyKey()][] = $field_storage_config->getConfigDependencyName();
      }
      if ($field_config = $etm->getStorage('field_config')->load($source_definition['entity_type'] . '.' . $source_definition['bundle'] . '.' . $this->settings['field_name'])) {
        $dependencies[$field_config->getConfigDependencyKey()][] = $field_config->getConfigDependencyName();
      }
    }
    if ($source) {
      foreach ($source->calculateDependencies() as $key => $source_dependencies) {
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
