<?php

namespace Drupal\commerceg\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase as CoreFormBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for all forms.
 *
 * The base class provided by core does not properly inject dependencies, which
 * is something that we want. This is unlikely to change any time soon as it
 * will break pretty much most forms. We therefore provide a base class here
 * that can be used by the Commerce Group ecosystem.
 *
 * @I Create form base classes with dependency injection in core
 *    type     : task
 *    priority : low
 *    labels   : coding-standards, external
 */
abstract class FormBase extends CoreFormBase {

  /**
   * Constructs a new FormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    RedirectDestinationInterface $redirect_destination,
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    TranslationInterface $string_translation
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->redirectDestination = $redirect_destination;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('redirect.destination'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('string_translation')
    );
  }

}
