<?php

/**
 * @file
 * Contains \Drupal\resource\ResourceTypeAccessControlHandler.
 */

namespace Drupal\resource;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the resource type entity type.
 *
 * @see \Drupal\resource\Entity\ResourceType
 */
class ResourceTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view resource');
        break;

      case 'delete':
        return parent::checkAccess($entity, $operation, $account)->cacheUntilEntityChanges($entity);
        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
        break;
    }
  }

}
