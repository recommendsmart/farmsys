<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Order ID field.
 *
 * @ViewsField("basket_order_id")
 */
class BasketOrderID extends FieldPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  const NEW_COLOR = '#00A337';

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
    $id = $this->basket->orders($this->getValue($values))->getId();
    if ($this->basket->orders($this->getValue($values))->isNew()) {
      return $this->basket->textColor(
        $id,
        $this::NEW_COLOR
      );
    }
    else {
      return $id;
    }
  }

}
