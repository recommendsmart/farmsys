<?php

namespace Drupal\content_as_config\Form;

use Drupal\content_as_config\Controller\BlockContentController;
use Drupal\content_as_config\Controller\EntityControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements ContentImportExportInterface for block content.
 */
trait BlockContentImportExportTrait {

  /**
   * {@inheritdoc}
   */
  public function getController(ContainerInterface $container): EntityControllerBase {
    return BlockContentController::create($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'block_content';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $info): string {
    return $info['info'];
  }

}
