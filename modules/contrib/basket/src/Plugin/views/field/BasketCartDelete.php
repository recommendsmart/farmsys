<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * The field for deleting an item in the cart.
 *
 * @ViewsField("basket_cart_delete")
 */
class BasketCartDelete extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addField('basket', 'id', 'basket_row_id', $params);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = ['default' => 'x'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type'         => 'textfield',
      '#title'        => 'Button text',
      '#default_value' => $this->options['text'],
      '#field_prefix' => 't(',
      '#field_suffix' => ')',
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="javascript:void(0);" class="{{ class|join(\' \') }}" onclick="{{ onclick }}" data-post="{{ post }}">{{ text }}</a>',
      '#context'      => [
        'class'         => ['button', 'button-delete'],
        'text'          => !empty($this->options['text']) ? \Drupal::getContainer()->get('Basket')->translate()->trans(trim($this->options['text'])) : '',
        'onclick'       => 'basket_ajax_link(this, \'' . Url::fromRoute('basket.pages', ['page_type' => 'api-delete_item'])->toString() . '\')',
        'post'          => json_encode([
          'delete_item'   => $values->basket_row_id,
          'view'          => [
            'id'            => $this->view->id(),
            'display'       => $this->view->current_display,
	          'args'          => $this->view->args
          ],
        ]),
      ],
      '#attached'     => [
        'library'       => ['basket/basket.js'],
      ],
    ];
  }

}
