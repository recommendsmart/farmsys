<?php

namespace Drupal\group_flex;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\GroupPermissionsManager;

/**
 * Saving of a Group to implement the correct group type permissions.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 */
class GroupFlexGroupSaver {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\group_permissions\GroupPermissionsManager definition.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermManager;

  /**
   * The group visibility manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  private $visibilityManager;

  /**
   * The group joining method manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupJoiningMethodManager
   */
  private $joiningMethodManager;

  /**
   * The group flex group object.
   *
   * @var \Drupal\group_flex\GroupFlexGroup
   */
  private $groupFlex;

  /**
   * Constructs a new GroupFlexGroupSaver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManager $groupPermManager
   *   The group permissions manager.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $visibilityManager
   *   The group visibility manager.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $joiningMethodManager
   *   The group joining method manager.
   * @param \Drupal\group_flex\GroupFlexGroup $groupFlex
   *   The group flex.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GroupPermissionsManager $groupPermManager, GroupVisibilityManager $visibilityManager, GroupJoiningMethodManager $joiningMethodManager, GroupFlexGroup $groupFlex) {
    $this->entityTypeManager = $entityTypeManager;
    $this->groupPermManager = $groupPermManager;
    $this->visibilityManager = $visibilityManager;
    $this->joiningMethodManager = $joiningMethodManager;
    $this->groupFlex = $groupFlex;
  }

  /**
   * Save the group visibility.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to save.
   * @param string $groupVisibility
   *   The desired visibility of the group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveGroupVisibility(GroupInterface $group, string $groupVisibility) {
    $groupPermission = $this->getGroupPermissionObject($group);

    if (!$groupPermission) {
      return;
    }

    /** @var \Drupal\group_flex\Plugin\GroupVisibilityBase $pluginInstance */
    foreach ($this->getAllGroupVisibility() as $id => $pluginInstance) {
      // Retrieve the current group visibility plugin.
      if ($groupVisibility !== $id) {
        continue;
      }

      foreach ($pluginInstance->getGroupPermissions($group) as $role => $rolePermissions) {
        $groupPermission = $this->addRolePermissionsToGroup($groupPermission, $role, $rolePermissions);
      }
      foreach ($pluginInstance->getDisallowedGroupPermissions($group) as $role => $rolePermissions) {
        $groupPermission = $this->removeRolePermissionsFromGroup($groupPermission, $role, $rolePermissions);
      }

    }

    $violations = $groupPermission->validate();
    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions are not saved correctly, because:' . $message);
    }
    $groupPermission->save();

    // Save the group entity to reset the cache tags.
    $group->save();
  }

  /**
   * Save the group joining methods.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to save.
   * @param array $joiningMethods
   *   The desired joining methods of the group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function saveGroupJoiningMethods(GroupInterface $group, array $joiningMethods) {
    $groupPermission = $this->getGroupPermissionObject($group);
    if (!$groupPermission) {
      return;
    }

    /** @var \Drupal\group_flex\Plugin\GroupJoiningMethodBase $pluginInstance */
    foreach ($this->getAllJoiningMethods() as $id => $pluginInstance) {
      // Checks if the method is enabled.
      $isEnabled = in_array($id, $joiningMethods, TRUE) && $joiningMethods[$id] === $id;
      // Checks if the method is allowed for the group's visibility.
      $allowedVisibilities = $pluginInstance->getVisibilityOptions();
      $isAllowed = in_array($this->groupFlex->getGroupVisibility($group), $allowedVisibilities, TRUE);
      if ($isEnabled && $isAllowed) {
        foreach ($pluginInstance->getGroupPermissions($group) as $role => $rolePermissions) {
          $groupPermission = $this->addRolePermissionsToGroup($groupPermission, $role, $rolePermissions);
        }
        continue;
      }

      if (empty($pluginInstance->getDisallowedGroupPermissions($group))) {
        continue;
      }
      foreach ($pluginInstance->getDisallowedGroupPermissions($group) as $role => $rolePermissions) {
        $groupPermission = $this->removeRolePermissionsFromGroup($groupPermission, $role, $rolePermissions);
      }
    }

    if (count($groupPermission->validate()) > 0) {
      throw new EntityStorageException('Group permissions are not saved correctly.');
    }
    $groupPermission->save();

    // Save the group entity to reset the cache tags.
    $group->save();
  }

  /**
   * Get the default permissions for the given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type to return the permissions for.
   *
   * @return array
   *   An array of permissions keyed by role.
   */
  public function getDefaultGroupTypePermissions(GroupTypeInterface $groupType): array {
    $permissions = [];

    foreach ($groupType->getRoles() as $groupRoleId => $groupRole) {
      $permissions[$groupRoleId] = $groupRole->getPermissions();
    }

    return $permissions;
  }

  /**
   * Get all joining methods.
   *
   * @return array
   *   An array of joining methods containing the PluginInstances.
   */
  public function getAllJoiningMethods(): array {
    return $this->joiningMethodManager->getAllAsArray();
  }

  /**
   * Get all group visibility.
   *
   * @return array
   *   An array of group visibilities containing the PluginInstances.
   */
  public function getAllGroupVisibility(): array {
    return $this->visibilityManager->getAllAsArray();
  }

  /**
   * Get the groupPermission object, will create a new one if needed.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to get the group permission object for.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission|null
   *   The (new) group permission object, returns NULL if something went wrong.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function getGroupPermissionObject(GroupInterface $group): ?GroupPermission {
    /** @var \Drupal\group_permissions\Entity\GroupPermission $groupPermission */
    $groupPermission = $this->groupPermManager->getGroupPermission($group);

    if ($groupPermission === NULL) {
      // Create the entity.
      $groupPermission = GroupPermission::create([
        'gid' => $group->id(),
        'permissions' => $this->getDefaultGroupTypePermissions($group->getGroupType()),
        'status' => 1,
      ]);
    }
    else {
      $groupPermission->setNewRevision(TRUE);
    }

    return $groupPermission;
  }

  /**
   * Add role permissions to the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $groupPermission
   *   The group permission object to add the permissions to.
   * @param string $role
   *   The role to add the permissions to.
   * @param array $rolePermissions
   *   The permissions to add to the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission
   *   The group permission object with the updated permissions.
   */
  private function addRolePermissionsToGroup(GroupPermission $groupPermission, string $role, array $rolePermissions): GroupPermission {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (!array_key_exists($role, $permissions) || !in_array($permission, $permissions[$role], TRUE)) {
        $permissions[$role][] = $permission;
      }
    }
    $groupPermission->setPermissions($permissions);
    return $groupPermission;
  }

  /**
   * Remove role permissions from the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $groupPermission
   *   The group permission object to set the permissions to.
   * @param string $role
   *   The role to remove the permissions from.
   * @param array $rolePermissions
   *   The permissions to remove from the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission
   *   The group permission object with the updated permissions.
   */
  private function removeRolePermissionsFromGroup(GroupPermission $groupPermission, string $role, array $rolePermissions): GroupPermission {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (array_key_exists($role, $permissions) || in_array($permission, $permissions[$role], TRUE)) {
        $permissions[$role] = array_diff($permissions[$role], [$permission]);
      }
    }
    $groupPermission->setPermissions($permissions);
    return $groupPermission;
  }

}
