<?php

namespace Drupal\group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupVisibilityBase;

/**
 * Provides a 'flex' group visibility.
 *
 * @GroupVisibility(
 *  id = "flex",
 *  label = @Translation("Let owner decide"),
 *  weight = -80
 * )
 */
class FlexVisibility extends GroupVisibilityBase {

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

}
