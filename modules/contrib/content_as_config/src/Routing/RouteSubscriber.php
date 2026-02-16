<?php

namespace Drupal\content_as_config\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Conditionally adds feed routes if feeds module is enabled.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->moduleHandler->moduleExists('feeds')) {
      $route = new Route(
        '/admin/structure/content-as-config/feeds-export',
        [
          '_form' => '\Drupal\content_as_config\Form\FeedsExportForm',
          '_title' => 'Export feeds',
        ],
        ['_permission' => 'administer site configuration']
      );
      $collection->add('content_as_config.feeds.export', $route);

      $route = new Route(
        '/admin/structure/content-as-config/feeds-import',
        [
          '_form' => '\Drupal\content_as_config\Form\FeedsImportForm',
          '_title' => 'Import feeds',
        ],
        ['_permission' => 'administer site configuration']
      );
      $collection->add('content_as_config.feeds.import', $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}
