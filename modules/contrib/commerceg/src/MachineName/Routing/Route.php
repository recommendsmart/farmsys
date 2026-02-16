<?php

namespace Drupal\commerceg\MachineName\Routing;

/**
 * Holds IDs of routes.
 *
 * See https://github.com/krystalcode/drupal8-coding-standards/blob/master/Fields.md#field-name-constants
 */
class Route {

  /**
   * Holds the name of the route that lists a group's cart orders.
   */
  const GROUP_CARTS = 'view.commerceg_group_carts.page_1';

  /**
   * Holds the name of the route that lists a group's orders.
   */
  const GROUP_ORDERS = 'view.commerceg_group_orders.page_1';

  /**
   * Holds the name of the route that lists a group's products.
   */
  const GROUP_PRODUCTS = 'view.commerceg_group_products.page_1';

  /**
   * Holds the name of the route that lists a group's profiles.
   */
  const GROUP_PROFILES = 'view.commerceg_group_profiles.page_1';

}
