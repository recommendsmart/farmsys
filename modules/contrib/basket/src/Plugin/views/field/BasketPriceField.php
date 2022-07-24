<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Views;

/**
 * Item price field (sorting).
 *
 * @ViewsField("basket_price_field")
 */
class BasketPriceField extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (empty($this->options['click_sort_column'])) {
      return;
    }
    $this->ensureMyTable();
    $field_storage_definition = $this->getFieldStorageDefinition();
    $columnValue = $this->getTableMapping()->getFieldColumnName($field_storage_definition, $this->options['click_sort_column']);
    $columnCurrency = $this->getTableMapping()->getFieldColumnName($field_storage_definition, 'currency');

    $this->aliases[$columnValue] = $this->tableAlias . '.' . $columnValue;

    $sub_query = \Drupal::database()->select($this->tableAlias, $this->tableAlias);
    $sub_query->fields($this->tableAlias, ['entity_id']);
    // basket_currency.
    $sub_query->innerJoin('basket_currency', 'bc', 'bc.id = ' . $this->tableAlias . '.' . $columnCurrency);
    $sub_query->innerJoin('basket_currency', 'bc_def', 'bc_def.default = 1');
    // ---
    $sub_query->addExpression($this->tableAlias . '.' . $columnValue . '*(bc.rate/bc_def.rate)', 'price');
    // ---
    $join = Views::pluginManager('join')->createInstance('standard', [
      'type'          => 'LEFT',
      'table'         => $sub_query,
      'field'         => 'entity_id',
      'left_table'    => $this->tableAlias,
      'left_field'    => 'entity_id',
      'operator'      => '=',
    ]);
    $rel = $this->query->addRelationship($this->tableAlias . '_sub_query', $join, $this->tableAlias);
    $this->query->addOrderBy($this->tableAlias . '_sub_query', 'price', $order);
  }

}
