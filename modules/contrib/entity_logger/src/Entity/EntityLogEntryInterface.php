<?php

namespace Drupal\entity_logger\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for the EntityLogEntry entity type.
 */
interface EntityLogEntryInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Get the target entity this log belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The target entity.
   */
  public function getTargetEntity();

  /**
   * Set the target entity this log belongs to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The target entity.
   *
   * @return \Drupal\entity_logger\Entity\EntityLogEntryInterface
   *   The called log entry entity.
   */
  public function setTargetEntity(EntityInterface $entity);

  /**
   * Get the severity level for this log message.
   *
   * @see \Drupal\Core\Logger\RfcLogLevel
   *
   * @return int
   *   The severity level.
   */
  public function getSeverity();

  /**
   * Set the severity level for this log message.
   *
   * @param int $severity
   *   The severity level.
   *
   * @see \Drupal\Core\Logger\RfcLogLevel
   *
   * @return \Drupal\entity_logger\Entity\EntityLogEntryInterface
   *   The called log entry entity.
   */
  public function setSeverity($severity);

  /**
   * Get the log message.
   *
   * @return string
   *   The log message.
   */
  public function getMessage();

  /**
   * Set the log message.
   *
   * @param string $message
   *   The log message.
   * @param array $context
   *   The log message context variables.
   *
   * @return \Drupal\entity_logger\Entity\EntityLogEntryInterface
   *   The called log entry entity.
   */
  public function setMessage($message, array $context = []);

  /**
   * Get the log message context variables.
   *
   * @return array
   *   The log message context variables.
   */
  public function getContext();

  /**
   * Set the log message context variables.
   *
   * @param array $context
   *   The log message context variables.
   *
   * @return \Drupal\entity_logger\Entity\EntityLogEntryInterface
   *   The called log entry entity.
   */
  public function setContext(array $context);

  /**
   * Returns the creation timestamp.
   *
   * @todo Remove and use the new interface when #2833378 is done.
   * @see https://www.drupal.org/node/2833378
   *
   * @return int
   *   Creation timestamp of the log entry.
   */
  public function getCreatedTime();

  /**
   * Sets the creation timestamp.
   *
   * @todo Remove and use the new interface when #2833378 is done.
   * @see https://www.drupal.org/node/2833378
   *
   * @param int $timestamp
   *   The entity creation timestamp.
   *
   * @return \Drupal\entity_logger\Entity\EntityLogEntryInterface
   *   The called log entry entity.
   */
  public function setCreatedTime($timestamp);

}
