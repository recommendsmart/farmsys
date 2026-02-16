<?php

namespace Drupal\entity_logger\Event;

/**
 * Defines events for entity_logger module.
 */
final class EntityLoggerEvents {

  /**
   * Event fired to gather entity types that are available for logging.
   *
   * @Event
   *
   * @see \Drupal\entity_logger\Event\EntityLoggerAvailableEntityTypesEvent
   *
   * @var string
   */
  const AVAILABLE_ENTITY_TYPES = 'entity_logger.available_entity_types';

}
