<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the role inheritance entity type.
 *
 * @see \Drupal\subgroup\Entity\RoleInheritance
 */
class RoleInheritanceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // We do not allow role inheritance entities to be updated as the only thing
    // you could update are the target or source role and doing so would open up
    // a large can of worms. Instead, delete the original one and create a new
    // one.
    if ($operation == 'update') {
      return AccessResult::forbidden('Role inheritance entities may not be updated after creation.');
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
