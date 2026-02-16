<?php

namespace Drupal\group_action\Plugin\Action;

/**
 * Action plugin for adding a user as a member to a group.
 *
 * @Action(
 *   id = "group_add_member",
 *   label = "Group: add user as member",
 *   type = "user"
 * )
 */
class GroupAddMember extends GroupAddContent {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operation' => 'create',
      'content_plugin' => 'group_membership',
      'group_id' => '',
      'entity_id' => '',
      'values' => '',
    ];
  }

}
