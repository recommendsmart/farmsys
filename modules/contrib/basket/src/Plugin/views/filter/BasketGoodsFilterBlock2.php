<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Default implementation of the base filter plugin.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("basket_goods_filter_block2")
 */
class BasketGoodsFilterBlock2 extends FilterPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set request.
   *
   * @var object
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->basket = \Drupal::service('Basket');
    $this->request = \Drupal::request()->request->all();
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $form['orderId'] = [
      '#type'            => 'hidden',
      '#value'        => !empty($this->request['orderId']) ? $this->request['orderId'] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $accept = FALSE;
    if (!empty($this->request['orderId'])) {
      $accept = TRUE;
    }
    return $accept;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $nodeTypes = [0];
    if (!empty($settingsNodeTypes = $this->basket->getNodeTypes())) {
      foreach ($settingsNodeTypes as $info) {
        $nodeTypes[] = $info->type;
      }
    }
    $this->query->addWhere(1, 'node_field_data.type', $nodeTypes, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return 'Basket goods filter block 2';
  }

}
