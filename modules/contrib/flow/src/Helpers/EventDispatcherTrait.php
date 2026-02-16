<?php

namespace Drupal\flow\Helpers;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Trait for components making use of the event dispatcher.
 */
trait EventDispatcherTrait {

  /**
   * The service name of the event dispatcher.
   *
   * @var string
   */
  protected static $eventDispatcherServiceName = 'event_dispatcher';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Set the event dispatcher.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function setEventDispatcher(EventDispatcherInterface $event_dispatcher): void {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Get the event dispatcher.
   *
   * @return \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  public function getEventDispatcher(): EventDispatcherInterface {
    if (!isset($this->eventDispatcher)) {
      $this->eventDispatcher = \Drupal::service(self::$eventDispatcherServiceName);
    }
    return $this->eventDispatcher;
  }

}
