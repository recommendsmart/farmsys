<?php

namespace Drupal\basket\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("basket_get_price_field")
 */
class BasketGetPriceField extends SortPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    \Drupal::getContainer()->get('BasketQuery')->priceViewsJoinSort($this, $this->options['order']);
  }

}
