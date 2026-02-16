<?php

namespace Drupal\element;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\element\Permissions\ElementPermissions;

/**
 * Access controller for the Element entity.
 *
 * @see \Drupal\element\Entity\Element.
 */
class ElementAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\element\Entity\ElementInterface $entity */
    $type = $entity->bundle();

    $hasAdminPermission = $account->hasPermission('administer element entities');
    $isOwner = $entity->getOwnerId() == $account->id();

    switch ($operation) {
      case 'view':
        $mayView = $entity->isPublished() && $account->hasPermission(ElementPermissions::buildPermissionId($type, 'view'));

        return AccessResult::allowedIf($hasAdminPermission || $mayView)
          ->cachePerPermissions()
          ->addCacheableDependency($entity)
          ->orIf($this->checkAccess($entity, 'view individual', $account));

      case 'update':
        $hasUpdateAllPermission = $account->hasPermission(ElementPermissions::buildPermissionId($type, 'update'));
        $hasUpdateOwnPermission = $account->hasPermission(ElementPermissions::buildPermissionId($type, 'update own'));

        $updateAllowed = $hasAdminPermission
          || $hasUpdateAllPermission
          || ($hasUpdateOwnPermission && $isOwner);

        return AccessResult::allowedIf($updateAllowed)
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      case 'delete':
        $hasDeleteAllPermission = $account->hasPermission(ElementPermissions::buildPermissionId($type, 'delete'));
        $hasDeleteOwnPermission = $account->hasPermission(ElementPermissions::buildPermissionId($type, 'delete own'));

        $deleteAllowed = $hasAdminPermission ||
          $hasDeleteAllPermission ||
          ($hasDeleteOwnPermission && $isOwner);

        return AccessResult::allowedIf($deleteAllowed)
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      // This is not a standard operation, we've invented this for having access
      // control on the page for viewing a element on its own page.
      case 'view individual':
        return AccessResult::allowedIf($hasAdminPermission)
          ->cachePerPermissions()
          ->orIf($this->checkAccess($entity, 'update', $account))
          ->orIf($this->checkAccess($entity, 'delete', $account));

      default:
        // Unknown operation.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $createPermission = ElementPermissions::buildPermissionId($entity_bundle, 'create');
    return AccessResult::allowedIfHasPermission($account, 'administer element entities')
      ->cachePerPermissions()
      ->orIf(AccessResult::allowedIfHasPermission($account, $createPermission));
  }

}
