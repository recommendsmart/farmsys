<?php

namespace Drupal\flow_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\flow\FlowTaskMode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates flow-related local tasks.
 */
class FlowUiLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a FlowUiLocalTasks object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->basePluginId = $base_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $task_modes = FlowTaskMode::service()->getAvailableTaskModes();
    $default_task_mode = FlowTaskMode::service()->getDefaultTaskMode();
    $flow_list_cache_tags = $this->entityTypeManager->getDefinition('flow')->getListCacheTags();

    $custom_flows = [];
    /** @var \Drupal\flow\Entity\FlowInterface $flow */
    foreach ($this->entityTypeManager->getStorage('flow')->loadMultiple() as $flow) {
      if ($flow->isCustom()) {
        $custom = $flow->get('custom');
        $custom_flows[$flow->getTargetEntityTypeId()][$flow->getTaskMode()] = $custom['label'];
      }
    }

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!($base_route = $entity_type->get('field_ui_base_route'))) {
        continue;
      }

      $this->derivatives[$entity_type_id] = [
        'title' => 'Manage flow',
        'route_name' => "entity.flow.{$entity_type_id}.default",
        'weight' => 100,
        'cache_tags' => $flow_list_cache_tags,
        'base_route' => $base_route,
      ];

      $weight = 0;
      foreach ($task_modes as $task_mode => $task_mode_label) {
        $this->derivatives[$entity_type_id . '.' . $task_mode] = [
          'title' => $task_mode_label,
          'route_name' => $task_mode === $default_task_mode ? "entity.flow.{$entity_type_id}.default" : "entity.flow.{$entity_type_id}.task_mode",
          'route_parameters' => $task_mode === $default_task_mode ? [] : [
            'flow_task_mode' => $task_mode,
          ],
          'parent_id' => "flow_ui.flow:{$entity_type_id}",
          'base_route' => $base_route,
          'cache_tags' => $flow_list_cache_tags,
          'weight' => $weight++,
        ];
      }

      $weight += 100;

      if (isset($custom_flows[$entity_type_id])) {
        foreach ($custom_flows[$entity_type_id] as $custom_task_mode => $custom_label) {
          $this->derivatives[$entity_type_id . '.' . $custom_task_mode . '.custom'] = [
            'title' => $custom_label,
            'route_name' => "entity.flow.{$entity_type_id}.task_mode",
            'route_parameters' => [
              'flow_task_mode' => $custom_task_mode,
            ],
            'parent_id' => "flow_ui.flow:{$entity_type_id}",
            'base_route' => $base_route,
            'cache_tags' => $flow_list_cache_tags,
            'weight' => $weight++,
          ];
        }
      }

      $weight += 100;

      $this->derivatives[$entity_type_id . '.custom.add'] = [
        'title' => $this->t('+ add'),
        'route_name' => "entity.flow.{$entity_type_id}.custom.add",
        'parent_id' => "flow_ui.flow:{$entity_type_id}",
        'base_route' => $base_route,
        'weight' => $weight++,
      ];
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
