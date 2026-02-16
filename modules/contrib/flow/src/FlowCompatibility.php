<?php

namespace Drupal\flow;

use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\Plugin\FlowQualifierInterface;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Drupal\flow\Plugin\FlowTaskInterface;

/**
 * Helper class for checking compatibility between Flow components.
 */
final class FlowCompatibility {

  /**
   * Validates Flow compatibility between given objects.
   *
   * @param ...
   *   An arbitrary sequence of objects as parameters, which are either one of a
   *   Flow config entity or a Flow-related plugin, e.g. a task or subject.
   *
   * @return bool
   *   Returns TRUE if the objects are compatible, FALSE otherwise.
   */
  public static function validate(): bool {
    $objects = func_get_args();
    if (count($objects) < 2) {
      throw new \InvalidArgumentException('FlowCompatibility::validate expects at least two arguments.');
    }

    $plugin_entity_type_id = NULL;
    $plugin_bundle = NULL;
    $flow_type_id = NULL;
    $flow_bundle = NULL;
    $task_modes = NULL;
    $targets = NULL;

    foreach ($objects as $object) {
      if ($object instanceof FlowInterface) {
        $flow_type_id = $flow_type_id ?: $object->getTargetEntityTypeId();
        $flow_bundle = $flow_bundle ?: $object->getTargetBundle();
        if ($flow_type_id !== $object->getTargetEntityTypeId()) {
          return FALSE;
        }
        if ($flow_bundle !== $object->getTargetBundle()) {
          return FALSE;
        }
        $task_modes = $task_modes ?? ($object->isCustom() ? [$object->get('custom')['baseMode'], $object->getTaskMode()] : [$object->getTaskMode()]);
        $targets = $targets ?: [$flow_type_id => [$flow_bundle]];
        if (!in_array($object->getTaskMode(), $task_modes)) {
          return FALSE;
        }
        if (!isset($targets[$flow_type_id]) || (!empty($targets[$flow_type_id]) && !in_array($flow_bundle, $targets[$flow_type_id]))) {
          return FALSE;
        }
      }
      elseif ($object instanceof FlowSubjectInterface || $object instanceof FlowTaskInterface || $object instanceof FlowQualifierInterface) {
        $definition = $object->getPluginDefinition();
        $plugin_entity_type_id = $plugin_entity_type_id ?: $definition['entity_type'];
        $plugin_bundle = $plugin_bundle ?: $definition['bundle'];
        if ($plugin_entity_type_id !== $definition['entity_type']) {
          return FALSE;
        }
        if ($plugin_bundle !== $definition['bundle']) {
          return FALSE;
        }
        $task_modes = $task_modes ?: $definition['task_modes'];
        $targets = $targets ?: $definition['targets'];
        if (!empty($task_modes) && !empty($definition['task_modes']) && empty(array_intersect($definition['task_modes'], $task_modes))) {
          return FALSE;
        }
        if (!empty($targets) && !empty($definition['targets'])) {
          foreach ($definition['targets'] as $target_type_id => $target_bundles) {
            if (!isset($targets[$target_type_id])) {
              return FALSE;
            }
            foreach ($target_bundles as $target_bundle) {
              if (!empty($targets[$target_type_id]) && !in_array($target_bundle, $targets[$target_type_id])) {
                return FALSE;
              }
            }
          }
        }
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

}
