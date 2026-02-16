<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Trait for Flow-related components making use of the entity repository.
 */
trait EntityRepositoryTrait {

  /**
   * The service name of the entity repository.
   *
   * @var string
   */
  protected static $entityRepositoryServiceName = 'entity.repository';

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Set the entity repository.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function setEntityRepository(EntityRepositoryInterface $entity_repository): void {
    $this->entityRepository = $entity_repository;
  }

  /**
   * Get the entity repository.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  public function getEntityRepository(): EntityRepositoryInterface {
    if (!isset($this->entityRepository)) {
      $this->entityRepository = \Drupal::service(self::$entityRepositoryServiceName);
    }
    return $this->entityRepository;
  }

}
