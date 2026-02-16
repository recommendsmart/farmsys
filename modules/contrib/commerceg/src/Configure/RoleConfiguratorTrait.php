<?php

namespace Drupal\commerceg\Configure;

/**
 * Trait for making it easy for configurators to provide default group roles.
 *
 * Modules of the Commerce Group ecosystem may provide default roles that should
 * be configured upon module installation. For example, the Commerce B2B module
 * provides a Manager and a Purchaser role, and the Commerce Product Group
 * module provides a Customer and a Group Customer role. The
 * `RoleConfiguratorInterface` together with the `RoleConfiguratorTrait` make it
 * easy to configure the required roles upon module installation.
 */
trait RoleConfiguratorTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group role storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected $groupRoleStorage;

  /**
   * Returns the default group roles required for general functionality.
   *
   * @return array
   *   An associative array keyed by the role ID and containing an associative
   *   array with further information about the role. Supported information is:
   *   - id: The ID (machine name) of the role.
   *   - label: The label (human-friendly name) of the role.
   */
  abstract protected function getDefaultRolesInfo();

  /**
   * Returns the permissions for the default group roles.
   *
   * @return array
   *   An associative array keyed by the group role ID and containing an
   *   associative array with the permissions. The permissions array is keyed by
   *   the permission ID (machine name) and containing the value to set (TRUE or
   *   FALSE to set it as the new value, or NULL to keep the current value
   *   unchanged).
   */
  abstract protected function getDefaultRolePermissions();

  /**
   * Configures the default group roles.
   *
   * It includes:
   *   - Resets the permissions for the default group roles to the default
   *     values. Permissions that are not related to functionality provided by
   *     module (as defined by the `getDefaultRolePermissions` method) will be
   *     left unchanged.
   *
   * @see \Drupal\commerceg\Configure\RoleConfiguratorInterface::configureDefaultRoles()
   */
  public function configureDefaultRoles() {
    $this->configureRoles(
      $this->getDefaultRoles(),
      $this->getDefaultRolePermissions()
    );
  }

  /**
   * Resets the given role permissions to the given values.
   *
   * @param \Drupal\group\Entity\GroupRoleInterface[] $roles
   *   The group roles.
   * @param array $permissions
   *   An associative array keyed by the group role ID and containing an
   *   associative array with the permissions. The permissions array is keyed by
   *   the permission ID (machine name) and containing the value to set (TRUE or
   *   FALSE to set it as the new value, or NULL to keep the current value
   *   unchanged).
   */
  protected function configureRoles(array $roles, array $permissions) {
    foreach ($roles as $role) {
      if (!isset($permissions[$role->id()])) {
        continue;
      }

      $role->changePermissions($permissions[$role->id()]);
      $this->groupRoleStorage->save($role);
    }
  }

  /**
   * Returns the default role entities required by the configurator's module.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The default group roles.
   */
  protected function getDefaultRoles() {
    return $this->groupRoleStorage->loadMultiple(
      array_keys($this->getDefaultRolesInfo())
    );
  }

  /**
   * Replaces base permissions with all derivative ones per entity bundle.
   *
   * To make it easy to define the default permissions for all bundles of an
   * entity type at once, we define them using the base plugin ID e.g.
   * `commerceg_product:` as the plugin ID. We need to convert them to the real
   * ones that make use of each entity bundle e.g. `commerceg_product:default`.
   *
   * @param string $entity_type_id
   *   The ID of the entity type that the permissions are for.
   * @param string $base_plugin_id
   *   The base ID of the group content enabler plugin used to generate the
   *   per-bundle derivatives.
   * @param array $permissions
   *   An associative array keyed by the group role ID and containing an
   *   associative array with the permissions. The permissions array is keyed by
   *   the permission ID (machine name) and containing the value to set (TRUE or
   *   FALSE to set it as the new value, or NULL to keep the current value
   *   unchanged).
   *
   * @return array
   *   An associative array with the same structure as the given permissions,
   *   but with the placeholder (base plugin ID) containing permissions removed
   *   and the corresponding permissions per entity bundle added with the same
   *   values.
   */
  protected function buildDerivativePermissions(
    $entity_type_id,
    $base_plugin_id,
    array $permissions
  ) {
    $bundles = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    foreach ($permissions as &$role_permissions) {
      foreach ($role_permissions as $role_permission => $value) {
        if (strpos($role_permission, "{$base_plugin_id}:") === FALSE) {
          continue;
        }

        foreach ($bundles as $bundle) {
          $new_role_permission = str_replace(
            "{$base_plugin_id}:",
            "{$base_plugin_id}:{$bundle}",
            $role_permission
          );
          $role_permissions[$new_role_permission] = $value;
        }

        unset($role_permissions[$role_permission]);
      }
    }

    return $permissions;
  }

}
