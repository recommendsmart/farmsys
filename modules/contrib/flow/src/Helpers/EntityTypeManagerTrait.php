<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Trait for Flow-related components making use of the entity type manager.
 */
trait EntityTypeManagerTrait {

  /**
   * The service name of the entity type manager.
   *
   * @var string
   */
  protected static $entityTypeManagerServiceName = 'entity_type.manager';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Set the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager): void {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager(): EntityTypeManagerInterface {
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::service(self::$entityTypeManagerServiceName);
    }
    return $this->entityTypeManager;
  }

}
