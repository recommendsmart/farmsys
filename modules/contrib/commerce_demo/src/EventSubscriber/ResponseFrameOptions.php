<?php declare(strict_types = 1);

namespace Drupal\commerce_demo\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ResponseFrameOptions implements EventSubscriberInterface {

  /**
   * Set header 'Content-Security-Policy' to allow embedding in iFrame.
   */
  public function setHeaderContentSecurityPolicy(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->remove('X-Frame-Options');
    $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' *", FALSE);

    if ($response instanceof HtmlResponse) {
      $response->headers->set('P3P', 'CP="ALL ADM DEV PSAi COM OUR OTRo STP IND ONL"');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Response: set header content security policy.
    $events[KernelEvents::RESPONSE][] = ['setHeaderContentSecurityPolicy'];

    return $events;
  }

}
