<?php

namespace Drupal\basket\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\basket\Admin\Page\Trash;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("basket_operations_buttons")
 */
class BasketOperationsButtons extends AreaPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set basketStockBulk.
   *
   * @var Drupal\basket\Plugins\Stock\BasketStockBulkManager
   */
  protected $basketStockBulk;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::service('Basket');
    $this->basketStockBulk = \Drupal::service('BasketStockBulk');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($this->view->id() == 'basket') {
      $items = [
        'playNodes'     => [
          'title'         => $this->basket->Translate()->t('Publish'),
          'ico'           => $this->basket->getIco('play.svg', 'base'),
          'color'         => '#FFFFFF',
          'notWhite'      => TRUE,
          'weight'        => -3,
        ],
        'stopNodes'     => [
          'title'         => $this->basket->Translate()->t('Unpublish'),
          'ico'           => $this->basket->getIco('stop.svg', 'base'),
          'color'         => '#FFFFFF',
          'notWhite'      => TRUE,
          'weight'        => -2,
        ],
        'deleteNodes'   => [
          'title'         => $this->basket->Translate()->t('Delete'),
          'ico'           => $this->basket->getIco('delete_trash.svg', 'base'),
          'color'         => '#DF0000',
          'weight'        => -1,
        ],
      ];
      if (!empty($bulks = $this->basketStockBulk->getDefinitions())) {
        foreach ($bulks as $bulk) {
          $ico = $this->basketStockBulk->getIco($bulk['id']);
          $items['bulk-' . $bulk['id']] = [
            'title'         => $this->basket->Translate($bulk['provider'])->trans($bulk['name']),
            'ico'           => !empty($ico) ? $ico : $this->basket->getIco('stock_def.svg'),
            'color'         => !empty($bulk['color']) ? $bulk['color'] : '#FFFFFF',
            'weight'        => !empty($bulk['weight']) ? $bulk['weight'] : 0,
            'notWhite'      => TRUE,
          ];
        }
      }
      foreach ($items as $key => $item) {
        $post = [
          'operationType'     => $key,
          'name'              => 'product_chacked',
        ];
        $items[$key]['dataPost'] = json_encode($post);
      }
      uasort($items, 'Drupal\\Component\\Utility\\SortArray::sortByWeightElement');
      if (!empty($this->view->args[0]) && $this->view->args[0] == 'is_delete') {
        $trash = new Trash();
        return [
          '#theme'        => 'basket_admin_basket_block_caption',
          '#info'         => [
            'items'         => $trash->getCaptionItems('products'),
          ],
        ];
      }
      return [
        '#theme'        => 'basket_area_buttons',
        '#info'         => [
          'items'         => $items,
          'onclickUrl'        => Url::fromRoute('basket.admin.pages', [
            'page_type'         => 'api-operations',
          ])->toString(),
          'inputName'         => 'product_chacked',
          'goodsInfo'         => [
            '#theme'            => 'basket_area_buttons_goods_info',
            '#info'             => [
              'goodsInfo'         => $this->basket->getCounts('goodsInfo', $this),
            ],
          ],
        ],
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('basket operations product');
  }

}
