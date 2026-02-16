<?php

namespace Drupal\group_flex\Plugin\GroupJoiningMethod;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;

/**
 * Provides a 'join_button' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "join_button",
 *  label = @Translation("Call to action (join button)"),
 *  weight = -100,
 *  visibilityOptions = {
 *   "public",
 *   "flex"
 *  }
 * )
 */
class JoinButton extends GroupJoiningMethodBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [$groupType->getOutsiderRoleId() => ['join group' => TRUE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [$groupType->getOutsiderRoleId() => ['join group' => FALSE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getOutsiderRoleId() => ['join group'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getOutsiderRoleId() => ['join group'],
    ];
  }

}
