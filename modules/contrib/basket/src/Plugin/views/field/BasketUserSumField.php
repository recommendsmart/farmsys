<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * The field for the total amount of the user's orders.
 *
 * @ViewsField("basket_user_sum_field")
 */
class BasketUserSumField extends FieldPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;
  /**
   * Set basketQuery.
   *
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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['templateTwig'] = ['default' => '{{ sum|number_format(2, \',\', \'\') }} {{ currency }}'];
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
        '{{ sum }}',
        '{{ currency }}',
      ]),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->basketQuery->userSumViewsJoin($this);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->basketQuery->userSumViewsJoinSort($this, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#type'         => 'inline_template',
      '#template'     => $this->options['templateTwig'],
      '#context'      => [
        'sum'           => !empty($values->{$this->field . '_total_sum'}) ? $values->{$this->field . '_total_sum'} : 0,
        'currency'      => $this->basket->Cart()->getCurrencyName(),
      ],
    ];
  }

}
