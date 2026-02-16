<?php

namespace Drupal\access_records\QueryAccess;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;

/**
 * Query access handler for access records.
 *
 * Requires the contrib Entity API module to be installed in order to be usable.
 *
 * @see https://www.drupal.org/project/entity
 *
 * @ingroup access_records_access
 */
class AccessRecordQueryAccessHandler extends QueryAccessHandlerBase {

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
    if ($account->hasPermission("$operation $entity_type_id") || $account->hasPermission("$operation any $entity_type_id")) {
      // The user has operational access to all items, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    $conditions = NULL;
    $record_conditions = [];
    if ($entity_conditions = $this->buildEntityConditions($operation, $account)) {
      $record_conditions[] = $entity_conditions;
    }
    if ($owner_conditions = $this->buildEntityOwnerConditions($operation, $account)) {
      $record_conditions[] = $owner_conditions;
    }

    $num_record_conditions = count($record_conditions);
    if ($num_record_conditions === 1) {
      $conditions = reset($record_conditions);
    }
    elseif ($num_record_conditions > 1) {
      $conditions = new ConditionGroup('OR');
      foreach ($record_conditions as $record_condition) {
        $conditions->addCondition($record_condition);
      }
    }

    if (!$conditions) {
      // The user doesn't have access to any access record.
      // Falsify the query to ensure no results are returned.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      $conditions->alwaysFalse();
    }

    return $conditions;
  }

}
