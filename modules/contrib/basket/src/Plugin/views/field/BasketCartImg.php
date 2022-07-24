<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Product photo field in the cart.
 *
 * @ViewsField("basket_cart_img")
 */
class BasketCartImg extends FieldPluginBase {

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
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addField('basket', 'id', 'basket_row_id', $params);
    $this->query->addField('basket', 'nid', 'basket_row_nid', $params);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['image_style'] = ['default' => 'thumbnail'];
    $options['image_link']  = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['image_style'] = [
      '#type'             => 'select',
      '#title'            => t('Image style'),
      '#options'          => image_style_options(FALSE),
      '#default_value'    => $this->options['image_style'],
    ];
    $form['image_link'] = [
      '#type'             => 'checkbox',
      '#title'            => t('Link to the @entity_label', ['@entity_label' => t('Content')]),
      '#default_value'    => $this->options['image_link'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!empty($values->basket_row_id)) {
      $getFid = $this->basket->cart()->getItemImg([
        'id'        => $values->basket_row_id,
      ]);
      if (!empty($getFid)) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($getFid);
      }
    }
    if (empty($file)) {
      return [];
    }
    $uri = $file->getFileUri();
    /*Alter*/
	  \Drupal::moduleHandler()->alter('basket_cart_img', $uri, $values);
	  /*End alter*/
    if (!empty($this->options['image_link'])) {
      return [
        '#type'         => 'link',
        '#title'        => [
          '#theme'        => 'image_style',
          '#style_name'   => $this->options['image_style'],
          '#uri'          => $uri,
        ],
        '#url'          => new Url('entity.node.canonical', [
          'node'          => $values->basket_row_nid,
        ]),
      ];
    }
    else {
      return [
        '#theme'        => 'image_style',
        '#style_name'   => $this->options['image_style'],
        '#uri'          => $uri,
      ];
    }
  }

}
