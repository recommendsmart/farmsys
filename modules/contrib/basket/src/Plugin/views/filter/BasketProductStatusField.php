<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by product statuses.
 *
 * @ViewsFilter("basket_product_status_field")
 */
class BasketProductStatusField extends FilterPluginBase {

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
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }
    $identifier = $this->options['expose']['identifier'];
    $form[$identifier] = [
      '#type'         => 'select',
      '#options'      => [
        1               => $this->basket->translate()->t('Active'),
        0               => $this->basket->translate()->t('Not active'),
      ],
      '#empty_option' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (isset($input[$this->options['expose']['identifier']]) && is_numeric($input[$this->options['expose']['identifier']])) {
      $this->value = $input[$this->options['expose']['identifier']];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (isset($this->value)) {
      $this->query->addWhere(1, 'node_field_data.status', $this->value);
    }
  }

}
