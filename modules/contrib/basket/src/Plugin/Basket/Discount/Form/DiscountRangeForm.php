<?php

namespace Drupal\basket\Plugin\Basket\Discount\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for setting discounts from the total amount of the basket.
 */
class DiscountRangeForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set currency.
   *
   * @var string
   */
  protected $currency = '';
  /**
   * Set currency.
   *
   * @var int
   */
  protected $currencyID = 0;

  const MAX_NUM = 10;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->currencyID = $this->basket->Currency()->getCurrent(TRUE);
    if (!empty($this->currencyID)) {
      $defCurrency = $this->basket->Currency()->load($this->currencyID);
      $this->currency = $this->basket->Translate()->trans($defCurrency->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_discount_range_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['config'] = [
      '#type'            => 'table',
      '#header'        => [
        '',
        $this->basket->Translate()->t('Price from') . ' (' . $this->currency . ')',
        $this->basket->Translate()->t('Price to') . ' (' . $this->currency . ')',
        '%',
      ],
    ];
    foreach (range(1, $this::MAX_NUM) as $key) {
      $form['config'][$key] = [
        'num'           => [
          '#markup'       => $key,
        ],
        'min'            => [
          '#type'            => 'number',
          '#field_suffix'    => '',
          '#min'            => 0,
          '#default_value' => $this->basket->getSettings('discount_range', 'config.' . $key . '.min'),
        ],
        'max'            => [
          '#type'            => 'number',
          '#field_suffix'    => '',
          '#min'            => 0,
          '#default_value' => $this->basket->getSettings('discount_range', 'config.' . $key . '.max'),
        ],
        'percent'        => [
          '#type'            => 'select',
          '#options'        => array_combine(range(0, 100), range(0, 100)),
          '#default_value' => $this->basket->getSettings('discount_range', 'config.' . $key . '.percent'),
        ],
      ];
    }
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue(['config', 'CurrencyID'], $this->currencyID);
    $this->basket->setSettings('discount_range', 'config', $form_state->getValue('config'));
  }

}
