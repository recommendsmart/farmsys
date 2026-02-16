<?php

namespace Drupal\basket\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("basket_cart_result")
 */
class BasketCartResult extends AreaPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set basket.
   *
   * @var Drupal\basket\Plugins\Delivery\BasketDeliveryManager
   */
  protected $basketDelivery;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::service('Basket');
    $this->basketDelivery = \Drupal::service('BasketDelivery');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    return [
      '#theme'        => 'basket_views_cart_data',
      '#info'         => [
        'Cart'          => $this->basket->Cart(),
        'view'          => $this->view,
        'delivery'      => $this->basketDelivery->getDeliveryInfo(),
      ],
    ];
  }

}
