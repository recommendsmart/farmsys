<?php

namespace Drupal\flow\Plugin\flow\Derivative\Task;

use Drupal\flow\Helpers\EntityFieldManagerTrait;
use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Task plugin deriver for relating content.
 *
 * @see \Drupal\flow\Plugin\flow\Task\Relate
 */
class RelateDeriver extends ContentDeriverBase {

  use EntityFieldManagerTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var \Drupal\flow\Plugin\flow\Derivative\Subject\ReferenceDeriver $instance */
    $instance = parent::create($container, $base_plugin_id);
    $instance->setEntityFieldManager($container->get(self::$entityFieldManagerServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $content_derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    $relate_derivatives = [];
    foreach ($content_derivatives as &$source_content_derivative) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($source_content_derivative['entity_type'], $source_content_derivative['bundle']);
      $reference_targets = [];
      foreach ($field_definitions as $field_definition) {
        $field_storage_definition = $field_definition->getFieldStorageDefinition();
        if (!(strpos($field_storage_definition->getType(), 'entity_reference') === 0)) {
          continue;
        };
        $target_type = $field_storage_definition->getSetting('target_type');
        $handler_settings = $field_definition->getConfig($source_content_derivative['bundle'])->getSetting('handler_settings');
        if (!isset($reference_targets[$target_type])) {
          $reference_targets[$target_type] = [];
        }
        if (!empty($handler_settings['target_bundles'])) {
          $reference_targets[$target_type] += $handler_settings['target_bundles'];
        }
        else {
          $reference_targets[$target_type] = [];
        }
      }
      foreach ($reference_targets as $target_type => $target_bundles) {
        foreach ($content_derivatives as $target_content_derivative) {
          $target_content_derivative['entity_type'];
          $target_content_derivative['bundle'];
          if (($target_type !== $target_content_derivative['entity_type']) || (!empty($target_bundles) && !in_array($target_content_derivative['bundle'], $target_bundles))) {
            continue;
          }
          $derivative_id = $source_content_derivative['entity_type'] . '.' . $source_content_derivative['bundle'] . '::' . $target_content_derivative['entity_type'] . '.' . $target_content_derivative['bundle'];
          $relate_derivatives[$derivative_id] = [
            'label' => $this->t('Relate @source with @target', [
              '@source' => $source_content_derivative['label'],
              '@target' => $target_content_derivative['label'],
            ]),
          ] + $source_content_derivative;
        }
      }
    }
    return $relate_derivatives;
  }

}
