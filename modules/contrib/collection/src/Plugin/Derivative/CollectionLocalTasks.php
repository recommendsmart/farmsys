<?php

namespace Drupal\collection\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for collection items.
 */
class CollectionLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route_provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CollectionLocalTasks.
   *
   * @param Drupal\Core\Routing\RouteProvider $route_provider
   *   The route_provider service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(RouteProvider $route_provider, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Add the 'Items' tab to Collection entities. The path for this tab is
    // `collection/%collection/items`, which is the listing path for
    // collection_item entities. There are multiple possible routes for this
    // path: The native collection route for the collection_item entity
    // (entity.collection_item.collection); an optional views-based listing; or
    // any additional, custom views that define a page display with the same
    // path. Since these routes have the same fitness, the last one returned
    // will be used. In effect this will use the views display if the view is
    // enabled, or the native listbuilder-based route if the view is disabled.
    $collection_item_routes = $this->routeProvider->getRoutesByPattern('collection/%collection/items');
    $collection_item_route = array_key_last($collection_item_routes->all());

    if ($collection_item_routes) {
      $this->derivatives['entity.collection.items'] = $base_plugin_definition;
      $this->derivatives['entity.collection.items']['title'] = 'Items';
      $this->derivatives['entity.collection.items']['route_name'] = $collection_item_route;
      $this->derivatives['entity.collection.items']['base_route'] = 'entity.collection.canonical';
      $this->derivatives['entity.collection.items']['weight'] = 1;
    }

    /* @var $route \Symfony\Component\Routing\Route */
    foreach ($this->routeProvider->getAllRoutes() as $route) {
      // Add Collections tabs to supported content entities. The
      // _collection_entity_type_id option is added when the dynamic routes are
      // generated in CollectionDynamicRoutes::routes(). The base_route should
      // exist because the _collection_entity_type_id option is only added when
      // there is a canonical link template. The name should always be
      // 'entity.$entity_type_id.canonical' according to
      // https://www.drupal.org/project/drupal/issues/2720215#comment-12270831
      if ($entity_type_id = $route->getOption('_collection_entity_type_id')) {
        $this->derivatives['entity.' . $entity_type_id . '.collections'] = $base_plugin_definition;
        $this->derivatives['entity.' . $entity_type_id . '.collections']['title'] = 'Collections';
        $this->derivatives['entity.' . $entity_type_id . '.collections']['route_name'] = 'collection.' . $entity_type_id . '.collections';
        $this->derivatives['entity.' . $entity_type_id . '.collections']['base_route'] = 'entity.' . $entity_type_id . '.canonical';
        $this->derivatives['entity.' . $entity_type_id . '.collections']['weight'] = 2;
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
