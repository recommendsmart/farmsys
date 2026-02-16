<?php

namespace Drupal\entity_logger\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_logger\Event\EntityLoggerAvailableEntityTypesEvent;
use Drupal\entity_logger\Event\EntityLoggerEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Configure Entity Logger settings.
 */
class EntityLoggerSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a EntityLoggerSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Manages entity type plugin definitions.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_logger_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entity_logger.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_logger.settings');

    $form['enabled_entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled entity types'),
      '#description' => $this->t('Enable logging for these entity types.'),
      '#tree' => TRUE,
    ];

    // Get all applicable entity types.
    foreach ($this->getAvailableEntityTypes() as $entity_type_id => $entity_type_name) {
      $form['enabled_entity_types'][$entity_type_id] = [
        '#type' => 'checkbox',
        '#title' => $entity_type_name,
        '#default_value' => in_array($entity_type_id, $config->get('enabled_entity_types')),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('entity_logger.settings');
    $enabled_entity_types = array_filter($form_state->getValue('enabled_entity_types'), function ($checked) {
      return (bool) $checked;
    });
    $config->set('enabled_entity_types', array_keys($enabled_entity_types));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get a list of available entity types to enable entity logger on.
   *
   * @return array
   *   Options list of available entity types.
   */
  protected function getAvailableEntityTypes() {
    // Get entity types that are explicitly made available via the event.
    $event_entity_types = [];
    $event = new EntityLoggerAvailableEntityTypesEvent($event_entity_types);
    $this->eventDispatcher->dispatch(EntityLoggerEvents::AVAILABLE_ENTITY_TYPES, $event);
    $event_entity_types = $event->getEntityTypes();

    // Render a list of all entity types that should be available.
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasLinkTemplate('canonical') || in_array($entity_type_id, $event_entity_types)) {
        $entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }
    return $entity_types;
  }

}
