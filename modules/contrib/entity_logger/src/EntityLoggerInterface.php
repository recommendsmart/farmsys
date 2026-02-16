<?php

namespace Drupal\entity_logger;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Interface for EntityLogger service.
 */
interface EntityLoggerInterface {

  /**
   * Add a log entry to a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add the log to.
   * @param string $message
   *   The log message.
   * @param array $context
   *   The log message context variables.
   * @param int $severity
   *   The log message severity.
   *
   * @return \Drupal\entity_logger\Entity\EntityLogEntryInterface|null
   *   The created log entry entity.
   */
  public function log(EntityInterface $entity, string $message, array $context = [], int $severity = RfcLogLevel::INFO);

}
