<?php

/**
 * @file
 * Hooks specific to the Flow module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the definition of available task modes.
 *
 * @param array &$task_modes
 *   The current list of defined task modes. Keyed by machine name, values are
 *   translatable labels.
 *
 * @see \Drupal\flow\FlowTaskMode
 */
function hook_flow_task_modes_alter(array &$task_modes) {
  // Do not support the deletion task mode.
  unset($task_modes['delete']);
}

/**
 * Alter the definition of available fallback methods to load subject items.
 *
 * Fallback methods can be selected for the case when a list of subject items
 * could not be loaded from an expected resource, e.g. when a view returns an
 * empty result set. A fallback method can then act accordingly, for example
 * creating a new subject item that will be automatically saved.
 *
 * @param array &$methods
 *   An associative array of available methods, keyed by method machine name.
 *   Each value is an associative array containing "label" as translatable
 *   markup for the human-readable label, and "callback" that is a callable
 *   for executing the method.
 * @param \Drupal\flow\Plugin\FlowSubjectInterface $plugin
 *   The plugin instance that asks for available fallback methods.
 */
function hook_flow_fallback_methods_alter(array &$methods, \Drupal\flow\Plugin\FlowSubjectInterface $plugin) {
  // Use a custom callback for creating new items.
  $methods['create']['callback'] = 'Drupal\mymodule\MyHandler::create';
}

/**
 * @} End of "addtogroup hooks".
 */
