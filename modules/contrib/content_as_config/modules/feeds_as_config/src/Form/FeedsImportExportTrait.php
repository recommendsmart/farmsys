<?php

namespace Drupal\feeds_as_config\Form;

use Drupal\feeds_as_config\Controller\FeedsController;
use Drupal\content_as_config\Controller\EntityControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements ContentImportExportInterface for feed content.
 */
trait FeedsImportExportTrait {

  /**
   * {@inheritdoc}
   */
  public function getController(ContainerInterface $container): EntityControllerBase {
    return FeedsController::create($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'feeds_feed';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $info): string {
    return $info['title'];
  }

}
