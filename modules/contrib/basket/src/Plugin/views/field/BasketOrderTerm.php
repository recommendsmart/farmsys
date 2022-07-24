<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Cart term field.
 *
 * @ViewsField("basket_order_term")
 */
class BasketOrderTerm extends FieldPluginBase {

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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['change'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['change'] = [
      '#type'         => 'checkbox',
      '#title'        => $this->t('Change ajax'),
      '#default_value' => $this->options['change'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $term = $this->basket->term()->load($this->getValue($values));
    if (!empty($term)) {
      if ($this->view->id() == 'basket') {
        return $this->getNameHtml(
          $term,
          $this->realField,
          $this->getValue($values),
          $values,
          $this->options['change']
        );
      }
      else {
        return $this->basket->translate()->trans($term->name);
      }
    }
    return $this->getValue($values);
  }

  /**
   * {@inheritdoc}
   */
  private function getNameHtml($term, $realField, $def, $values, $ajaxChange = FALSE) {
    $val = [];
    switch ($realField) {
      case'status':
      case'fin_status':
        $active_html = $this->basket->textColor(
          $this->basket->translate()->trans($term->name),
          $term->color,
          [
            'class'     => ['status_' . $term->type . '_' . $values->basket_orders_id],
          ]
        );
        if (empty($ajaxChange)) {
          $val = $active_html;
        }
        if (\Drupal::getContainer()->get('BasketAccess')->hasPermission('basket edit_' . $realField . '_order_access', [
					'orderId'       => $values->basket_orders_id
	      ]) && $ajaxChange) {
          $order = $this->basket->Orders($values->basket_orders_id)->load();
          if (!empty($order->is_delete)) {
            $val = $active_html;
          }
          $val = [
            'view'          => $active_html,
            'select'        => [
              '#type'         => 'select',
              '#options'      => $this->basket->term()->getOptions($term->type),
              '#attributes'   => [
                'class'         => ['term_change_select'],
                'onchange'      => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', [
                  'page_type'     => 'api-order_change_status',
                ])->toString() . '\')',
                'data-post'     => json_encode([
                  'orderID'       => $values->basket_orders_id,
                  'status_type'   => $term->type,
                  'set_val'       => $term->id,
                ]),
              ],
              '#field_suffix' => ' ',
              '#value'        => $term->id,
            ],
          ];
        }
        else {
          $val = $active_html;
        }
        break;

      default:
        $val = $this->basket->translate()->trans($term->name);
        break;
    }
    return $val;
  }

}
