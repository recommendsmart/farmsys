<?php

namespace Drupal\group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupVisibilityBase;

/**
 * Provides a 'public' group visibility.
 *
 * @GroupVisibility(
 *  id = "public",
 *  label = @Translation("Public (visible by anybody authorised to view a Group of that type)"),
 *  weight = -100
 * )
 */
class PublicVisibility extends GroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => TRUE,
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
    $groupType = $group->getGroupType();

    // Add perm when anonymous role has permission to view group on group type.
    $anonymousPermissions = [];
    if ($groupType->getAnonymousRole()->hasPermission('view group')) {
      $anonymousPermissions = [$groupType->getAnonymousRoleId() => ['view group']];
    }
    return array_merge($anonymousPermissions, [
      $groupType->getOutsiderRoleId() => ['view group'],
      $groupType->getMemberRoleId() => ['view group'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Public (The @group_type_name will be viewed by non-members of the group)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name will be viewed by non-members of the group', ['@group_type_name' => $groupType->label()]);
  }

}
