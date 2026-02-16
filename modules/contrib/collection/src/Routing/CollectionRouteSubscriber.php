<?php

namespace Drupal\collection\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class CollectionRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $route_collection) {
    foreach ($route_collection->all() as $route) {
      $path = $route->getPath();

      // Ensure that this path uses the admin theme.
      // @todo Remove if https://www.drupal.org/i/2719797 lands.
      if ($path === '/collection/{collection}/items') {
        $route->setOption('_admin_route', 'TRUE');
      }

      // Ensure that the {collection} parameter is upcast. It may not be if
      // this path is a View.
      // @todo Remove if https://www.drupal.org/i/2528166 lands.
      if (strpos($path, '{collection}') !== FALSE) {
        $options = $route->getOptions();

        if (!isset($options['parameters']['collection'])) {
          $options['parameters']['collection']['type'] = 'entity:collection';
          $route->setOptions($options);
        }
      }
    }
  }

}
