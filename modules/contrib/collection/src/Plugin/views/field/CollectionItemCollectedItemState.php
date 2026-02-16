<?php

namespace Drupal\collection\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide the workflow state of the collected item entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("collection_item_collected_item_state")
 */
class CollectionItemCollectedItemState extends FieldPluginBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a CollectionItemCollectedItemState object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The id of the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$this->moduleHandler->moduleExists('content_moderation')) {
      return '';
    }

    $collection_item = $values->_entity;

    if ($this->moderationInfo->isModeratedEntity($collection_item->item->entity)) {
      $storage = $this->entityTypeManager->getStorage($collection_item->item->entity->getEntityTypeId());
      $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($collection_item->item->entity->id(), $collection_item->item->entity->language()->getId());
      $latest_revision = $storage->loadRevision($latest_revision_id);
      $workflow_type = $this->moderationInfo->getWorkflowForEntity($collection_item->item->entity)->getTypePlugin();

      if ($workflow_type->hasState($latest_revision->moderation_state->value)) {
        return $workflow_type->getState($latest_revision->moderation_state->value)->label();
      }
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Override the parent query function, since this is a computed field.
  }

}
