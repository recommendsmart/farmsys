<?php

namespace Drupal\basket\Admin\Page;

/**
 * {@inheritdoc}
 */
class StockProduct {

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
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function page() {
    return [
      'statBlock'     => $this->basket->full('getProductStatBlock'),
      'list'          => [
        '#prefix'       => '<div class="basket_table_wrap">',
        '#suffix'       => '</div>',
        'title'         => [
          '#prefix'       => '<div class="b_title">',
          '#suffix'       => '</div>',
          '#markup'       => $this->basket->Translate()->t('Product List'),
        ],
        'content'       => [
          '#prefix'       => '<div class="b_content">',
          '#suffix'       => '</div>',
          'view'          => $this->basket->getView('basket', 'block_3'),
        ],
      ],
    ];
  }

}
