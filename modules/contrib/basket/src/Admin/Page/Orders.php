<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class Orders {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $asket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function page() {
    return [
      'statistics'    => $this->basket->full('getStatisticsBlock', [TRUE]),
      'orders'        => [
        '#prefix'        => '<div class="basket_table_wrap">',
        '#suffix'        => '</div>',
        'title'            => [
          '#prefix'        => '<div class="b_title">',
          '#suffix'        => '</div>',
          '#markup'        => $this->basket->Translate()->t('Orders'),
        ],
        'content'        => [
          '#prefix'        => '<div class="b_content">',
          '#suffix'        => '</div>',
          'view'            => $this->basket->getView('basket', 'block_1', 'not_delete'),
          'color'            => [
            '#prefix'        => '<div class="basket_color_info">',
            '#suffix'        => '</div>',
            'info'            => $this->basket->textColor($this->basket->Translate()->t('Order not yet reviewed'), '#00A337'),
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter($response, $api_type = NULL) {
    switch ($api_type) {
      case'orders_stat_block_settings':
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->basket->Translate()->t('Order statistics block settings'),
          \Drupal::formBuilder()->getForm(new BasketOrdersStatBlockSettings()),
          [
            'width' => 600,
            'class' => [],
          ]
        );
        break;

      case'orders_tabs_settings':
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->basket->Translate()->t('Tab setting'),
          \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\OrdersTabsSettingsForm'),
          [
            'width'     => 960,
            'class'     => [],
          ]
        );
        break;
    }
  }

}

/**
 * {@inheritdoc}
 */
class BasketOrdersStatBlockSettings extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $asket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_orders_stat_block_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'     => '<div id="basket_orders_stat_block_settings_form_ajax_wrap">',
      '#suffix'     => '</div>',
      '#attached'   => [
        'library'     => [
          'basket/colorpicker',
        ],
      ],
      'config'      => [
        '#tree'       => TRUE,
      ],
    ];
    foreach ([
      'processed'     => [
        'title'         => 'Orders are being processed',
        'status'        => TRUE,
      ],
      'completed'     => [
        'title'         => 'Completed orders',
        'status'        => TRUE,
      ],
      'return'        => [
        'title'         => 'Purchase returns',
        'status'        => TRUE,
      ],
      'total'         => [
        'title'         => 'Total amount of orders',
      ],
    ] as $key => $info) {
      $title = $this->basket->getSettings('orders_stat_block_settings', 'config.' . $key . '.title');
      $title = !empty($title) ? trim($title) : $info['title'];
      $form['config'][$key] = [
        '#type'       => 'details',
        '#title'      => $this->basket->Translate()->trans($title),
        'title'       => [
          '#type'       => 'textfield',
          '#title'      => $this->basket->Translate()->t('Title'),
          '#required'   => TRUE,
          '#default_value' => $title,
        ],
        'on'          => [
          '#type'       => 'checkbox',
          '#title'      => $this->basket->Translate()->t('Active'),
          '#default_value' => $this->basket->getSettings('orders_stat_block_settings', 'config.' . $key . '.on'),
        ],
        'color'       => [
          '#type'       => 'textfield',
          '#title'      => $this->basket->Translate()->trans('Color') . ':',
          '#attributes' => [
            'readonly'    => 'readonly',
            'class'       => ['color_input'],
          ],
          '#states'     => [
            'visible'     => [
              'input[name="config[' . $key . '][on]"]' => ['checked' => TRUE],
            ],
          ],
          '#default_value' => $this->basket->getSettings('orders_stat_block_settings', 'config.' . $key . '.color'),
        ],
      ];
      if (!empty($info['status'])) {
        $form['config'][$key]['status'] = [
          '#type'       => 'checkboxes',
          '#title'      => $this->basket->Translate()->trans('Order status') . ':',
          '#options'    => $this->basket->Term()->getOptions('status'),
          '#states'     => [
            'visible'     => [
              'input[name="config[' . $key . '][on]"]' => ['checked' => TRUE],
            ],
          ],
          '#default_value' => $this->basket->getSettings('orders_stat_block_settings', 'config.' . $key . '.status'),
        ];
      }
    }

    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
        '#ajax'         => [
          'wrapper'       => 'basket_orders_stat_block_settings_form_ajax_wrap',
          'callback'      => [$this, 'ajaxSubmit'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->basket->setSettings('orders_stat_block_settings', 'config', $form_state->getValue('config'));
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
      'status',
      $this->basket->Translate()->t('Settings saved.'),
    ]));
    $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
    return $response;
  }

}
