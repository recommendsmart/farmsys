<?php

namespace Drupal\entity_logger;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for EntityLogEntryStorage class.
 */
interface EntityLogEntryStorageInterface extends ContentEntityStorageInterface {

  /**
   * Delete entity_log_entry entities related to a given target entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity to delete entity_log_entry entities for.
   */
  public function deleteForTargetEntity(EntityInterface $target_entity);

}
