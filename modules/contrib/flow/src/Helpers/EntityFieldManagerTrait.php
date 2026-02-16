<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Trait for Flow-related components making use of the entity field manager.
 */
trait EntityFieldManagerTrait {

  /**
   * The service name of the entity field manager.
   *
   * @var string
   */
  protected static $entityFieldManagerServiceName = 'entity_field.manager';

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * Set the entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entity_field_manager): void {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  public function getEntityFieldManager(): EntityFieldManagerInterface {
    if (!isset($this->entityFieldManager)) {
      $this->entityFieldManager = \Drupal::service(self::$entityFieldManagerServiceName);
    }
    return $this->entityFieldManager;
  }

}
