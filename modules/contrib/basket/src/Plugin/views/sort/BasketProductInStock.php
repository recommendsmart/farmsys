<?php

namespace Drupal\basket\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\Views;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("basket_product_in_stock")
 */
class BasketProductInStock extends SortPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    if (empty($this->query->relationships['basket_product_in_stock'])) {
      if (!empty($subQueryCount = \Drupal::getContainer()
        ->get('BasketQuery')
        ->getQtyQuery())) {
        $join = Views::pluginManager('join')->createInstance('standard', [
          'type' => 'LEFT',
          'table' => $subQueryCount,
          'field' => 'nid',
          'left_table' => 'node_field_data',
          'left_field' => 'nid',
          'operator' => '=',
        ]);
        $this->query->addRelationship('basket_product_in_stock', $join, 'node_field_data');
      }
    }
    if (!empty($this->query->relationships['basket_product_in_stock'])) {
      $this->query->addOrderBy(NULL, 'IF((basket_product_in_stock.count*1) > 0, 1, 0)', $this->options['order'], 'basket_product_in_stock');
    }
    $this->view->basketProductInStock = TRUE;
  }
}
