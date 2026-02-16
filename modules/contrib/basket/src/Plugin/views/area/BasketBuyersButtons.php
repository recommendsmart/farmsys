<?php

namespace Drupal\basket\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("basket_buyers_buttons")
 */
class BasketBuyersButtons extends AreaPluginBase {

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
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['addUser'] = ['default' => '#02B1FF'];
    $options['changePercent'] = ['default' => '#4D9923'];
    $options['deleteUser'] = ['default' => '#DF0000'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['addUser'] = [
      '#type'             => 'textfield',
      '#title'            => $this->basket->Translate()->t('Add a user'),
      '#default_value'    => $this->options['addUser'],
    ];
    $form['changePercent'] = [
      '#type'             => 'textfield',
      '#title'            => $this->basket->Translate()->t('Change individual discount'),
      '#default_value'    => $this->options['changePercent'],
    ];
    $form['deleteUser'] = [
      '#type'             => 'textfield',
      '#title'            => $this->basket->Translate()->t('Delete'),
      '#default_value'    => $this->options['deleteUser'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($this->view->id() == 'basket_users') {
      return [
        '#theme'        => 'basket_area_buttons',
        '#info'         => [
          'items'         => [
            'addUser'       => [
              'title'         => 'Add a user',
              'ico'           => $this->basket->getIco('add_ico.svg'),
              'color'         => !empty($this->options['addUser']) ? $this->options['addUser'] : '#02B1FF',
            ],
            'changePercent' => [
              'title'         => 'Change individual discount',
              'ico'           => $this->basket->getIco('percent_ico.svg'),
              'color'         => !empty($this->options['changePercent']) ? $this->options['changePercent'] : '#4D9923',
            ],
            'deleteUser'    => [
              'title'         => 'Delete',
              'ico'           => $this->basket->getIco('delete_trash.svg'),
              'color'         => !empty($this->options['deleteUser']) ? $this->options['deleteUser'] : '#DF0000',
            ],
          ],
        ],
      ];
    }
  }

}
