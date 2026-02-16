<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\entity_version_history\Controller\EntityVersionHistoryController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity version history routes.
 */
class EntityVersionHistoryRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityVersionHistoryRouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type->hasLinkTemplate('entity-version-history')) {
        continue;
      }

      $route = new Route(
        $entity_type->getLinkTemplate('entity-version-history'),
        [
          '_controller' => EntityVersionHistoryController::class . '::historyOverview',
          '_title_callback' => EntityVersionHistoryController::class . '::title',
        ],
        [
          '_custom_access' => EntityVersionHistoryController::class . '::checkAccess',
        ],
        [
          '_entity_type_id' => $entity_type_id,
          '_admin_route' => TRUE,
          'parameters' => [
            $entity_type_id => [
              'type' => 'entity:' . $entity_type_id,
            ],
          ],
        ]
      );
      $route_name = "entity.$entity_type_id.entity_version_history";
      $collection->add($route_name, $route);
    }
  }

}
