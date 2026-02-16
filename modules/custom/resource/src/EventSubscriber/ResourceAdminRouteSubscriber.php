<?php

/**
 * @file
 * Contains \Drupal\resource\EventSubscriber\ResourceAdminRouteSubscriber.
 */

namespace Drupal\resource\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _admin_route for specific resource-related routes.
 */
class ResourceAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ResourceAdminRouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('resource.settings')->get('resource_use_admin_theme')) {
      foreach ($collection->all() as $route) {
        if ($route->hasOption('_resource_operation_route')) {
          $route->setOption('_admin_route', TRUE);
        }
      }
    }
  }

}
