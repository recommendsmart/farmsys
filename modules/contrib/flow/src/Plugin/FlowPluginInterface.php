<?php

namespace Drupal\flow\Plugin;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface for all Flow-related plugins.
 */
interface FlowPluginInterface {

  /**
   * Get the entity type ID of the subject.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId(): string;

  /**
   * Get the entity type of the subject.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type.
   */
  public function getEntityType(): EntityTypeInterface;

  /**
   * Get the bundle of the subject.
   *
   * @return string
   *   The bundle.
   */
  public function getEntityBundle(): string;

  /**
   * Get the entity bundle config of the subject, if any.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   *   The entity bundle config, or NULL if the subject has no bundle config.
   */
  public function getEntityBundleConfig(): ?ConfigEntityInterface;

}
