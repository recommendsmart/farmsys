<?php

namespace Drupal\basket\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Drupal\views\Ajax\ViewAjaxResponse;

/**
 * {@inheritdoc}
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 0];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof ViewAjaxResponse) {
      $view = $response->getView();
      if ($view->id() == 'basket' && $view->current_display == 'block_2') {
        $commands = &$response->getCommands();
        foreach ($commands as $key => $command) {
          if ($command['command'] == 'viewsScrollTop') {
            unset($commands[$key]);
          }
          if ($command['command'] == 'insert' && $command['method'] && !empty($command['data']) && strpos($command['data'], 'data-basketid="') !== FALSE) {
            $commands[$key]['selector'] = '[data-basketid="view_wrap-' . $view->id() . '-' . $view->current_display . '"]';
          }
        }
      }
    }
  }

}
