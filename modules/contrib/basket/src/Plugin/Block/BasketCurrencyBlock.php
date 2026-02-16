<?php

namespace Drupal\basket\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Block of the selected site currency.
 *
 * @Block(
 *   id = "basket_currency",
 *   admin_label = "Basket currency",
 *   category = "Basket currency",
 * )
 */
class BasketCurrencyBlock extends BlockBase {

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
    return [
      'view'          => [
        '#type'         => 'select',
        '#options'      => $this->basket->Currency()->getOptions(TRUE),
        '#value'        => $this->basket->Currency()->getCurrent(),
        '#attributes'   => [
          'onchange'            => 'basket_ajax_link(this, \'' . Url::fromRoute('basket.pages', ['page_type' => 'api-change_currency'])->toString() . '\')',
          'data-post'     => json_encode([
            'post_type'        => 'change_currency',
          ]),
        ],
        '#attached'        => [
          'library'        => [
            'basket/basket.js',
          ],
        ],
      ],
      '#cache'        => [
        'max-age'        => 0,
      ],
    ];
  }

}
