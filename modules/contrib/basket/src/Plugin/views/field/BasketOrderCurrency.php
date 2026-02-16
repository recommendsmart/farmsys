<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Order currency field.
 *
 * @ViewsField("basket_order_currency")
 */
class BasketOrderCurrency extends FieldPluginBase {

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
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $currency = $this->basket->currency()->load($this->getValue($values));
    if (!empty($currency->name)) {
      return $this->basket->translate()->trans($currency->name);
    }
    return $this->getValue($values);
  }

}
