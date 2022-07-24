<?php

namespace Drupal\basket\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Cart Currency Rate Block.
 *
 * @Block(
 *   id = "basket_currency_rate",
 *   admin_label = "Basket currency rate",
 *   category = "Basket currency",
 * )
 */
class BasketCurrencyRateBlock extends BlockBase {

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
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currency = $this->basket->Currency()->tree();
    foreach ($currency as $key => $item) {
      if (empty($this->configuration['currency_enabled'][$item->id])) {
        unset($currency[$key]);
      }
    }
    return [
      '#theme'        => 'basket_rate_block',
      '#info'            => [
        'items'            => $currency,
      ],
      '#cache'        => [
        'tags'            => [
          \Drupal::service('cache_context.basket_currency')->getCacheTag(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->basket->Currency()->tree() as $key => $item) {
      $options[$item->id] = $item->iso;
    }
    $form['currency'] = [
      '#type'            => 'checkboxes',
      '#title'        => $this->basket->Translate()->t('Currency'),
      '#options'        => $options,
      '#default_value' => !empty($this->configuration['currency_enabled']) ? $this->configuration['currency_enabled'] : NULL,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->configuration['currency_enabled'] = $form_state->getValue('currency');
    }
  }

}
