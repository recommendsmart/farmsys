<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Order amount field.
 *
 * @ViewsField("basket_order_price")
 */
class BasketOrderPrice extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];

      $sub_query = \Drupal::database()->select('basket_orders', 'b');
      $sub_query->fields('b', ['nid']);
      // basket_currency.
      $sub_query->innerJoin('basket_currency', 'bc', 'bc.id = b.currency');
      $sub_query->innerJoin('basket_currency', 'bc_def', 'bc_def.default = 1');
      // ---
      $sub_query->addExpression('b.price*(bc.rate/bc_def.rate)', 'price');

      $join = Views::pluginManager('join')->createInstance('standard', [
        'type'           => 'INNER',
        'table'          => $sub_query,
        'field'          => 'nid',
        'left_table'     => 'node_field_data',
        'left_field'     => 'nid',
        'operator'       => '=',
      ]);
      $rel = $this->query->addRelationship('sub_query', $join, 'node_field_data');
      $this->query->addOrderBy('sub_query', 'price', $order, $this->field_alias, $params);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['templatePrice'] = ['default' => '{{ price|basket_number_format(2, \',\', \' \') }}'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['templatePrice'] = [
      '#type'             => 'textarea',
      '#title'            => 'Render template (Twig)',
      '#rows'             => 2,
      '#default_value'    => $this->options['templatePrice'],
      '#description'      => implode('<br/>', [
        '{{ price }}',
      ]),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#type'     => 'inline_template',
      '#template' => $this->options['templatePrice'],
      '#context'  => [
        'price'     => $this->getValue($values),
      ],
    ];
  }

}
