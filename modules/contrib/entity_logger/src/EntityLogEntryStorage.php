<?php

namespace Drupal\entity_logger;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage handler for entity_log_entry entities.
 */
class EntityLogEntryStorage extends SqlContentEntityStorage implements EntityLogEntryStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function deleteForTargetEntity(EntityInterface $target_entity) {
    $ids = $this->getQuery()
      ->accessCheck(FALSE)
      ->condition('target_entity.target_type', $target_entity->getEntityTypeId())
      ->condition('target_entity.target_id', $target_entity->id())
      ->execute();

    $entries = $this->loadMultiple($ids);
    $this->delete($entries);
  }

}
