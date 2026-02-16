<?php

namespace Drupal\basket\Plugins\Discount;

/**
 * Provides an interface for all Basket Discount plugins.
 */
interface BasketDiscountInterface {

  /**
   * Get a link to edit.
   */
  public function settingsLink();

  /**
   * Discount for a single item in the basket.
   */
  public function discountItem($item);

}
