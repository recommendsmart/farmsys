<?php

namespace Drupal\commerceg_order\Plugin;

use Drupal\group\Plugin\GroupContentPermissionProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines permissions for the order group content enabler plugin.
 */
class OrderPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    $plugin_id,
    array $definition
  ) {
    $instance = parent::createInstance($container, $plugin_id, $definition);

    // Orders do not implement the `EntityOwnerInterface`. They do have a
    // creator however and we want to define ownership-based permissions
    // e.g. view own orders and view any order etc.
    $instance->implementsOwnerInterface = TRUE;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = parent::buildPermissions();

    $prefix = 'Cart:';
    $plugin_id = $this->pluginId;

    // Cart permissions.
    // @I Define cart permissions only if `commerceg_cart` is enabled
    //    type     : improvement
    //    priority : low
    //    labels   : permission
    $permissions["view any $plugin_id cart"] = $this->buildPermission(
      "$prefix View any cart"
    );
    $permissions["view own $plugin_id cart"] = $this->buildPermission(
      "$prefix View own carts"
    );
    $permissions["update any $plugin_id cart"] = $this->buildPermission(
      "$prefix Update any cart"
    );
    $permissions["update own $plugin_id cart"] = $this->buildPermission(
      "$prefix Update own carts"
    );

    // Checkout permissions.
    // @I Define checkout permissions only if `commerceg_checkout` is enabled
    //    type     : improvement
    //    priority : low
    //    labels   : permission
    $permissions["checkout any $plugin_id cart"] = $this->buildPermission(
      "$prefix Checkout any cart"
    );
    $permissions["checkout own $plugin_id cart"] = $this->buildPermission(
      "$prefix Checkout own carts"
    );

    return $permissions;
  }

}
