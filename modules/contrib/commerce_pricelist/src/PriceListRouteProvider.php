<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the price list entity.
 */
class PriceListRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    // Use addTitle instead of addBundleTitle because "Add product variation"
    // sounds more confusing than "Add price list".
    $route->setDefault('_title_callback', EntityController::class . '::addTitle');

    return $route;
  }

}
