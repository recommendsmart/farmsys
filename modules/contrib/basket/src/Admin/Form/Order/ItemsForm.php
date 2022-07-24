<?php

namespace Drupal\basket\Admin\Form\Order;

use Drupal\file\Entity\File;
use Drupal\basket\Admin\Page\Order;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * {@inheritdoc}
 */
class ItemsForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set order.
   *
   * @var object
   */
  protected $order;

  /**
   * Set currencyName.
   *
   * @var string
   */
  protected $currencyName;

  /**
   * Set itemAjax.
   *
   * @var array
   */
  protected $itemAjax;

  /**
   * {@inheritdoc}
   */
  public function __construct($order) {
    $this->basket = \Drupal::service('Basket');
    $this->order = $order;
    if (!empty($this->order->currency)) {
      if (is_object($this->order->currency)) {
        $currency = $this->order->currency;
      }
      else {
        $currency = $this->basket->Currency()->load($this->order->currency);
      }
      if (!empty($currency->name)) {
        $this->currencyName = $this->basket->Translate()->trans($currency->name);
      }
    }
    $this->itemAjax = [
      'wrapper'       => 'basket_order_items_form_ajax_wrap',
      'callback'      => __CLASS__ . '::ajaxReload',
      'url'           => new Url('basket.admin.pages', ['page_type' => 'api-orders-basket_order_items_form']),
      'options'       => [
        'query'         => \Drupal::request()->query->All() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_order_items_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (empty($storage['Order'])) {
      $storage['Order'] = $this->order;
      $form_state->setStorage($storage);
    }
    $formOrder = $storage['Order'];
    $form += [
      '#prefix'       => '<div id="basket_order_items_form_ajax_wrap">',
      '#suffix'       => '</div>',
      'orderId'       => [
        '#type'         => 'hidden',
        '#value'        => $formOrder->id,
      ],
      'items'         => [
        '#tree'         => TRUE,
        '#type'         => 'table',
        '#header'       => [
          [
            'data'      => $this->basket->Translate()->t('Product'),
            'colspan'   => 2,
          ], [
            'data'      => $this->basket->Translate()->t('Extra options'),
          ], [
            'data'      => $this->basket->Translate()->t('Price'),
          ], [
            'data'      => $this->basket->Translate()->t('Quantity'),
          ], [
            'data'      => [
              '#type'       => 'inline_template',
              '#template'   => '{{label}} <span class="info-help">{{ ico|raw }}</span><span class="info-help-content">{{text|raw}}</span>',
              '#context'    => [
                'label'       => $this->basket->Translate()->t('Discount'),
                'ico'         => $this->basket->getIco('help.svg'),
                'text'        => $this->basket->Translate()->t('Individual product discount.'),
              ],
            ],
          ], [
            'data'      => $this->basket->Translate()->t('Sum'),
          ], [],
        ],
      ],
    ];
    $totalSum = 0;
    $totalSumItems = 0;
    $totalCount = 0;
    if (!empty($formOrder->items)) {
      $nids = [];
      foreach ($formOrder->items as $item) {
        if (empty($item->nid)) {
          continue;
        }
        $nids[$item->nid] = $item->nid;
        // Save temp.
        $this->basket->addBasketItemTemp($item, $this->order->id);
      }
      $itemNodes = !empty($nids) ? \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple($nids) : [];
      // ----------------
      foreach ($formOrder->items as $item) {
        // Is delete.
        if (!empty($item->isDelete)) {
          continue;
        }
        // ---
        $rowPrice = $this->getItemInfo('price', $item->id, $form_state);
        $rowCount = $this->getItemInfo('count', $item->id, $form_state);
        $rowDiscount = $this->getItemInfo('discount', $item->id, $form_state);
        $rowSum = $rowPrice * $rowCount - ($rowPrice * $rowCount / 100 * $rowDiscount);

        $attr = [
          'min'       => 1,
          'step'      => 1,
          'max'       => 999,
          'scale'     => 0,
          'itemEdit'  => $item,
        ];
        \Drupal::moduleHandler()->alter('basket_count_input_attr', $attr, $item->nid, $item->params);
        if (!isset($attr['scale'])) {
          $attr['scale'] = 0;
        }
        if (isset($attr['itemEdit'])) {
          unset($attr['itemEdit']);
        }
        $form['items'][$item->id] = [
          [
            '#type'                 => 'inline_template',
            '#template'             => '{% if uri %}{{ basket_image(uri, \'thumbnail\') }}{% endif %}',
            '#context'              => [
              'uri'                   => !empty($item->node_fields['img_uri']) ? $item->node_fields['img_uri'] : NULL,
            ],
            '#wrapper_attributes'   => ['style' => 'width:100px;'],
          ], [
            '#type'                 => 'inline_template',
            '#template'             => '<b>{% if node %}<a href="{{ url(\'entity.node.canonical\', {\'node\': node.id}) }}" target="_blank">{{ title }}</a>{% else %}{{ title }}{% endif %}</b>',
            '#context'              => [
              'title'                 => !empty($item->node_fields['title']) ? $item->node_fields['title'] : NULL,
              'node'                  => !empty($itemNodes[$item->nid]) ? $itemNodes[$item->nid] : NULL,
            ],
          ],
          'params'                => $this->itemParams([
            'item'                  => $item,
            'node'                  => !empty($itemNodes[$item->nid]) ? $itemNodes[$item->nid] : NULL,
          ]),
          'price'                 => [
            '#type'                 => 'number',
            '#default_value'        => $rowPrice,
            '#field_suffix'         => $this->currencyName,
            '#step'                 => 0.01,
            '#min'                  => 0,
            '#wrapper_attributes'   => ['class' => ['auto_width']],
            '#ajax'                 => $this->itemAjax + ['event' => 'change'],
          ],
          'count'                 => [
            '#type'                 => 'number',
            '#default_value'        => number_format($rowCount, $attr['scale'], '.', ''),
            '#min'                  => 0,
            '#step'                 => $attr['step'] ?? 1,
            '#max'                  => $attr['max'] ?? 999,
            '#wrapper_attributes'   => ['class' => ['auto_width count_td']],
            '#ajax'                 => $this->itemAjax + ['event' => 'change'],
          ],
          'discount'              => [
            '#type'                 => 'select',
            '#field_suffix'         => '%',
            '#options'              => array_combine(range(0, 100), range(0, 100)),
            '#wrapper_attributes'   => ['class' => ['auto_width']],
            '#ajax'                 => $this->itemAjax + ['event' => 'change'],
            '#default_value'        => $rowDiscount,
          ],
          'sum'                   => [
            '#type'                 => 'item',
            '#value'                => $rowPrice * $rowCount,
              [
                '#type'                 => 'inline_template',
                '#template'             => '<b>{{ row_sum|number_format(2, \',\', \' \') }} {{ currency }}</b>',
                '#context'              => [
                  'currency'              => $this->currencyName,
                  'row_sum'               => $rowSum,
                ],
              ],
          ],
          'delete'    => $this->itemDelete($item->id, $form_state),
        ];

        $totalSum += $rowSum;
        $totalSumItems += $rowSum;
        $totalCount += $rowCount;

      }
      // ----------------
    }
    $form['items']['add'] = [
      [
        '#type'                 => 'inline_template',
        '#template'             => '<a href="javascript:void(0);" class="form-submit button--add" onclick="{{ onclick }}" data-post="{{ post }}">+ {{ text }}</a>',
        '#context'              => [
          'text'                  => $this->basket->Translate()->t('Add a product'),
          'onclick'               => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-add_goods_popup'])->toString() . '\')',
          'post'                  => json_encode([
            'orderId'               => $formOrder->id,
          ]),
        ],
        '#wrapper_attributes'   => [
          'colspan'               => 6,
          'class'                 => ['not_hover'],
        ],
      ], [
        '#type'                 => 'textarea',
        '#parents'              => ['addItem'],
        '#wrapper_attributes'   => [
          'class'                 => ['not_hover'],
        ],
        '#attributes'           => ['style' => 'display:none;'],
        '#ajax'                 => $this->itemAjax + ['event' => 'change'],
        '#validate'             => [__CLASS__ . '::itemAddValid'],
      ],
    ];
    $totalSum += $this->getTotalInfo('add_price', $form_state);
    $totalSum += $this->getTotalInfo('delivery_price', $form_state);
    $form['total'] = [
      '#type'         => 'table',
      '#header'       => [
        $this->basket->Translate()->t('Order price'),
        $this->basket->Translate()->t('Shipping cost'),
        $this->basket->Translate()->trans('Amount adjustment') . ' (+/-)',
        $this->basket->Translate()->t('Items in order'),
        $this->basket->Translate()->t('To pay'),
      ],
      [
        [
          '#type'                     => 'item',
          '#value'                    => $totalSumItems,
          '#parents'                  => ['total', 'totalSum'],
            [
              '#type'                 => 'inline_template',
              '#template'             => '<b>{{ totalSum|number_format(2, \',\', \' \') }} {{ currency }}</b>',
              '#context'              => [
                'currency'              => $this->currencyName,
                'totalSum'              => $totalSumItems,
              ],
            ],
          '#wrapper_attributes'   => ['class' => ['not_hover']],
        ], [
          '#type'                     => 'number',
          '#parents'                  => ['total', 'delivery_price'],
          '#wrapper_attributes'       => [
            'class'           => [
              'auto_width', 'not_hover',
            ],
          ],
          '#ajax'                     => $this->itemAjax + ['event' => 'change'],
          '#default_value'            => $this->getTotalInfo('delivery_price', $form_state),
          '#field_suffix'             => $this->currencyName,
        ], [
          '#type'                     => 'number',
          '#parents'                  => ['total', 'add_price'],
          '#wrapper_attributes'       => [
            'class'           => [
              'auto_width', 'not_hover',
            ],
          ],
          '#ajax'                     => $this->itemAjax + ['event' => 'change'],
          '#default_value'            => $this->getTotalInfo('add_price', $form_state),
          '#field_suffix'             => [
            '#type'                     => 'inline_template',
            '#template'                 => '{{currency}} <span class="info-help">{{ ico|raw }}</span><span class="info-help-content">{{text|raw}}</span>',
            '#context'                  => [
              'currency'                  => $this->currencyName,
              'ico'                       => $this->basket->getIco('help.svg'),
              'text'                      => $this->basket->Translate()->t('Adjusting the cost of the order up or down.'),
            ],
          ],
        ], [
          '#type'                     => 'item',
          '#value'                    => $totalCount,
          '#parents'                  => ['total', 'totalCount'],
            [
              '#type'                 => 'inline_template',
              '#template'             => '<b>{{ totalCount }}</b>',
              '#context'              => [
                'totalCount'            => $totalCount,
              ],
            ],
          '#wrapper_attributes'   => ['class' => ['not_hover']],
        ], [
          '#type'                     => 'item',
          '#value'                    => $totalSum,
          '#parents'                  => ['total', 'paySum'],
            [
              '#type'                 => 'inline_template',
              '#template'             => '<b>{{ totalSum|number_format(2, \',\', \' \') }} {{ currency }}</b>',
              '#context'              => [
                'currency'              => $this->currencyName,
                'totalSum'              => $totalSum,
              ],
            ],
          '#wrapper_attributes'   => ['class' => ['not_hover']],
        ],
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#name'         => 'saveItems',
        '#id'           => 'saveItems',
        '#value'        => $this->basket->Translate()->t('Save'),
        '#ajax'         => $this->itemAjax,
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxReload(array &$form, FormStateInterface $form_state) {
    $response = NULL;
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#name'])) {
      switch ($triggerElement['#name']) {
        case'saveItems':
          $response = new AjaxResponse();
          // Message.
          \Drupal::messenger()->deleteAll();
          $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
            'status',
            \Drupal::service('Basket')->Translate()->t('Settings saved.'),
          ]));
          // Replace info block.
          $storage = $form_state->getStorage();
          if(!empty($storage['Order'])) {
            $order = new Order($storage['Order']->id);
            $response->addCommand(new ReplaceCommand('#order_info_block', $order->getOrderInfoBlock()));
          }
          $response->addCommand(new ReplaceCommand('#basket_order_items_form_ajax_wrap', $form));

          break;
      }
    }
    return !empty($response) ? $response : $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#name'])) {
      switch ($triggerElement['#name']) {
        case'saveItems':
          $storage = $form_state->getStorage();
          $formOrder = $storage['Order'];
          $values = $form_state->getValues();
          // Set goods.
          $totalCount = 0;
          // Set price.
          $paySum = 0;
          // Set add_price.
          $formOrder->add_price = $values['total']['add_price'];
          $paySum += $formOrder->add_price;
          // Set delivery_price.
          $formOrder->delivery_price = $values['total']['delivery_price'];
          $paySum += $formOrder->delivery_price;
          // Items.
          if (!empty($formOrder->items)) {
            foreach ($formOrder->items as &$item) {
              $item->oldItem = clone $item;
              // Set price.
              $item->price = $this->getItemInfo('price', $item->id, $form_state);
              // Set count.
              $item->count = $this->getItemInfo('count', $item->id, $form_state);
              // Set discount.
              $item->discount['percent'] = $this->getItemInfo('discount', $item->id, $form_state);
              // ---
              if(empty($item->isDelete)) {
                $totalCount += $item->count;
                $paySum += $item->price * $item->count - ($item->price * $item->count / 100 * $item->discount['percent']);
              }
              // form_state
              $item->form_state = $form_state;
              $item->form_state_val = $form_state->getValue(['items', $item->id]);
            }
          }
          // ---
          $formOrder->goods = $totalCount;
          $formOrder->price = $paySum;
          // Replace and Save Order.
          $order = $this->basket->Orders($formOrder->id);
          $order->replaceOrder($formOrder);
          $formOrderNew = $order->save();
          // Clear storage.
          if (!empty($storage['itemsDelete'])) {
            unset($storage['itemsDelete']);
          }
          if (!empty($storage['itemsAdd'])) {
            unset($storage['itemsAdd']);
          }
          $storage['Order'] = $formOrderNew;
          $form_state->setStorage($storage);
          $form_state->setRebuild();
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getItemInfo($fieldName, $key, $form_state) {
    $items = $form_state->getValue('items');
    $storage = $form_state->getStorage();
    $val = NULL;
    switch ($fieldName) {
      case'discount':
        $discount = isset($items[$key][$fieldName]) ? $items[$key][$fieldName] : (!empty($storage['Order']->items[$key]->{$fieldName}['percent']) ? $storage['Order']->items[$key]->{$fieldName}['percent'] : 0);
        $val = $discount > 100 ? 100 : $discount;

        break;

      default:
        $val = isset($items[$key][$fieldName]) ? $items[$key][$fieldName] : $storage['Order']->items[$key]->{$fieldName};
        break;
    }
    return $val;
  }

  /**
   * {@inheritdoc}
   */
  private function getTotalInfo($fieldName, $form_state) {
    $storage = $form_state->getStorage();
    $total = $form_state->getValue('total');
    return isset($total[$fieldName]) ? $total[$fieldName] : (!empty($storage['Order']->{$fieldName}) ? $storage['Order']->{$fieldName} : 0);
  }

  /**
   * {@inheritdoc}
   */
  private function itemDelete($itemId, $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    if (isset($triggerdElement['#isDelete']) && $triggerdElement['#isDelete'] === $itemId) {
      $element = [
        'yes'           => [
          '#type'         => 'button',
          '#value'        => $this->basket->Translate()->t('Delete'),
          '#name'         => 'delete_yes_item_' . $itemId,
          '#ajax'         => $this->itemAjax,
          '#attributes'   => [
            'class'         => ['button--delete'],
            'title'         => $this->basket->Translate()->t('Delete'),
          ],
          '#validate'     => [__CLASS__ . '::itemDeleteValid'],
          '#itemId'       => $itemId,
        ],
        'cancel'        => [
          '#type'         => 'button',
          '#value'        => $this->basket->Translate()->t('Cancel'),
          '#name'         => 'delete_cancel_item_' . $itemId,
          '#ajax'         => $this->itemAjax,
          '#attributes'   => [
            'class'         => ['button--cancel'],
            'title'         => $this->basket->Translate()->t('Cancel'),
          ],
        ],
        '#wrapper_attributes' => ['class' => ['items_td_delete']],
      ];
    }
    else {
      $element = [
        '#type'         => 'button',
        '#value'        => $this->basket->Translate()->t('Delete'),
        '#name'         => 'delete_item_' . $itemId,
        '#ajax'         => $this->itemAjax,
        '#isDelete'     => $itemId,
        '#attributes'   => [
          'title'         => $this->basket->Translate()->t('Delete'),
        ],
        '#wrapper_attributes' => ['class' => ['items_td_delete']],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function itemDeleteValid(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    $storage = $form_state->getStorage();
    if (!empty($storage['Order']->items[$triggerdElement['#itemId']])) {
      $storage['Order']->items[$triggerdElement['#itemId']]->isDelete = TRUE;
    }
    $form_state->setStorage($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function itemAddValid(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    $basket = \Drupal::service('Basket');
    if (!empty($triggerdElement['#name']) && $triggerdElement['#name'] == 'addItem') {
      $storage = $form_state->getStorage();
      $addItemInfo = @json_decode($form_state->getValue('addItem'), TRUE);
      if (!empty($addItemInfo['nid'])) {
        $addNode = \Drupal::service('entity_type.manager')->getStorage('node')->load($addItemInfo['nid']);
        $cart = $basket->Cart();
        $addItem = (object) [
          'isNew'         => TRUE,
          'id'            => time() . rand(0, 9999),
          'nid'           => $addItemInfo['nid'],
          'order_nid'     => $storage['Order']->nid,
          'price'         => 0,
          'count'         => !empty($addItemInfo['count']) ? $addItemInfo['count'] : 1,
          'fid'           => 0,
          'params'        => $cart->encodeParams(!empty($addItemInfo['params']) ? $addItemInfo['params'] : []),
          'add_time'      => time(),
          'node_fields'   => [
            'title'         => $addNode->getTitle(),
            'img_uri'       => '',
          ],
          'discount'      => [
            'percent'       => 0,
          ],
        ];
        if (!empty($addItemInfo['params'])) {
          $addItem->params_html = [
            'full'              => \Drupal::service('BasketParams')->getDefinitionParams($addItemInfo['params'], $addItem->nid),
            'inline'            => \Drupal::service('BasketParams')->getDefinitionParams($addItemInfo['params'], $addItem->nid, TRUE),
          ];
        }
        $addItem->price = $cart->getItemPrice($addItem);
        $addItem->fid = $cart->getItemImg($addItem);
        if (!empty($addItem->fid)) {
          $file = File::load($addItem->fid);
          $addItem->node_fields['img_uri'] = $file->getFileUri();
          $addItem->setUriByFid = $addItem->fid;
        }
        $storage['Order']->items[$addItem->id] = $addItem;
        ksort($storage['Order']->items);
        // Save temp.
        $basket->addBasketItemTemp($addItem, $storage['Order']->id);
      }
      $form_state->setStorage($storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function itemParams($info) {
    $element = [
      'inline'        => !empty($info['item']->params_html['inline']) ? $info['item']->params_html['inline'] : [],
    ];
    if (!empty($info['node'])) {
      $element['edit'] = [
        '#type'         => 'textarea',
        '#default_value' => json_encode($info['item']->params),
        '#ajax'         => $this->itemAjax + ['event' => 'change'],
        '#validate'     => [__CLASS__ . '::itemChangeParamsValid'],
        '#parents'      => ['items', $info['item']->id, 'editParams'],
        '#attributes'   => ['style' => 'display:none;'],
      ];
      $element['edit_link'] = [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" class="button--edit_link" onclick="{{ onclick }}" data-post="{{ post }}">{{ ico|raw }}</a>',
        '#context'      => [
          'ico'           => $this->basket->getIco('settings_row.svg', 'base'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-edit_params'])->toString() . '\')',
          'post'          => json_encode([
            'basketItemId'  => $info['item']->id,
            'basketItemNid' => $info['item']->nid,
            'params'        => $info['item']->params,
            'orderId'       => $this->order->id,
          ]),
        ],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function itemChangeParamsValid(array &$form, FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#name']) && strpos($triggerElement['#name'], 'editParams') !== FALSE) {
      $orderID = $triggerElement['#parents'][1];
      $storage = $form_state->getStorage();
      if (!empty($storage['Order']->items[$orderID])) {
        // ---
        $newParams = @json_decode(trim($form_state->getValue($triggerElement['#parents'])), TRUE);
        if (!empty($storage['Order']->items[$orderID]->params) && !is_array($storage['Order']->items[$orderID]->params)) {
          $storage['Order']->items[$orderID]->params = [];
        }
        $storage['Order']->items[$orderID]->params = \Drupal::service('Basket')->arrayMergeRecursive(
          $storage['Order']->items[$orderID]->params,
          $newParams
        );
        $storage['Order']->items[$orderID]->params_html = [
          'full'              => \Drupal::service('BasketParams')->getDefinitionParams($storage['Order']->items[$orderID]->params, $storage['Order']->items[$orderID]->nid),
          'inline'            => \Drupal::service('BasketParams')->getDefinitionParams($storage['Order']->items[$orderID]->params, $storage['Order']->items[$orderID]->nid, TRUE),
        ];
        // ---
        $form_state->setStorage($storage);
      }
    }
  }

}
