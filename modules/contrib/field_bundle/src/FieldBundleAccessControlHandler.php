<?php

namespace Drupal\field_bundle;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for field bundle items.
 *
 * @ingroup field_bundle_access
 */
class FieldBundleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $entity_type_id = $entity->getEntityTypeId();
    if ($account->hasPermission("administer $entity_type_id")) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\field_bundle\FieldBundleInterface $field_bundle */
    $field_bundle = $entity;
    $config_id = $field_bundle->bundle();
    $is_owner = ($account->id() && $account->id() === $field_bundle->getOwnerId());
    switch ($operation) {
      case 'view':
        if ($account->hasPermission("view any $entity_type_id")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission("view any $config_id $entity_type_id")) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($field_bundle);
        }
        if ($is_owner && ($account->hasPermission("view own $entity_type_id") || $account->hasPermission("view own $config_id $entity_type_id"))) {
          return AccessResult::allowed()
            ->cachePerUser()
            ->addCacheableDependency($field_bundle);
        }
        if ($field_bundle->isPublished()) {
          $access_result = AccessResult::allowedIfHasPermissions($account, ["view $entity_type_id", "view $config_id $entity_type_id"], 'OR')
            ->cachePerPermissions()
            ->addCacheableDependency($field_bundle);
          if (!$access_result->isAllowed()) {
            $access_result->setReason("The 'view $entity_type_id' or 'view $config_id $entity_type_id' permission is required when the field bundle item is published.");
          }
        }
        else {
          $access_result = AccessResult::neutral()
            ->cachePerPermissions()
            ->addCacheableDependency($field_bundle)
            ->setReason("The user must be the owner and the 'view own $entity_type_id' or 'view own $config_id $entity_type_id' permission is required when the field bundle item is unpublished.");
        }
        return $access_result;

      case 'update':
        if ($account->hasPermission("update any $entity_type_id")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission("update any $config_id $entity_type_id")) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($field_bundle);
        }
        if ($account->hasPermission("update own $entity_type_id") || $account->hasPermission("update own $config_id $entity_type_id")) {
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($field_bundle);
        }
        return AccessResult::neutral("The following permissions are required: 'update own $config_id $entity_type_id' OR 'update any $config_id $entity_type_id'.")->cachePerPermissions();

      case 'delete':
        if ($account->hasPermission("delete any $entity_type_id")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission("delete any $config_id $entity_type_id")) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($field_bundle);
        }
        if ($account->hasPermission("delete own $entity_type_id") || $account->hasPermission("delete own $config_id $entity_type_id")) {
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($field_bundle);
        }
        return AccessResult::neutral("The following permissions are required: 'delete own $config_id $entity_type_id' OR 'delete any $config_id $entity_type_id'.")->cachePerPermissions();

      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      'administer field_bundle',
      'create field_bundle',
      'create ' . (string) $entity_bundle . ' field_bundle',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
