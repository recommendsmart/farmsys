<?php

namespace Drupal\group_flex\Plugin\GroupJoiningMethod;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;

/**
 * Provides a 'group_membership_request' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "group_membership_request",
 *  label = @Translation("Request to join"),
 *  weight = -90,
 *  visibilityOptions = {
 *   "public",
 *   "flex"
 *  }
 * )
 */
class GroupMembershipRequest extends GroupJoiningMethodBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    // Only enable plugin when it doesn't exist yet.
    $contentEnablers = $this->groupContentEnabler->getInstalledIds($groupType);
    if (!in_array('group_membership_request', $contentEnablers)) {
      $storage = $this->entityTypeManager->getStorage('group_content_type');
      $config = [
        'group_cardinality' => 0,
        'entity_cardinality' => 1,
      ];
      $storage->createFromPlugin($groupType, 'group_membership_request', $config)->save();
    }

    $mappedPerm = [$groupType->getOutsiderRoleId() => ['request group membership' => TRUE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [$groupType->getOutsiderRoleId() => ['request group membership' => FALSE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getOutsiderRoleId() => ['request group membership'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getOutsiderRoleId() => ['request group membership'],
    ];
  }

}
