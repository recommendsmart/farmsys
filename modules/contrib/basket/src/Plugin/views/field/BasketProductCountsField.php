<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Item balances field.
 *
 * @ViewsField("basket_product_counts_field")
 */
class BasketProductCountsField extends FieldPluginBase {
  
  /**
   * @var object
   */
  protected $basket;
  
  /**
   * @var object
   */
  protected $basketQuery;
  
  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketQuery = \Drupal::getContainer()->get('BasketQuery');
  }
  
  /**
   * Called to add the field to a query.
   */
  public function query() {
    // We don't need to modify query for this particular example.
    $this->basketQuery->qtyViewsJoin($this);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->basketQuery->qtyViewsJoinSort($this, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#type'         => 'inline_template',
      '#template'     => '<div class="basket_count"><span class="count">{{ counts|round(2) }}</span></div>',
      '#context'      => [
        'counts'        => $values->basket_node_counts ?? 0
      ],
    ];
  }

}
