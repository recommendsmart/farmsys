<?php

namespace Drupal\group_permissions;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupRoleSynchronizerInterface;
use Drupal\group_permissions\Entity\GroupPermission;

/**
 * Service to handle custom group permissions.
 */
class GroupPermissionsManager implements GroupPermissionsManagerInterface {

  /**
   * The array of the group custom permissions.
   *
   * @var array
   */
  protected $customPermissions = [];

  /**
   * The array of the group permissions objects.
   *
   * @var array
   */
  protected $groupPermissions = [];

  /**
   * The array of the outsider group roles.
   *
   * @var array
   */
  protected $outsiderRoles = [];

  /**
   * The array of the group roles.
   *
   * @var array
   */
  protected $groupRoles = [];

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizerInterface
   */
  protected $groupRoleSynchronizer;

  /**
   * Group role storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupRoleStorage;

  /**
   * Group permissions storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupPermissionStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache_backend, EntityTypeManagerInterface $entity_type_manager, GroupRoleSynchronizerInterface $group_role_synchronizer) {
    $this->cacheBackend = $cache_backend;
    $this->groupRoleSynchronizer = $group_role_synchronizer;
    $this->groupRoleStorage = $entity_type_manager->getStorage('group_role');
    $this->groupPermissionStorage = $entity_type_manager->getStorage('group_permission');
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomPermissions(GroupInterface $group) {
    $group_id = $group->id();
    $this->customPermissions[$group_id] = [];
    if (empty($this->customPermissions[$group_id])) {
      $cid = "custom_group_permissions:$group_id";
      $data_cached = $this->cacheBackend->get($cid);
      if (!$data_cached) {
        /** @var \Drupal\group_permissions\Entity\GroupPermission $group_permission */
        $group_permission = $this->loadByGroup($group);
        if (!empty($group_permission) && $group_permission->isPublished()) {
          $this->groupPermissions[$group_id] = $group_permission;
          $tags = [];
          $tags[] = "group:$group_id";
          $tags[] = "group_permission:{$group_permission->id()}";
          $this->customPermissions[$group_id] = $group_permission->getPermissions();
          // Store the tree into the cache.
          $this->cacheBackend->set($cid, $this->customPermissions[$group_id], CacheBackendInterface::CACHE_PERMANENT, $tags);
        }
      }
      else {
        $this->customPermissions[$group_id] = $data_cached->data;
      }
    }

    return $this->customPermissions[$group_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermission(GroupInterface $group) {
    $group_id = $group->id();
    if (empty($this->groupPermissions[$group_id])) {
      $this->groupPermissions[$group_id] = $this->loadByGroup($group);
    }

    return $this->groupPermissions[$group_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutsiderRoles(GroupInterface $group, AccountInterface $account) {
    $group_type = $group->getGroupType();
    $group_type_id = $group_type->id();
    if (empty($this->outsiderRoles[$group_type_id])) {
      $account_roles = $account->getRoles(TRUE);
      foreach ($account_roles as $role) {
        $advanced_outsider_role_id = $this->groupRoleSynchronizer->getGroupRoleId($group_type_id, $role);
        $outsider_roles[] = $this->groupRoleStorage->load($advanced_outsider_role_id);
      }
      $outsider_roles[$group_type->getOutsiderRoleId()] = $group_type->getOutsiderRole();
      $this->outsiderRoles[$group_type_id] = $outsider_roles;
    }

    return $this->outsiderRoles[$group_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return $this->groupPermissionStorage->getAllActive();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupRoles(GroupInterface $group) {
    if (empty($this->groupRoles[$group->id()])) {
      $group_type_id = $group->getGroupType()->id();
      $properties = [
        'group_type' => $group_type_id,
        'permissions_ui' => TRUE,
      ];

      $roles = $this->groupRoleStorage->loadByProperties($properties);

      uasort($roles, '\Drupal\group\Entity\GroupRole::sort');

      $outsider_roles = $this->groupRoleStorage->loadSynchronizedByGroupTypes([$group_type_id]);
      $this->groupRoles[$group->id()] = array_merge($roles, $outsider_roles);
    }

    return $this->groupRoles[$group->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group) {
    return $this->groupPermissionStorage->loadByGroup($group);
  }

}
