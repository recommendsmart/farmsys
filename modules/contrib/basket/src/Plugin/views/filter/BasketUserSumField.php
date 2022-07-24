<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by the total value of the user's orders.
 *
 * @ViewsFilter("basket_user_sum_field")
 */
class BasketUserSumField extends FilterPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;
  
  /**
   * Set basketQuery.
   *
   * @var object
   */
  protected $basketQuery;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketQuery = \Drupal::getContainer()->get('BasketQuery');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['labelMIN'] = ['default' => 'Amount, from'];
    $options['labelMAX'] = ['default' => 'Amount, up'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    $form['expose']['labelMIN'] = [
      '#type'         => 'textfield',
      '#title'        => 'Label MIN',
      '#default_value' => $this->options['labelMIN'],
    ];
    $form['expose']['labelMAX'] = [
      '#type'         => 'textfield',
      '#title'        => 'Label MAX',
      '#default_value' => $this->options['labelMAX'],
    ];
    parent::buildExposeForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }
    $identifier = $this->options['expose']['identifier'];
    $form[$identifier] = [
      '#type'         => 'item',
      '#wrapper_attributes' => ['class' => ['items_combine']],
      [
        'min'           => [
          '#type'         => 'number',
          '#step'         => 1,
          '#title'        => !empty($this->options['labelMIN']) ? $this->basket->translate()->trans(trim($this->options['labelMIN'])) : '',
          '#min'          => 0,
          '#parents'      => [$identifier, 'min'],
        ],
        'arrow'         => [
          '#markup'       => '<div class="arrow form-item"></div>',
        ],
        'max'           => [
          '#type'         => 'number',
          '#step'         => 1,
          '#title'        => !empty($this->options['labelMAX']) ? $this->basket->translate()->trans(trim($this->options['labelMAX'])) : '',
          '#min'          => 0,
          '#parents'      => [$identifier, 'max'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (!empty($input[$this->options['expose']['identifier']])) {
      $this->value = $input[$this->options['expose']['identifier']];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->value)) {
      if ((isset($this->value['min']) && is_numeric($this->value['min'])) || (isset($this->value['max']) && is_numeric($this->value['max']))) {
        $this->basketQuery->userSumViewsJoin($view);
        if (!empty($this->query->relationships[$this->field . '_getUserSumQuery'])) {
          if (isset($this->value['min']) && is_numeric($this->value['min'])) {
            $this->query->addWhere(NULL, $this->field . '_getUserSumQuery.total_sum', $this->value['min'], '>=');
          }
          if (isset($this->value['max']) && is_numeric($this->value['max'])) {
            $this->query->addWhere(NULL, $this->field . '_getUserSumQuery.total_sum', $this->value['max'], '<=');
          }
        }
      }
    }
  }

}
