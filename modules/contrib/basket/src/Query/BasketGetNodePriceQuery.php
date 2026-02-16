<?php

namespace Drupal\basket\Query;

/**
 * Determination of prices for goods according to the settings.
 *
 * @deprecated in basket:2.0.0 and is removed from basket:3.0.0.
 * Use \Drupal::getContainer()->get('BasketQuery').
 */
class BasketGetNodePriceQuery {

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getPriceQuery($keyPriceField, $entityId).
   */
  public static function getQuery($keyPriceField = 'MIN', $entityId = NULL) {
    return \Drupal::getContainer()->get('BasketQuery')->getPriceQuery($keyPriceField, $entityId);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->isPriceConvert().
   */
  public static function isConvert() {
    return \Drupal::getContainer()->get('BasketQuery')->isPriceConvert();
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->priceViewsJoin($view, $keyPriceField, $filter).
   */
  public static function viewsJoin(&$view, $keyPriceField = 'MIN', $filter = []) {
    \Drupal::getContainer()->get('BasketQuery')->priceViewsJoin($view, $keyPriceField, $filter);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->priceViewsJoinSort($view, $order, $keyPriceField, $filter).
   */
  public static function viewsJoinSort(&$view, $order, $keyPriceField = 'MIN', $filter = []) {
    \Drupal::getContainer()->get('BasketQuery')->priceViewsJoinSort($view, $order, $keyPriceField, $filter);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getNodePriceMin($entity, $keyPriceField, $filter).
   */
  public static function getNodePriceMin($entity, $keyPriceField = 'MIN', $filter = []) {
    return \Drupal::getContainer()->get('BasketQuery')->getNodePriceMin($entity, $keyPriceField, $filter);
  }

}
