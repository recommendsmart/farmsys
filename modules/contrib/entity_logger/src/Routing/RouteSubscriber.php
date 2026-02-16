<?php

namespace Drupal\entity_logger\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Entity Logger routes.
 *
 * @see \Drupal\entity_logger\Controller\EntityLoggerController
 * @see \Drupal\entity_logger\Plugin\Derivative\EntityLoggerLocalTask
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getEntityLoggerRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.entity_logger", $route);
      }
    }
  }

  /**
   * Gets the entity logger route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityLoggerRoute(EntityTypeInterface $entity_type) {
    if ($entity_logger_template = $entity_type->getLinkTemplate('entity-logger')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_logger_template);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\entity_logger\Controller\EntityLoggerController::log',
          '_title_callback' => '\Drupal\entity_logger\Controller\EntityLoggerController::pageTitle',
        ])
        ->addRequirements([
          '_permission' => 'view entity log entries',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_entity_logger_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
    return NULL;
  }

}
