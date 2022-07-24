<?php

namespace Drupal\basket\Query;

/**
 * Calculating the total amount of user purchases.
 *
 * @deprecated in basket:2.0.0 and is removed from basket:3.0.0.
 * Use \Drupal::getContainer()->get('BasketQuery').
 */
class BasketGetUserSumQuery {

  /**
   * GetQuery.
   *
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getUserSumQuery().
   */
  public static function getQuery() {
    return \Drupal::getContainer()->get('BasketQuery')->getUserSumQuery();
  }

  /**
   * ViewsJoin.
   *
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->userSumViewsJoin($view).
   */
  public static function viewsJoin(&$view) {
    \Drupal::getContainer()->get('BasketQuery')->userSumViewsJoin($view);
  }

  /**
   * ClickSort.
   *
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->userSumViewsJoinSort($view, $order).
   */
  public static function clickSort(&$view, $order) {
    \Drupal::getContainer()->get('BasketQuery')->userSumViewsJoinSort($view, $order);
  }

}
