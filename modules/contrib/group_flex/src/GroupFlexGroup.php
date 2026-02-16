<?php

namespace Drupal\group_flex;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Drupal\group_permissions\GroupPermissionsManager;

/**
 * Get the group flex settings from a group.
 */
class GroupFlexGroup {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermManager;

  /**
   * The flex group type helper.
   *
   * @var \Drupal\group_flex\GroupFlexGroupType
   */
  protected $flexGroupType;

  /**
   * Constructs a new GroupFlexGroup.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManager $groupPermManager
   *   The group permissions manager.
   * @param \Drupal\group_flex\GroupFlexGroupType $flexGroupType
   *   The group type flex.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GroupPermissionsManager $groupPermManager, GroupFlexGroupType $flexGroupType) {
    $this->entityTypeManager = $entityTypeManager;
    $this->groupPermManager = $groupPermManager;
    $this->flexGroupType = $flexGroupType;
  }

  /**
   * Get the group visibility for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to return the default value for.
   *
   * @return string
   *   The group visibility.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupVisibility(GroupInterface $group): string {
    // Retrieve the default group type permission.
    $groupType = $group->getGroupType();
    $defaultVisibility = GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC;
    if (!$this->flexGroupType->hasFlexibleGroupTypeVisibility($groupType)) {
      $groupTypePermissions = $groupType->getOutsiderRole()->getPermissions();
      $defaultVisibility = in_array('view group', $groupTypePermissions, TRUE) ? GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC : GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE;
    }

    if (!$group->id()) {
      return $defaultVisibility;
    }

    // If this is an existing group add default based on permissions.
    $groupPermissions = $this->groupPermManager->getCustomPermissions($group);
    if (array_key_exists($group->getGroupType()->getOutsiderRoleId(), $groupPermissions) &&
      !in_array('view group', $groupPermissions[$group->getGroupType()->getOutsiderRoleId()], TRUE)) {
      return GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE;
    }

    return $defaultVisibility;
  }

  /**
   * Retrieve the default joining methods for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to retrieve the joining methods for.
   *
   * @return array
   *   The default joining methods for the given group.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getDefaultJoiningMethods(GroupInterface $group): array {
    $defaultMethods = [];

    // If this is an existing group add default based on permissions.
    if ($group->id()) {
      $joiningMethodPlugins = $this->flexGroupType->getEnabledJoiningMethodPlugins($group->getGroupType());

      $groupPermissions = $this->groupPermManager->getCustomPermissions($group);
      foreach ($joiningMethodPlugins as $pluginId => $pluginInstance) {
        $pluginPermissions = $pluginInstance->getGroupPermissions($group);
        if (!empty($pluginPermissions)) {
          foreach ($pluginPermissions as $roleId => $rolePermissions) {
            if (array_key_exists($roleId, $groupPermissions)) {
              foreach ($rolePermissions as $rolePermission) {
                if (in_array($rolePermission, $groupPermissions[$roleId], TRUE)) {
                  $defaultMethods[] = $pluginId;
                }
              }
            }
          }
        }
      }
    }
    return $defaultMethods;
  }

}
