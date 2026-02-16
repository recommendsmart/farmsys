<?php

namespace Drupal\field_bundle\QueryAccess;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;

/**
 * Query access handler for field bundles.
 *
 * Requires the contrib Entity API module to be installed in order to be usable.
 *
 * @see https://www.drupal.org/project/entity
 *
 * @ingroup field_bundle_access
 */
class FieldBundleQueryAccessHandler extends QueryAccessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function buildConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();

    if ($account->hasPermission("administer $entity_type_id")) {
      // The user has full access to all operations, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    $conditions = NULL;
    if ($operation == 'view') {
      $view_conditions = [];
      if ($account->hasPermission("$operation any $entity_type_id")) {
        // The user has full view access to all items, no conditions needed.
        $conditions = new ConditionGroup('OR');
        $conditions->addCacheContexts(['user.permissions']);
        return $conditions;
      }

      $published_key = $this->entityType->getKey('published');
      if ($view_published_conditions = $this->buildEntityConditions($operation, $account)) {
        $published_conditions = new ConditionGroup('AND');
        $published_conditions->addCacheContexts(['user.permissions']);
        $published_conditions->addCondition($view_published_conditions);
        $published_conditions->addCondition($published_key, '1');
        $view_conditions[] = $published_conditions;
      }
      if ($owner_conditions = $this->buildEntityOwnerConditions($operation, $account)) {
        $view_conditions[] = $owner_conditions;
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
      // The user doesn't have access to any field bundle items.
      // Falsify the query to ensure no results are returned.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      $conditions->alwaysFalse();
    }

    return $conditions;
  }

}
