<?php

namespace Drupal\entity_logger\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Definition for event to gather entity types that are available for logging.
 */
class EntityLoggerAvailableEntityTypesEvent extends Event {

  /**
   * The entity types to be made available.
   *
   * @var array
   */
  protected $entityTypes;

  /**
   * EntityLoggerAvailableEntityTypesEvent constructor.
   *
   * @param array $entity_types
   *   The entity types to make available.
   */
  public function __construct(array $entity_types) {
    $this->entityTypes = $entity_types;
  }

  /**
   * Add an entity type ID to make it available for entity logger.
   *
   * @param string $entity_type
   *   The entity type ID to make available.
   */
  public function addEntityType(string $entity_type) {
    if (!in_array($entity_type, $this->entityTypes)) {
      $this->entityTypes[] = $entity_type;
    }
  }

  /**
   * Get entity types to make available for entity logger.
   *
   * @return array
   *   List of entity types.
   */
  public function getEntityTypes() {
    return $this->entityTypes;
  }

}
