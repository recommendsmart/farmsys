<?php

namespace Drupal\commerce_funds\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler to provide amount.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_funds_amount")
 */
class MoneyAmount extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    foreach ($values as $key => $row) {
      if ($row->_entity->bundle() == 'conversion') {
        $values[$key]->transaction_currency_symbol = $row->_entity->getCurrency()->getSymbol();
        $values[$key]->transaction_from_currency_symbol = $row->_entity->getFromCurrency()->getSymbol();
      }
      else {
        $values[$key]->transaction_currency_symbol = $row->_entity->getCurrency()->getSymbol();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $field_name = isset($this->options['entity_field']) ? $this->options['entity_field'] : $this->options['id'];
    if (isset($values->transaction_from_currency_symbol) && $field_name) {
      if ($field_name == 'brut_amount') {
        $value = $this->getValue($values);
        $symbol = $values->transaction_from_currency_symbol;
      }
      elseif ($field_name == 'net_amount') {
        $value = $this->getValue($values);
        $symbol = $values->transaction_currency_symbol;
      }
      else {
        $value = $this->getValue($values);
        $symbol = '';
      }
    }
    else {
      $options = $this->options;
      $value = number_format($this->getValue($values), 2, $options['decimal'], $options['separator']);
      $symbol = $values->transaction_currency_symbol;
    }

    return $symbol . $value;
  }

}
