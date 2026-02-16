<?php

declare(strict_types = 1);

namespace Drupal\entity_version;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining entity version settings for content entities.
 */
interface EntityVersionSettingsInterface extends ConfigEntityInterface {

  /**
   * Gets the entity type ID this config applies to.
   *
   * @return string
   *   Returns the target entity type id.
   */
  public function getTargetEntityTypeId(): string;

  /**
   * Sets the entity type this config applies to.
   *
   * @param string $target_entity_type_id
   *   The entity type id.
   *
   * @return $this
   *   Returns an EntityVersionSettingsInterface instance.
   */
  public function setTargetEntityTypeId(string $target_entity_type_id): EntityVersionSettingsInterface;

  /**
   * Gets the bundle this config applies to.
   *
   * @return string
   *   Returns the name of the target bundle.
   */
  public function getTargetBundle(): string;

  /**
   * Sets the bundle this config applies to.
   *
   * @param string $target_bundle
   *   The bundle.
   *
   * @return $this
   *   Returns an EntityVersionSettingsInterface instance.
   */
  public function setTargetBundle(string $target_bundle): EntityVersionSettingsInterface;

  /**
   * Gets the target version field machine name.
   *
   * @return string
   *   Return the target version field machine name.
   */
  public function getTargetField(): string;

  /**
   * Sets the target version field to read the versions from.
   *
   * @param string $target_field
   *   The target version field machine name.
   *
   * @return $this
   *   Returns an EntityVersionSettingsInterface instance.
   */
  public function setTargetField(string $target_field): EntityVersionSettingsInterface;

}
