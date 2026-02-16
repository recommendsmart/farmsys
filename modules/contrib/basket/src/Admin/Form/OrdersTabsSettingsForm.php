<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * {@inheritdoc}
 */
class OrdersTabsSettingsForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set config.
   *
   * @var array
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->config = $this->basket->getSettings('orders_tabs_settings', 'config');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_orders_tabs_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (empty($storage['max'])) {
      $storage['max'] = !empty($this->config) ? count($this->config) - 1 : 0;
      $form_state->setStorage($storage);
    }
    // ---
    $form += [
      '#prefix'       => '<div id="basket_orders_tabs_settings_ajax_wrap">',
      '#suffix'       => '</div>',
      'config'        => [
        '#tree'         => TRUE,
        '#type'         => 'table',
        '#prefix'       => '<div class="basket_table_wrap">',
        '#suffix'       => '</div>',
        '#header'       => [
          '',
          $this->basket->Translate()->t('Name'),
          $this->basket->Translate()->t('Status'),
          $this->basket->Translate()->t('Default'),
          '',
          '',
        ],
        '#tabledrag'    => [
         [
           'action'        => 'order',
           'relationship'  => 'sibling',
           'group'         => 'group-order-weight',
         ],
        ],
      ],
    ];
    $status_options = $this->basket->Term()->getOptions('status');
    foreach (range(0, $storage['max']) as $key) {
      if (!empty($storage['delete'][$key])) {
        continue;
      }
      $form['config'][$key] = [
        '#attributes'   => [
          'class'         => ['draggable'],
        ],
        '#weight'       => isset($this->config[$key]['weight']) ? $this->config[$key]['weight'] : 10000,
        'handle'        => [],
        [
          '#type'         => 'textfield',
          '#size'         => 20,
          '#parents'      => ['config', $key, 'name'],
          '#attributes'   => [
            'title'         => $this->basket->Translate()->t('The field can be translated into other languages.'),
          ],
          '#default_value' => !empty($this->config[$key]['name']) ? $this->config[$key]['name'] : '',
        ], [
          '#type'         => 'select',
          '#options'      => $status_options,
          '#multiple'     => TRUE,
          '#parents'      => ['config', $key, 'status'],
          '#default_value' => !empty($this->config[$key]['status']) ? $this->config[$key]['status'] : [],
        ], [
          '#type'         => 'radio',
          '#title'        => 'on',
          '#attributes'   => [
            'class'         => ['not_label'],
            'checked'       => isset($this->config['default']) && $this->config['default'] == $key ? TRUE : FALSE,
          ],
          '#parents'      => ['config', 'default'],
          '#return_value' => $key,
        ], [
          '#type'         => 'button',
          '#name'         => 'delete_' . $key,
          '#value'        => 'x',
          '#ajax'         => [
            'wrapper'       => 'basket_orders_tabs_settings_ajax_wrap',
            'callback'      => '::ajaxReload',
          ],
          '#deleteKey'    => $key,
          '#validate'     => [__CLASS__ . '::deleteValidate'],
          '#parents'      => ['config_delete', $key],
        ], [
          '#type'         => 'number',
          '#attributes'   => [
            'class'         => ['group-order-weight'],
          ],
          '#parents'      => ['config', $key, 'weight'],
          '#default_value' => !empty($this->config[$key]['weight']) ? $this->config[$key]['weight'] : 0,
        ],
      ];
    }
    uasort($form['config'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');
    $form['config']['add'] = [
     [
       '#type'         => 'button',
       '#name'         => 'add',
       '#value'        => '+ ' . $this->basket->Translate()->t('Add tab'),
       '#ajax'         => [
         'wrapper'       => 'basket_orders_tabs_settings_ajax_wrap',
         'callback'      => '::ajaxReload',
       ],
       '#validate'       => ['\\' . __CLASS__ . '::addValidate'],
       '#wrapper_attributes' => ['colspan' => 6],
       '#attributes'   => [
         'class'         => ['button--add'],
       ],
     ],
    ];
    $form['actions'] = [
      '#type'            => 'actions',
      'submit'        => [
        '#type'            => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
        '#ajax'            => [
          'wrapper'        => 'basket_orders_tabs_settings_ajax_wrap',
          'callback'        => '::ajaxSubmit',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $saveConfig = [];
    $config = $form_state->getValue('config');
    if (isset($config['default'])) {
      $saveConfig['default'] = $config['default'];
    }
    foreach ($config as $key => $info) {
      if (!empty(trim($info['name'])) && !empty($info['status'])) {
        $saveConfig[] = $info;
      }
    }
    $this->basket->setSettings('orders_tabs_settings', 'config', $saveConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxReload(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addValidate(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $storage['max']++;
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteValidate(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    if (isset($triggerdElement['#deleteKey'])) {
      $storage = $form_state->getStorage();
      $storage['delete'][$triggerdElement['#deleteKey']] = TRUE;
      $form_state->setStorage($storage);
      $form_state->setRebuild();
    }
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
