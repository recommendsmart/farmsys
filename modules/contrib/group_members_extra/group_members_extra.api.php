<?php

/**
 * @file
 * Hooks specific to the Group Members Extra module.
 */

use Drupal\group\Entity\GroupInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links for the group_membership_operations block.
 *
 * @param array $operations
 *   A list of links to be set in an 'operations' element.
 * @param GroupInterface $group
 *   The group to alter the operations for.
 *
 * @see \Drupal\group\Plugin\Block\GroupOperationsBlock
 * @see \Drupal\Core\Render\Element\Dropbutton
 */
function hook_group_members_extra_membership_operations_alter(array &$operations, GroupInterface $group) {
  if ($group->label() == 'Hotel California') {
    unset($operations['group-leave']);
  }
}

/**
 * Alter the links for the group_content_operations block.
 *
 * @param array $operations
 *   A list of links to be set in an 'operations' element.
 * @param GroupInterface $group
 *   The group to alter the operations for.
 *
 * @see \Drupal\group\Plugin\Block\GroupOperationsBlock
 * @see \Drupal\Core\Render\Element\Dropbutton
 */
function hook_group_members_extra_content_operations_alter(array &$operations, GroupInterface $group) {
  if ($group->label() == 'Hotel California') {
    unset($operations['gnode-create-page']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
