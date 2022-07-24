<?php

namespace Drupal\basket\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Added quantity to cart block.
 *
 * @Block(
 *   id = "basket_count",
 *   admin_label = @Translation("Basket count"),
 *   category = @Translation("Basket count"),
 * )
 */
class BasketCountBlock extends BlockBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set cart.
   *
   * @var object
   */
  protected $cart;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::service('Basket');
    $this->cart = $this->basket->Cart();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = new Attribute([
      'class'        => ['count-link'],
    ]);
    if ($this->basket->getSettings('order_page', 'config.view_ajax')) {
      $attributes->setAttribute('href', 'javascript:void(0);');
      $attributes->setAttribute('onclick', 'basket_ajax_link(this, \'' . Url::fromRoute('basket.pages', [
        'page_type'        => 'api-load_popup',
      ])->toString() . '\')');
      $attributes->setAttribute('data-post', json_encode(['load_popup' => 'basket_view']));
    }
    else {
      $attributes->setAttribute('href', Url::fromRoute('basket.pages', ['page_type' => 'view'])->toString());
    }
    $info           = [
      'count'            => $this->cart->getCount(),
      'link'            => [
        'text'            => $this->basket->Translate()->t('Basket'),
        'attributes'    => $attributes,
      ],
      'Cart'            => $this->cart,
    ];
    $info['countK'] = $this->basket->NumberFormat()->convert($info['count']);
    return [
      '#theme'     => 'basket_count_block',
      '#info'        => $info,
      '#prefix'    => '<div id="' . Html::getUniqueId('basket-count-block-wrap') . '">',
      '#suffix'    => '</div>',
      '#cache'    => [
        'max-age'    => 0,
      ],
      '#attached'    => [
        'library'    => ['basket/basket.js'],
      ],
    ];
  }

}
