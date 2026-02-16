<?php

namespace Drupal\content_as_config\Form;

use Drupal\content_as_config\Controller\EntityControllerBase;
use Drupal\content_as_config\Controller\MenuLinksController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements ContentImportExportInterface for menu link content.
 */
trait MenuLinksImportExportTrait {

  /**
   * {@inheritdoc}
   */
  public function getController(ContainerInterface $container): EntityControllerBase {
    return MenuLinksController::create($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'menu_link_content';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $info): string {
    return $info['title'];
  }

}
