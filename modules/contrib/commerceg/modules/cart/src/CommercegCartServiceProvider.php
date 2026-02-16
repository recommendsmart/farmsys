<?php

namespace Drupal\commerceg_cart;

use Drupal\commerceg_cart\Hook\Context\EntitySave;
use Drupal\commerceg_cart\Hook\Context\FormAlter;
use Drupal\commerceg_cart\Hook\Context\QueryAlter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services for non-required modules.
 */
class CommercegCartServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (!isset($modules['commerceg_context'])) {
      return;
    }

    $container
      ->register('commerceg_cart.hook.context_entity_save', EntitySave::class)
      ->addArgument(new Reference('current_user'))
      ->addArgument(new Reference('config.factory'))
      ->addArgument(new Reference('commerceg_context.manager'))
      ->addArgument(new Reference('entity_type.manager'));

    $container
      ->register('commerceg_cart.hook.context_form_alter', FormAlter::class)
      ->addArgument(new Reference('config.factory'))
      ->addArgument(new Reference('commerceg_context.manager'))
      ->addArgument(new Reference('string_translation'));

    $container
      ->register('commerceg_cart.hook.context_query_alter', QueryAlter::class)
      ->addArgument(new Reference('current_user'))
      ->addArgument(new Reference('config.factory'))
      ->addArgument(new Reference('commerceg_context.manager'))
      ->addArgument(new Reference('entity_type.manager'));
  }

}
