<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Cart quantity field.
 *
 * @ViewsField("basket_cart_quantity")
 */
class BasketCartQuantity extends FieldPluginBase {

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
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addField('basket', 'id', 'basket_row_id', $params);
    $this->query->addField('basket', 'count', 'basket_row_count', $params);
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
      '#title'        => 'Change AJAX',
      '#default_value' => $this->options['change'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $basketCartItem = $this->basket->cart()->loadItem($values->basket_row_id);
    $attr = [
      'min'       => 1,
      'step'      => 1,
      'max'       => 999,
      'scale'     => 0,
    ];
    /*Alter*/
    \Drupal::moduleHandler()->alter('basket_count_input_attr', $attr, $basketCartItem->nid, $basketCartItem->all_params);
    /*End Alter*/
    if (!isset($attr['scale'])) {
      $attr['scale'] = 0;
    }
    if (!empty($this->options['change'])) {

      $page = \Drupal::request()->query->get('page');
      $onclickUrl = Url::fromRoute('basket.pages', ['page_type' => 'api-change_count'], [
        'query'     => [
          'page'      => !empty($page) ? $page : 0,
        ],
      ])->toString();
      $post = json_encode([
        'update_id'     => $values->basket_row_id,
        'view'          => [
          'id'            => $this->view->id(),
          'display'       => $this->view->current_display,
	        'args'          => $this->view->args
        ],
      ]);
      return [
        '#type'         => 'inline_template',
        '#template'     => '<div class="basket_add_button_wrap">
        	<div class="basket_item_count">
          	<a href="javascript:void(0);" class="{{ min.class|join(\' \') }}" onclick="{{ min.onclick }}" data-post="{{ min.post }}">{{ min.text }}</a>
            <input type="number" data-basket-scale="{{ count.scale }}" value="{{ count.val }}" min="{{ count.min }}" step="{{ count.step }}" class="{{ count.class|join(\' \') }}" onblur="{{ count.onblur }}" onchange="{{ count.onchange }}" data-post="{{ count.post }}"/>
            <a href="javascript:void(0);" class="{{ plus.class|join(\' \') }}" onclick="{{ plus.onclick }}" data-post="{{ plus.post }}">{{ plus.text }}</a>
          </div>
        </div>',
        '#context'      => [
          'min'           => [
            'text'          => '-',
            'class'         => ['arrow', 'min'],
            'onclick'       => 'basket_change_input_count(this, \'-\', \'' . $onclickUrl . '\')',
            'post'          => $post,
          ],
          'plus'          => [
            'text'          => '+',
            'class'         => ['arrow', 'plus'],
            'onclick'       => 'basket_change_input_count(this, \'+\', \'' . $onclickUrl . '\')',
            'post'          => $post,
          ],
          'count'         => $attr + [
            'val'           => round($values->basket_row_count, $attr['scale']),
            'class'         => ['count_input'],
            'onchange'      => 'basket_change_input_count(this, \'\', \'' . $onclickUrl . '\')',
            'onblur'        => 'basket_input_count_format(this)',
            'post'          => $post,

          ],
        ],
        '#attached'     => [
          'library'       => ['basket/basket.js'],
        ],
      ];
    }
    return number_format($values->basket_row_count, $attr['scale'], '.', '');
  }

}
