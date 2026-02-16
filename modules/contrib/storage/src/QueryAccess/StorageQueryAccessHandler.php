<?php

namespace Drupal\storage\QueryAccess;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;

/**
 * Query access handler for Storage entities.
 *
 * Requires the contrib Entity API module to be installed in order to be usable.
 *
 * @see https://www.drupal.org/project/entity
 *
 * @ingroup storage_access
 */
class StorageQueryAccessHandler extends QueryAccessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function buildConditions($operation, AccountInterface $account) {
    if ($account->hasPermission("administer storage entities")) {
      // The user has full access to all operations, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    $conditions = NULL;
    if ($operation == 'view') {
      $view_conditions = [];

      $owner_key = $this->entityType->hasKey('owner') ? $this->entityType->getKey('owner') : $this->entityType->getKey('uid');
      $published_key = $this->entityType->getKey('published');
      if ($view_published_conditions = $this->buildEntityConditions('view published', $account)) {
        $published_conditions = new ConditionGroup('AND');
        $published_conditions->addCacheContexts(['user.permissions']);
        $published_conditions->addCondition($view_published_conditions);
        $published_conditions->addCondition($published_key, '1');
        $view_conditions[] = $published_conditions;
      }
      if ($view_unpublished_conditions = $this->buildEntityConditions('view unpublished', $account)) {
        $unpublished_conditions = new ConditionGroup('AND');
        $unpublished_conditions->addCacheContexts(['user.permissions']);
        $unpublished_conditions->addCondition($view_unpublished_conditions);
        $unpublished_conditions->addCondition($published_key, '0');
        $view_conditions[] = $unpublished_conditions;
      }
      if ($view_own_unpublished_conditions = $this->buildEntityViewOwnUnpublishedConditions($account)) {
        $own_unpublished_conditions = new ConditionGroup('AND');
        $own_unpublished_conditions->addCacheContexts(['user']);
        $own_unpublished_conditions->addCondition($view_own_unpublished_conditions);
        $own_unpublished_conditions->addCondition($owner_key, $account->id());
        $own_unpublished_conditions->addCondition($published_key, '0');
        $view_conditions[] = $own_unpublished_conditions;
      }

      $num_view_conditions = count($view_conditions);
      if ($num_view_conditions === 1) {
        $conditions = reset($view_conditions);
      }
      elseif ($num_view_conditions > 1) {
        $conditions = new ConditionGroup('OR');
        foreach ($view_conditions as $view_condition) {
          $conditions->addCondition($view_condition);
        }
      }
    }
    else {
      $conditions = $this->buildEntityOwnerConditions($operation, $account);
    }

    if (!$conditions) {
      // The user doesn't have access to any Storage items.
      // Falsify the query to ensure no results are returned.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      $conditions->alwaysFalse();
    }

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityOwnerConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $owner_key = $this->entityType->hasKey('owner') ? $this->entityType->getKey('owner') : $this->entityType->getKey('uid');
    $bundle_key = $this->entityType->getKey('bundle');

    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.permissions']);
    // Any $entity_type permission.
    if ($account->hasPermission("$operation any storage entities")) {
      // The user has full access, no conditions needed.
      return $conditions;
    }

    // Own $entity_type permission.
    if ($account->hasPermission("$operation own storage entities")) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition($owner_key, $account->id());
    }

    $bundles = array_keys($this->bundleInfo->getBundleInfo($entity_type_id));
    $bundles_with_any_permission = [];
    $bundles_with_own_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("$operation any $bundle storage entities")) {
        $bundles_with_any_permission[] = $bundle;
      }
      if ($account->hasPermission("$operation own $bundle storage entities")) {
        $bundles_with_own_permission[] = $bundle;
      }
    }
    // Any $bundle permission.
    if ($bundles_with_any_permission) {
      $conditions->addCondition($bundle_key, $bundles_with_any_permission);
    }
    // Own $bundle permission.
    if ($bundles_with_own_permission) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition((new ConditionGroup('AND'))
        ->addCondition($owner_key, $account->id())
        ->addCondition($bundle_key, $bundles_with_own_permission)
      );
    }

    return $conditions->count() ? $conditions : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $bundle_key = $this->entityType->getKey('bundle');

    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.permissions']);
    // The $entity_type permission.
    if ($account->hasPermission("$operation storage entities")) {
      // The user has full access, no conditions needed.
      return $conditions;
    }

    $bundles = array_keys($this->bundleInfo->getBundleInfo($entity_type_id));
    $bundles_with_any_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("$operation $bundle storage entities")) {
        $bundles_with_any_permission[] = $bundle;
      }
    }
    // The $bundle permission.
    if ($bundles_with_any_permission) {
      $conditions->addCondition($bundle_key, $bundles_with_any_permission);
    }

    return $conditions->count() ? $conditions : NULL;
  }

  /**
   * Builds a conditions subgroup for viewing own unpublished Storage entities.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\entity\QueryAccess\ConditionGroup|null
   *   The conditions or NULL, if the user does not have any permission.
   */
  protected function buildEntityViewOwnUnpublishedConditions(AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $bundle_key = $this->entityType->getKey('bundle');

    $conditions = new ConditionGroup('OR');
    // Any $entity_type permission.
    if ($account->hasPermission("view own unpublished storage entities")) {
      // The user has full access, no conditions needed.
      return $conditions;
    }

    $bundles = array_keys($this->bundleInfo->getBundleInfo($entity_type_id));
    $bundles_with_own_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("view own unpublished $bundle storage entities")) {
        $bundles_with_own_permission[] = $bundle;
      }
    }

    if ($bundles_with_own_permission) {
      $conditions->addCondition($bundle_key, $bundles_with_own_permission);
    }

    return $conditions->count() ? $conditions : NULL;
  }

}
