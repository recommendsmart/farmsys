<?php

namespace Drupal\basket\Query;

/**
 * Determination of product balances according to the settings.
 *
 * @deprecated in basket:2.0.0 and is removed from basket:3.0.0.
 * Use \Drupal::getContainer()->get('BasketQuery').
 */
class BasketGetNodeCountsQuery {

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getQtyQuery($entityId).
   */
  public static function getQuery($entityId = NULL) {
    return \Drupal::getContainer()->get('BasketQuery')->getQtyQuery($entityId);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->qtyViewsJoin($view).
   */
  public static function viewsJoin(&$view) {
    \Drupal::getContainer()->get('BasketQuery')->qtyViewsJoin($view);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->qtyViewsJoin($view).
   */
  public static function viewsJoinSort(&$view, $order) {
    \Drupal::getContainer()->get('BasketQuery')->qtyViewsJoinSort($view, $order);
  }
}
