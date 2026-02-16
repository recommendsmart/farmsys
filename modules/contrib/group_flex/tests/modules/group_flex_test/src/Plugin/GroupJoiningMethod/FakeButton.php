<?php

namespace Drupal\group_flex_test\Plugin\GroupJoiningMethod;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;

/**
 * Provides a 'fake_button' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "fake_button",
 *  label = @Translation("Non-existent fake joining method"),
 *  weight = 10,
 *  visibilityOptions = {
 *   "public",
 *   "flex"
 *  }
 * )
 */
class FakeButton extends GroupJoiningMethodBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [$groupType->getOutsiderRoleId() => ['join group' => FALSE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [];
  }

}
