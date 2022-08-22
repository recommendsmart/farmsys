<?php

namespace Drupal\group_action\Plugin\Action;

/**
 * Action plugin for updating an existing membership.
 *
 * @Action(
 *   id = "group_update_member",
 *   label = "Group: update user membership",
 *   type = "user"
 * )
 */
class GroupUpdateMember extends GroupUpdateContent {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operation' => 'update',
      'content_plugin' => 'group_membership',
      'group_id' => '',
      'entity_id' => '',
      'values' => '',
    ];
  }

}
