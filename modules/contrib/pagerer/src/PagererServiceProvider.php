<?php

namespace Drupal\pagerer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the "pager.*" services.
 */
class PagererServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides "pager.*" services to use Pagerer.
    $definition = $container->getDefinition('pager.parameters');
    $definition->setClass(PagererParameters::class)
      ->setArguments([
        new Reference('request_stack'),
        new Reference('config.factory'),
      ]);
    $definition = $container->getDefinition('pager.manager');
    $definition->setClass(PagererManager::class)
      ->setArguments([
        new Reference('pager.parameters'),
        new Reference('config.factory'),
      ]);
  }

}
