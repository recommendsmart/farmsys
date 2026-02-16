<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Cart amount field.
 *
 * @ViewsField("basket_cart_sum")
 */
class BasketCartSum extends FieldPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addField('basket', 'id', 'basket_row_id', $params);
    $this->query->addField('basket', 'count', 'basket_row_count', $params);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['templatePrice'] = [
      'default' => '{% if discount %}
    {{ (sum-(sum/100*discount))|basket_number_format(2, \',\', \' \') }} {{ basket_t(currency) }}
{% else %}
    {{ sum|basket_number_format(2, \',\', \' \') }} {{ basket_t(currency) }}
{% endif %}',
    ];
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
        '{{ sum }}',
        '{{ currency }}',
        '{{ discount }}',
      ]),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!empty($values->basket_row_id)) {
      $price = $this->basket->cart()->getItemPrice([
        'id'        => $values->basket_row_id,
      ]);
      $discount = $this->basket->cart()->getItemDiscount([
        'id'        => $values->basket_row_id,
      ]);
    }
    if (empty($price)) {
      $price = 0;
    }
    if (empty($discount)) {
      $discount = 0;
    }
    $count = !empty($values->basket_row_count) ? $values->basket_row_count : 1;
    return [
      '#type'     => 'inline_template',
      '#template' => $this->options['templatePrice'],
      '#context'  => [
        'sum'     => $price * $count,
        'currency'  => $this->basket->cart()->getCurrencyName(),
        'discount'  => $discount,
      ],
    ];
  }

}
