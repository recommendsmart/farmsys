<?php

namespace Drupal\designs_view\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\designs_view\Form\Ajax\DesignHandler;
use Drupal\views_ui\ViewUI;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a route subscriber to ensure Views UI design forms properly load.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route.
   */
  public function __construct(CurrentRouteMatch $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::CONTROLLER] = ['onController', -1000];
    return $events;
  }

  /**
   * Allow the views ui to add additional forms.
   *
   * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
   *   The controller event.
   */
  public function onController(ControllerEvent $event) {
    $route = $this->routeMatch->getCurrentRouteMatch();
    $route_name = $route->getRouteName();

    // We are extending the views_ui route, so ensure the class exists.
    if ($route_name === 'views_ui.form_design') {
      ViewUI::$forms['design'] = DesignHandler::class;
    }
  }

}
