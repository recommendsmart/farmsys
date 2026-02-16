<?php

namespace Drupal\commerceg\Configure;

/**
 * Trait for making it easy for installers to provide default group roles.
 *
 * Modules of the Commerce Group ecosystem may provide default roles that should
 * be installed upon module installation. For example, the Commerce B2B module
 * provides a Manager and a Purchaser role, and the Commerce Product Group
 * module provides a Customer and a Group Customer role. The
 * `RoleInstallerInterface` together with the `RoleInstallerTrait` make it easy
 * to add the required roles upon module installation.
 */
trait RoleInstallerTrait {

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
   * Returns the ID of the group type (bundle) for the default roles.
   *
   * @return string
   *   The ID of the group type.
   */
  abstract protected function getDefaultRolesGroupTypeId();

  /**
   * Installs the default group roles.
   *
   * The default group roles are the ones required for general functionality
   * provided by the installer's module.
   *
   * @see \Drupal\commerceg\Configure\RoleInstallerInterface::installDefaultRoles()
   */
  public function installDefaultRoles() {
    foreach ($this->getDefaultRolesInfo() as $id => $role) {
      $this->installRole(
        $id,
        $role['label'],
        $this->getDefaultRolesGroupTypeId()
      );
    }
  }

  /**
   * Installs the role with the given ID and label.
   *
   * @param string $id
   *   The ID of the group role to create, including the group type prefix
   *   e.g. `commerceg_organization-manager`.
   * @param string $label
   *   The label of the group role. It will be used only if the group role is
   *   created i.e. if the role already exists and it has a different label, it
   *   will not be corrected.
   * @param string $group_type_id
   *   The ID of the group type to install the role on.
   */
  protected function installRole($id, $label, $group_type_id) {
    if ($this->isRoleInstalled($id)) {
      return;
    }

    $role = $this->groupRoleStorage->create([
      'id' => $id,
      'label' => $label,
      'group_type' => $group_type_id,
    ]);
    $role->save();
  }

  /**
   * Returns the default role entities required by the installer's module.
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
   * Returns whether the group role with the given ID exists or not.
   *
   * @param string $id
   *   The ID of the group role to check, including the group type prefix
   *   e.g. `commerceg_organization-manager`.
   *
   * @return bool
   *   TRUE if the group role exists, FALSE otherwise.
   */
  protected function isRoleInstalled($id) {
    return $this->groupRoleStorage->load($id) ? TRUE : FALSE;
  }

}
