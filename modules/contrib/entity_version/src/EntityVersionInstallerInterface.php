<?php

namespace Drupal\entity_version;

/**
 * Implemented by entity version installers.
 */
interface EntityVersionInstallerInterface {

  /**
   * Install the entity version field on an entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $bundles
   *   Array of entity bundle names.
   * @param array $default_value
   *   The default value of the entity version field.
   */
  public function install(string $entity_type = 'node', array $bundles = [], array $default_value = []): void;

}
