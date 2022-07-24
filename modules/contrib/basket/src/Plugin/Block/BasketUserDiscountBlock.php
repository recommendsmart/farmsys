<?php

namespace Drupal\basket\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * User discount block.
 *
 * @Block(
 *   id = "basket_user_discount",
 *   admin_label = @Translation("Basket user discount percent"),
 *   category = @Translation("Basket user discount percent"),
 * )
 */
class BasketUserDiscountBlock extends BlockBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set basketDiscount.
   *
   * @var Drupal\basket\Plugins\Discount\BasketDiscountManager
   */
  protected $basketDiscount;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::service('Basket');
    $this->basketDiscount = \Drupal::service('BasketDiscount');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme'        => 'basket_user_discount',
      '#info'            => [
        'percent'        => $this->getMaxPercent(),
      ],
      '#prefix'        => '<div id="basket_user_discount_wrap">',
      '#suffix'        => '</div>',
      '#cache'        => [
        'max-age'        => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxPercent() {
    $items = $this->basket->Cart()->getItemsInBasket();
    $userDiscounts = [0];
    if (empty($items)) {
      $items[] = (object) [];
    }
    if (!empty($items)) {
      foreach ($items as $item) {
        $discounts = $this->basketDiscount->getDiscounts($item);
        $max = max($discounts);
        $userDiscounts[$max] = $max;
      }
    }
    return max($userDiscounts);
  }

}
