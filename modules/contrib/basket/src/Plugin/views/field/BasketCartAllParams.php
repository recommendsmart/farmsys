<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * The field of additional parameters of the item in the cart.
 *
 * @ViewsField("basket_cart_all_params")
 */
class BasketCartAllParams extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addField('basket', 'nid', 'basket_row_nid', $params);
    $this->query->addField('basket', 'all_params', 'basket_row_all_params', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!empty($values->basket_row_all_params) && !empty($values->basket_row_nid)) {
      $params = \Drupal::getContainer()->get('Basket')->cart()->decodeParams($values->basket_row_all_params);
      return \Drupal::getContainer()->get('BasketParams')->getDefinitionParams($params, $values->basket_row_nid);
    }
    return [];
  }

}
