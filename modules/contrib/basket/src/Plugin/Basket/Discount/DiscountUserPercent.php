<?php

namespace Drupal\basket\Plugin\Basket\Discount;

use Drupal\basket\Plugins\Discount\BasketDiscountInterface;
use Drupal\Core\Url;

/**
 * Individual user discount plugin.
 *
 * @BasketDiscount(
 *          id        = "discount_user_percent",
 *          name      = "Individual user discount",
 * )
 */
class DiscountUserPercent implements BasketDiscountInterface {
  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * Get a link to edit.
   */
  public function settingsLink() {
    if ($this->basket->full('StatisticsBuyers')) {
      return [
        '#type'           => 'link',
        '#title'          => $this->basket->Translate()->t('Settings page'),
        '#url'            => new Url('basket.admin.pages', [
          'page_type'        => 'statistics-buyers',
        ], [
          'attributes'    => [
            'class'         => ['button--link target'],
            'target'        => '_blank',
          ],
        ]),
      ];
    }
    else {
      return [
        '#type'           => 'link',
        '#title'          => $this->basket->Translate()->t('Settings page'),
        '#url'            => new Url('view.user_admin_people.page_1', [], [
          'attributes'    => [
            'class'         => ['button--link target'],
            'target'        => '_blank',
          ],
        ]),
      ];
    }
  }

  /**
   * Discount for a single item in the basket.
   */
  public function discountItem($item) {
    return $this->basket->getCurrentUserPercent();
  }

}
