<?php

namespace Drupal\commerceg\Configure;

/**
 * Interface for a configurator that provides default group roles.
 *
 * Modules of the Commerce Group ecosystem may provide default roles that should
 * be configured upon module installation. For example, the Commerce B2B module
 * provides a Manager and a Purchaser role, and the Commerce Product Group
 * module provides a Customer and a Group Customer role. The
 * `RoleConfiguratorInterface` together with the `RoleConfiguratorTrait` make it
 * easy to configure the required roles upon module installation.
 */
interface RoleConfiguratorInterface {

  /**
   * Configures the default group roles.
   *
   * It includes:
   *   - Resets the permissions for the default group roles to the default
   *     values. Permissions that are not related to functionality provided by
   *     module (as defined by the `getDefaultRolePermissions` method) will be
   *     left unchanged.
   */
  public function configureDefaultRoles();

}
