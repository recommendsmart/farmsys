<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\EntityFormBuilderInterface;

/**
 * Trait for Flow-related components making use of the entity form builder.
 */
trait EntityFormBuilderTrait {

  /**
   * The service name of the entity form builder.
   *
   * @var string
   */
  protected static $entityFormBuilderServiceName = 'entity.form_builder';

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected EntityFormBuilderInterface $entityFormBuilder;

  /**
   * Set the entity form builder.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function setEntityFormBuilder(EntityFormBuilderInterface $entity_form_builder): void {
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * Get the entity form builder.
   *
   * @return \Drupal\Core\Entity\EntityFormBuilderInterface
   *   The entity form builder.
   */
  public function getEntityFormBuilder(): EntityFormBuilderInterface {
    if (!isset($this->entityFormBuilder)) {
      $this->entityFormBuilder = \Drupal::service(self::$entityFormBuilderServiceName);
    }
    return $this->entityFormBuilder;
  }

}
