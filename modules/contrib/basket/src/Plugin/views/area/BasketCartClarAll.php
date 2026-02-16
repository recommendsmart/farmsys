<?php

namespace Drupal\basket\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("basket_cart_clear_all")
 */
class BasketCartClarAll extends AreaPluginBase {

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
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($empty) {
      return [];
    }
    return [
      '#theme'        => 'basket_cart_clear_all',
      '#info'         => [
        'text'          => $this->basket->Translate()->t('Delete all'),
        'attributes'    => new Attribute([
          'class'         => ['basket_delete_all_link'],
          'href'          => 'javascript:void(0);',
          'onclick'       => 'basket_ajax_link(this, \'' . Url::fromRoute('basket.pages', ['page_type' => 'api-cart_clear_all'])->toString() . '\')',
          'data-post'     => json_encode([
            'view'          => [
              'id'            => $this->view->id(),
              'display'       => $this->view->current_display,
            ],
          ]),
        ]),
      ],
    ];
  }

}
