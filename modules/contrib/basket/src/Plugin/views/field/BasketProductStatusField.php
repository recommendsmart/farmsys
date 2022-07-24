<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Item status field.
 *
 * @ViewsField("basket_product_status_field")
 */
class BasketProductStatusField extends FieldPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // We don't need to modify query for this particular example.
    $this->query->addField('node_field_data', 'status', 'basket_product_status');
    $this->query->addField('node_field_data', 'nid', 'basket_product_nid');
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->query->addOrderBy('node_field_data', 'status', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->statusHtml($values->basket_product_nid, $values->basket_product_status, $this->basket);
  }

  /**
   * {@inheritdoc}
   */
  public static function statusHtml($nid, $status, $basket) {
    $statusText = !empty($status) ? 'Active' : 'Not active';
    return [
      '#prefix'       => '<span class="basket_product_status_' . $nid . '">',
      '#suffix'       => '</span>',
      '#markup'       => $basket->Translate()->trans($statusText),
    ];
  }

}
