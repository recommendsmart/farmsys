<?php

namespace Drupal\subgroup\Plugin;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentAccessControlHandler;

/**
 * Provides access control for subgroup GroupContent entities.
 */
class SubgroupAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    $permission = $this->permissionProvider->getEntityCreatePermission();
    return $this->combinedPermissionCheck($group, $account, $permission, $return_as_object);
  }

}
