<?php

namespace Drupal\basket\Plugin\Basket\Discount;

use Drupal\basket\Plugins\Discount\BasketDiscountInterface;
use Drupal\Core\Url;

/**
 * Plugin discounts from the total amount of the cart.
 *
 * @BasketDiscount(
 *          id        = "discount_range",
 *          name      = "Discount on the total amount of the order",
 * )
 */
class DiscountRange implements BasketDiscountInterface {

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
   * Получаем ссылку на редактирование.
   */
  public function settingsLink() {
    return [
      '#type'            => 'link',
      '#title'        => $this->basket->Translate()->t('Settings page'),
      '#url'            => new Url('basket.admin.pages', [
        'page_type'        => 'settings-discount_range',
      ], [
        'attributes'    => [
          'class'            => ['button--link target'],
        ],
        'query'            => \Drupal::destination()->getAsArray(),
      ]),
    ];
  }

  /**
   * Скидка на отдельную позицию корзины.
   */
  public function discountItem($item) {
    $percent = 0;
    $config = $this->basket->getSettings('discount_range', 'config');
    if (!empty($config)) {
      $totalSum = $this->basket->cart()->getTotalSum(TRUE, TRUE);
      if ($this->basket->currency()->getCurrent(TRUE) != $this->basket->currency()->getCurrent(FALSE)) {
        $currencyCurrent = $this->basket->currency()->getCurrent(FALSE);
        $this->basket->currency()->priceConvert($totalSum, $currencyCurrent, TRUE);
      }
      if (!empty($totalSum)) {
        foreach ($config as $row) {
          if (!empty($row['min']) && $row['min'] <= $totalSum && !empty($row['percent'])) {
            if (!empty($row['max'])) {
              if ($row['max'] >= $totalSum) {
                $percent = $row['percent'];
              }
            }
            else {
              $percent = $row['percent'];
            }
          }
        }
      }
    }
    return $percent;
  }

}
