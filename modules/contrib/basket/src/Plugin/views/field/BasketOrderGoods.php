<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Order item quantity field.
 *
 * @ViewsField("basket_order_goods")
 */
class BasketOrderGoods extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $goods = $this->getValue($values);
    return !empty($goods) ? round($goods, 6) : 0;
  }

}
