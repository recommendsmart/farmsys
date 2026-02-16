<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * User's individual discount field.
 *
 * @ViewsField("basket_user_percent_field")
 */
class BasketUserPercentField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['templateTwig'] = ['default' => '{{ percent }} %'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['templateTwig'] = [
      '#type'             => 'textarea',
      '#title'            => 'Render template (Twig)',
      '#rows'             => 1,
      '#default_value'    => $this->options['templateTwig'],
      '#description'      => implode('<br/>', [
        '{{ percent }}',
      ]),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $join = Views::pluginManager('join')->createInstance('standard', [
      'type'          => 'LEFT',
      'table'         => 'basket_user_percent',
      'field'         => 'uid',
      'left_table'    => 'users_field_data',
      'left_field'    => 'uid',
      'operator'      => '=',
    ]);
    $this->query->addRelationship($this->field, $join, 'users_field_data');
    $this->query->addField($this->field, 'percent', $this->field . '_percent');
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->query->addOrderBy($this->field, 'percent', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#type'         => 'inline_template',
      '#template'     => $this->options['templateTwig'],
      '#context'      => [
        'percent'       => !empty($values->{$this->field . '_percent'}) ? $values->{$this->field . '_percent'} : 0,
      ],
    ];
  }

}
