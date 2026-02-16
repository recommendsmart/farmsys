<?php

namespace Drupal\group_flex\Plugin\GroupJoiningMethod;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;

/**
 * Provides a 'group_membership_invite' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "group_invitation",
 *  label = @Translation("Invite users by email"),
 *  weight = -90,
 *  visibilityOptions = {
 *   "public",
 *   "private",
 *   "flex"
 *  }
 * )
 */
class GroupMembershipInvite extends GroupJoiningMethodBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    // Only enable plugin when it doesn't exist yet.
    $contentEnablers = $this->groupContentEnabler->getInstalledIds($groupType);
    if (!in_array('group_invitation', $contentEnablers)) {
      $storage = $this->entityTypeManager->getStorage('group_content_type');
      $config = [
        'group_cardinality' => 0,
        'entity_cardinality' => 0,
      ];
      $storage->createFromPlugin($groupType, 'group_invitation', $config)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) { }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getMemberRoleId() => ['invite users to group'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getMemberRoleId() => ['invite users to group'],
    ];
  }

}
