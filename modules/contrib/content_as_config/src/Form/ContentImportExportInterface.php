<?php

namespace Drupal\content_as_config\Form;

use Drupal\content_as_config\Controller\EntityControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A common interface for import/export forms.
 */
interface ContentImportExportInterface {

  /**
   * Fetches the controller for this entity type.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DI container.
   *
   * @return \Drupal\content_as_config\Controller\EntityControllerBase
   *   An instance of the controller
   */
  public function getController(ContainerInterface $container): EntityControllerBase;

  /**
   * Returns the machine name of the entity type.
   *
   * @return string
   *   The machine name of the entity type.
   */
  public function getEntityType(): string;

  /**
   * Gets the label for an entity from its stored configuration.
   *
   * @param array $info
   *   The stored configuration for an entity.
   *
   * @return string
   *   The label for that entity.
   */
  public function getLabel(array $info): string;

}
