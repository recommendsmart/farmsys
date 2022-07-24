<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\Views;

/**
 * Filter by product name.
 *
 * @ViewsFilter("basket_filter_title_field")
 */
class BasketFilterTitleField extends StringFilter {

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    $join = Views::pluginManager('join')->createInstance('standard', [
      'type'       => 'LEFT',
      'table'      => 'node_field_data',
      'field'      => 'nid',
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'operator'   => '=',
    ]);
    $this->query->addRelationship($this->realField, $join, 'node_field_data');
    $field = "$this->realField.title";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
