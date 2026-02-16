<?php

namespace Drupal\group_action\Plugin\Action;

/**
 * Action plugin for removing a user as a member from a group.
 *
 * @Action(
 *   id = "group_remove_member",
 *   label = "Group: remove user as member",
 *   type = "user"
 * )
 */
class GroupRemoveMember extends GroupRemoveContent {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operation' => 'delete',
      'content_plugin' => 'group_membership',
      'group_id' => '',
      'entity_id' => '',
      'values' => '',
    ];
  }

}
