<?php

namespace Drupal\access_records;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for access records.
 *
 * @ingroup access_records_access
 */
class AccessRecordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $entity_type_id = $entity->getEntityTypeId();
    if ($account->hasPermission("administer $entity_type_id")) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\access_records\AccessRecordInterface $access_record */
    $access_record = $entity;
    $config_id = $access_record->bundle();
    $is_owner = ($account->id() && $account->id() === $access_record->getOwnerId());
    switch ($operation) {
      case 'view':
        if ($account->hasPermission("view any $entity_type_id")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission("view any $config_id $entity_type_id")) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($access_record);
        }
        if ($is_owner && ($account->hasPermission("view own $entity_type_id") || $account->hasPermission("view own $config_id $entity_type_id"))) {
          return AccessResult::allowed()
            ->cachePerUser()
            ->addCacheableDependency($access_record);
        }
        if ($access_record->isPublished()) {
          $access_result = AccessResult::allowedIfHasPermissions($account,
            ["view $entity_type_id", "view $config_id $entity_type_id"], 'OR')
            ->cachePerPermissions()
            ->addCacheableDependency($access_record);
          if (!$access_result->isAllowed()) {
            $access_result->setReason("The 'view $entity_type_id' or 'view $config_id $entity_type_id' permission is required when the access record is enabled.");
          }
        }
        else {
          $access_result = AccessResult::neutral()
            ->cachePerPermissions()
            ->addCacheableDependency($access_record)
            ->setReason("The user must be the owner and the 'view own $entity_type_id' or 'view own $config_id $entity_type_id' permission is required when the access record is not enabled.");
        }
        return $access_result;

      case 'update':
        if ($account->hasPermission("update any $entity_type_id")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission("update any $config_id $entity_type_id")) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($access_record);
        }
        if ($account->hasPermission("update own $entity_type_id") || $account->hasPermission("update own $config_id $entity_type_id")) {
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($access_record);
        }
        return AccessResult::neutral("The following permissions are required: 'update own $config_id $entity_type_id' OR 'update any $config_id $entity_type_id'.")->cachePerPermissions();

      case 'delete':
        if ($account->hasPermission("delete any $entity_type_id")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission("delete any $config_id $entity_type_id")) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($access_record);
        }
        if ($account->hasPermission("delete own $entity_type_id") || $account->hasPermission("delete own $config_id $entity_type_id")) {
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($access_record);
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
      'administer access_record',
      'create access_record',
      'create ' . (string) $entity_bundle . ' access_record',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
