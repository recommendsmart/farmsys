<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filter by SID.
 *
 * @ViewsFilter("basket_cart_sid")
 */
class BasketCartSid extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->query->addWhere(NULL, 'basket.sid', \Drupal::service('Basket')->Cart()->getSid());
    // Node publich.
    $join = Views::pluginManager('join')->createInstance('standard', [
      'type'           => 'INNER',
      'table'          => 'node_field_data',
      'field'          => 'nid',
      'left_table'     => 'basket',
      'left_field'     => 'nid',
      'operator'       => '=',
      'extra'            => [[
        'field'            => 'status',
        'value'            => 1,
      ]],
    ]);
    $this->query->addRelationship($this->realField . 'basketNode', $join, 'basket');
    // Group.
    $this->query->addField('basket', 'nid', 'n_nid', ['function' => 'groupby']);
    $this->query->addGroupBy("basket.nid");
  }

}
