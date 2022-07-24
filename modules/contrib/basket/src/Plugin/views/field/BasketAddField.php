<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * The field for adding an item to the cart.
 *
 * @ViewsField("basket_add_field")
 */
class BasketAddField extends FieldPluginBase {

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
  public function query() {}

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options                = parent::defineOptions();
    $options['view_count']  = ['default' => 1];
    $options['button_text'] = ['default' => 'Buy'];
    // Alter.
    \Drupal::moduleHandler()->alter('basket_add_field_views_defineOptions', $options);
    // ---
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['view_count'] = [
      '#type'             => 'checkbox',
      '#title'            => $this->basket->translate()->t('Show + / -'),
      '#default_value'    => $this->options['view_count'],
    ];
    $form['button_text'] = [
      '#type'             => 'textfield',
      '#title'            => $this->basket->translate()->t('Button text'),
      '#default_value'    => $this->options['button_text'],
      '#field_prefix'     => 'basket::t(',
      '#field_suffix'     => ')',
    ];
    // Alter.
    \Drupal::moduleHandler()->alter('basket_add_field_views_buildOptionsForm', $form, $this->options);
    // ---
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->basket->getClass('Drupal\basket\BasketExtraFields')->BasketAddGenerate($values->_entity, 'views', (object) [
      'extra_fields'      => [
        'add'               => [
          'on'                => TRUE,
          'text'              => $this->options['button_text'],
          'count'             => $this->options['view_count'],
        ],
      ],
      'allOptions'        => $this->options,
    ]);
  }

}
