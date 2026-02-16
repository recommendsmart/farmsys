<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Trait for components making use of the entity type bundle info service.
 */
trait EntityTypeBundleInfoTrait {

  /**
   * The service name of the entity type bundle info service.
   *
   * @var string
   */
  protected static $entityTypeBundleInfoServiceName = 'entity_type.bundle.info';

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Set the entity type bundle info service.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info): void {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Get the entity type bundle info service.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity type bundle info service.
   */
  public function getEntityTypeBundleInfo(): EntityTypeBundleInfoInterface {
    if (!isset($this->entityTypeBundleInfo)) {
      $this->entityTypeBundleInfo = \Drupal::service(self::$entityTypeBundleInfoServiceName);
    }
    return $this->entityTypeBundleInfo;
  }

}
