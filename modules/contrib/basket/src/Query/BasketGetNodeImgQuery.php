<?php

namespace Drupal\basket\Query;

/**
 * Determination of the product image according to the settings.
 *
 * @deprecated in basket:2.0.0 and is removed from basket:3.0.0.
 * Use \Drupal::getContainer()->get('BasketQuery').
 */
class BasketGetNodeImgQuery {

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getImgQuery().
   */
  public static function getQuery($entityId = NULL) {
    return \Drupal::getContainer()->get('BasketQuery')->getImgQuery($entityId);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->ImgViewsJoin($view).
   */
  public static function viewsJoin(&$view) {
    \Drupal::getContainer()->get('BasketQuery')->ImgViewsJoin($view);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getNodeImgFirst($entity).
   */
  public static function getNodeImgFirst($entity) {
    return \Drupal::getContainer()->get('BasketQuery')->getNodeImgFirst($entity);
  }

  /**
   * @deprecated in basket:2.0.0 and is removed from basket:3.0.0. Use
   * \Drupal::getContainer()->get('BasketQuery')->getDefFid($entity).
   */
  public static function getDefFid($entity) {
    return \Drupal::getContainer()->get('BasketQuery')->getDefFid($entity);
  }

}
