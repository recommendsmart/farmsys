<?php

namespace Drupal\content_as_config\Form;

use Drupal\content_as_config\Controller\EntityControllerBase;
use Drupal\content_as_config\Controller\TaxonomiesController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements ContentImportExportInterface for taxonomy term content.
 */
trait TaxonomiesImportExportTrait {

  /**
   * {@inheritdoc}
   */
  public function getController(ContainerInterface $container): EntityControllerBase {
    return TaxonomiesController::create($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $info): string {
    return $info['name'];
  }

}
