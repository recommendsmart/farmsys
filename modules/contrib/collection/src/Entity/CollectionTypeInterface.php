<?php

namespace Drupal\collection\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Collection type entities.
 */
interface CollectionTypeInterface extends ConfigEntityInterface {

  /**
   * Get the allowed collection item types. Optionally, pass in an entity type
   * and/or bundle name to filter the return values.
   *
   * @param string $entity_type_id
   *   (optional) The machine name of the entity type (e.g. `node`)
   *
   * @param string $bundle
   *   (optional) The bundle name (e.g. `article`)
   *
   * @return array
   *   An array of allowed collection item types.
   */
  public function getAllowedCollectionItemTypes($entity_type_id, $bundle);

  /**
   * Get the allowed bundles for the collection item types property.
   *
   * @param string $entity_type_id
   *   (optional) The machine name of the entity type (e.g. `node`)
   *
   * @return array
   *   An array of bundles, keyed by entity type.
   */
  public function getAllowedEntityBundles($entity_type_id);

}
