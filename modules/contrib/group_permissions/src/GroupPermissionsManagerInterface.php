<?php

namespace Drupal\group_permissions;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Group permissions manager interface.
 */
interface GroupPermissionsManagerInterface {

  /**
   * Helper function to get custom group permissions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Permissions array.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getCustomPermissions(GroupInterface $group);

  /**
   * Get group permission object.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission|null
   *   Group permission.
   */
  public function getGroupPermission(GroupInterface $group);

  /**
   * Get outsider roles.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   *
   * @return mixed
   *   List of outsider roles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOutsiderRoles(GroupInterface $group, AccountInterface $account);

  /**
   * Get all group permissions objects.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface[]
   *   Group permissions list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAll();

  /**
   * Get group roles.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Group roles.
   */
  public function getGroupRoles(GroupInterface $group);

  /**
   * Retrieves Group permission entity for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group content entities for.
   *
   * @return \Drupal\group\Entity\GroupPermissiontInterface|null
   *   The GroupPermission entity of given group OR NULL if not existing.
   */
  public function loadByGroup(GroupInterface $group);

}
