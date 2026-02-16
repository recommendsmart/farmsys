<?php

namespace Drupal\collection\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks for collections.
 */
class DynamicLocalActions extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route_provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Constructs a new DynamicLocalActions.
   *
   * @param Drupal\Core\Routing\RouteProvider $route_provider
   *   The route_provider service.
   */
  public function __construct($route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // The new content and existing item links can appear on any route that
    // responds to the `collection/%collection/items` path.
    $collection_item_routes = $this->routeProvider->getRoutesByPattern('collection/%collection/items');
    $collection_item_route_names = array_keys($collection_item_routes->all());

    // Add the 'new content' action link to collection_item listings.
    // %todo: Only add if the collection allows nodes.
    $this->derivatives['entity.collection.new_content'] = $base_plugin_definition;
    $this->derivatives['entity.collection.new_content']['title'] = 'Add new content';
    $this->derivatives['entity.collection.new_content']['route_name'] = 'collection_item.new.node';
    $this->derivatives['entity.collection.new_content']['appears_on'] = $collection_item_route_names;

    // Add the 'existing content' action link to collection_item listings.
    $this->derivatives['entity.collection.add_form'] = $base_plugin_definition;
    $this->derivatives['entity.collection.add_form']['title'] = 'Add existing item';
    $this->derivatives['entity.collection.add_form']['route_name'] = 'entity.collection_item.add_page';
    $this->derivatives['entity.collection.add_form']['appears_on'] = $collection_item_route_names;

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
