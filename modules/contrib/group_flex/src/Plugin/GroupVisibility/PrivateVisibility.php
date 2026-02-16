<?php

namespace Drupal\group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupVisibilityBase;

/**
 * Provides a 'private' group visibility.
 *
 * @GroupVisibility(
 *  id = "private",
 *  label = @Translation("Private (visible by members only)"),
 *  weight = -90
 * )
 */
class PrivateVisibility extends GroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getMemberRoleId() => ['view group'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getAnonymousRoleId() => ['view group'],
      $group->getGroupType()->getOutsiderRoleId() => ['view group'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Private (The @group_type_name will be viewed by group members only)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name will be viewed by group members only', ['@group_type_name' => $groupType->label()]);
  }

}
