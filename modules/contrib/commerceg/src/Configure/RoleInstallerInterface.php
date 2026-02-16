<?php

namespace Drupal\commerceg\Configure;

/**
 * Interface for an installer that provides default group roles.
 *
 * Modules of the Commerce Group ecosystem may provide default roles that should
 * be installed upon module installation. For example, the Commerce B2B module
 * provides a Manager and a Purchaser role, and the Commerce Product Group
 * module provides a Customer and a Group Customer role. The
 * `RoleInstallerInterface` together with the `RoleInstallerTrait` make it easy
 * to add the required roles upon module installation.
 */
interface RoleInstallerInterface {

  /**
   * Installs the default group roles.
   *
   * The default group roles are the ones required for general functionality
   * provided by the installer's module.
   */
  public function installDefaultRoles();

}
