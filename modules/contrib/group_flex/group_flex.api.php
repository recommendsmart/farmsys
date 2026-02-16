<?php

/**
 * @file
 * Hooks specific to the Group Flex module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the plugin implementations of the group joining methods.
 *
 * @param array $definitions
 *   The array of plugin definitions keyed by plugin id.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
function hook_group_flex_group_joining_method_info_alter(array &$definitions) {
  // When the grequest module is not installed remove this plugin.
  if (isset($definitions['group_membership_request'])) {
    /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $moduleHandler = Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('grequest')) {
      unset($definitions['group_membership_request']);
    }
  }
}

/**
 * Alter the plugin implementations of the group visibility.
 *
 * @param array $definitions
 *   The array of plugin definitions keyed by plugin id.
 */
function hook_group_flex_group_visibility_info_alter(array &$definitions) {
  // Public visibility should not be allowed on this site.
  if (isset($definitions['public'])) {
    unset($definitions['public']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
