<?php

namespace Drupal\basket\Admin\Page;

use Drupal\basket\Admin\BasketDeleteConfirm;
use Drupal\basket\Admin\Form\Order\ItemsForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * {@inheritdoc}
 */
class Order {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set orderClass.
   *
   * @var object
   */
  protected $orderClass;

  /**
   * Set order.
   *
   * @var object
   */
  protected $order;

  /**
   * Set orderNode.
   *
   * @var object
   */
  protected $orderNode;

  /**
   * Set loadTab.
   *
   * @var string
   */
  protected $loadTab = 'order_data';

  /**
   * Set viewType.
   *
   * @var string
   */
  protected $viewType;
  
  /**
   * Set basketAccess.
   *
   * @var Drupal\basket\BasketAccess
   */
  protected $basketAccess;

  /**
   * {@inheritdoc}
   */
  public function __construct($orderId = NULL, $view = 'full') {
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketAccess = \Drupal::getContainer()->get('BasketAccess');
    $this->orderClass = $this->basket->Orders($orderId);
    $this->order = $this->orderClass->load();
    if (!empty($this->order->nid)) {
      $this->orderNode = \Drupal::entityTypeManager()->getStorage('node')->load($this->order->nid);
      $this->orderNode->basket_admin_process = $this->order;
    }
    if (!empty($this->order) && is_numeric($this->order->id) && empty($this->order->first_view_uid) && $view == 'full') {
      $this->orderClass->set('first_view_uid', \Drupal::currentUser()->id());
      $this->orderClass->save();
    }
    if (!empty($this->order->id) && $this->order->id == 'NEW') {
      $this->orderNode = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
          'type'      => 'basket_order',
          'status'    => FALSE,
          'title'     => 'Order',
        ]);
      $this->orderNode->basket_admin_process = $this->order;
      $this->orderNode->basket_create_order = TRUE;
    }
    $query = \Drupal::request()->query->all();
    if (!empty($query['tab'])) {
      $this->loadTab = $query['tab'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function edit() {
    if (empty($this->order)) {
      return $this->basket->getError(404);
    }
    if (!$this->basketAccess->hasPermission('basket access_edit_order', [
			'orderId'     => $this->order->id
    ])) {
      return $this->basket->getError(403);
    }
    $this->viewType = 'edit';
    return [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
      'links'         => $this->allLinks() + [
        '#prefix'       => '<div class="b_title">',
        '#suffix'       => '</div>',
        '#markup'       => $this->basket->Translate()->t('Order ID: @num@', ['@num@' => $this->basket->Orders($this->order->id)->getId()]),
      ], [
        '#prefix'       => '<div class="b_content">',
        '#suffix'       => '</div>',
        'order_info'    => $this->getOrderInfoBlock(),
        'tabs'          => $this->getTabs($this->loadTab),
        'content'       => $this->getContent($this->loadTab) + [
          '#prefix'       => '<div id="basket_order_edit_tab_content">',
          '#suffix'       => '</div>',
        ],
      ],
      '#cache'            => [
        'max-age'           => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function view() {
    $this->viewType = 'view';
    if (empty($this->order)) {
      return $this->basket->getError(404);
    }
    return [
      '#prefix'        => '<div class="basket_table_wrap">',
      '#suffix'        => '</div>',
      'links'         => $this->allLinks() + [
        '#prefix'        => '<div class="b_title">',
        '#suffix'        => '</div>',
        '#markup'        => $this->basket->Translate()->t('Order ID: @num@', ['@num@' => $this->basket->Orders($this->order->id)->getId()]),
      ], [
        '#prefix'        => '<div class="b_content">',
        '#suffix'        => '</div>',
        'order_info'    => $this->getOrderInfoBlock(),
        'order_view'    => $this->getOrderInfoBlock('order_view_info'),
      ],
      '#cache'            => [
        'max-age'            => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function allLinks($entity = NULL) {
    if (empty($this->order)) {
      if(!empty($entity)) {
        return [
          'delete'    => [
            '#type'         => 'inline_template',
            '#template'     => '<a href="{{ url }}" class="button--link"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
            '#context'      => [
              'text'          => $this->basket->Translate()->t('Delete'),
              'ico'           => $this->basket->getIco('trash.svg'),
              'url'           => Url::fromRoute('entity.node.delete_form', ['node' => $entity->id()], ['query' => \Drupal::destination()->getAsArray()])
            ],
          ]
        ];
      }
      return [];
    }
    $links = [
      'edit'        => $this->getEditLink(),
      'delete'      => $this->getDeleteLink(),
      'restore'     => $this->getRestoreLink(),
      'permDelete'  => $this->getPermanentlyDeleteLink(),
      'waybill'     => $this->basket->Waybill($this->order->id)->getLink(),
      'export'      => $this->basket->getClass('Drupal\basket\BasketExport')->getLinkOrderExport($this->order->id),
    ];
    // Alter.
    \Drupal::moduleHandler()->alter('basket_order_links', $links, $this->order);
    // ---
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditLink() {
    if ($this->viewType == 'edit') {
      return [];
    }
    $context = $this->getEditLinkContext();
    if (empty($context)) {
      return [];
    }
    return [
      '#type'         => 'inline_template',
      '#template'        => '<a href="{{ url }}" class="button--link"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
      '#context'         => $context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEditLinkContext() {
    if (empty($this->order)) {
      return [];
    }
    if (!\Drupal::currentUser()->hasPermission('basket access_edit_order')) {
      return [];
    }
    if (!empty($this->order->is_delete)) {
      return [];
    }
    return [
      'text'            => $this->basket->Translate()->t('Edit'),
      'ico'            => $this->basket->getIco('edit.svg'),
      'url'            => Url::fromRoute('basket.admin.pages', ['page_type' => 'orders-edit-' . $this->order->id])->toString(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteLink() {
    $context = $this->getDeleteContext();
    if (empty($context)) {
      return [];
    }
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="javascript:void(0);" class="button--link" onclick="{{ onclick }}" data-post="{{ post }}"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
      '#context'         => $context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteContext() {
    if (empty($this->order)) {
      return [];
    }
    if (!empty($this->order->is_delete)) {
      return [];
    }
    if ($this->order->id == 'NEW') {
      return [];
    }
    if (!\Drupal::currentUser()->hasPermission('basket access_delete_order')) {
      return [];
    }
    return [
      'text'            => $this->basket->Translate()->t('Delete'),
      'ico'            => $this->basket->getIco('trash.svg'),
      'onclick'        => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-delete'])->toString() . '\')',
      'post'            => json_encode([
        'orderId'        => $this->order->id,
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRestoreLink() {
    $context = $this->getRestoreContext();
    if (empty($context)) {
      return [];
    }
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="javascript:void(0);" class="button--link" onclick="{{ onclick }}" data-post="{{ post }}"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
      '#context'         => $context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRestoreContext() {
    if (empty($this->order->is_delete)) {
      return [];
    }
    if (!\Drupal::currentUser()->hasPermission('basket access_restore_order')) {
      return [];
    }
    return [
      'text'            => $this->basket->Translate()->t('Restore'),
      'ico'            => $this->basket->getIco('restore.svg'),
      'onclick'        => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-restore'])->toString() . '\')',
      'post'            => json_encode([
        'orderId'        => $this->order->id,
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermanentlyDeleteLink() {
    $context = $this->getPermanentlyDeleteContext();
    if (empty($context)) {
      return [];
    }
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="javascript:void(0);" class="button--link" onclick="{{ onclick }}" data-post="{{ post }}"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
      '#context'         => $context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermanentlyDeleteContext() {
    if (empty($this->order->is_delete)) {
      return [];
    }
    if (!\Drupal::currentUser()->hasPermission('basket access_trash_clear_page')) {
      return [];
    }
    return [
      'text'            => $this->basket->Translate()->t('Permanently remove'),
      'ico'            => $this->basket->getIco('trash.svg'),
      'onclick'        => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-permanently_delete'])->toString() . '\')',
      'post'            => json_encode([
        'orderId'        => $this->order->id,
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderInfoBlock($template = 'order_info') {
    $settings = $this->basket->getSettings('templates', $template);
    if (empty($settings['config']['template'])) {
      return [
        '#prefix'            => '<div>',
        '#suffix'            => '</div>',
      ];
    }
    $html = [
      '#type'                => 'inline_template',
      '#template'        => $settings['config']['template'],
      '#context'        => $this->basket->MailCenter()->getContext($template, [
        'order'                => $this->order,
      ]),
    ];
    $html = \Drupal::service('renderer')->render($html);
    $html = \Drupal::token()->replace(
    $html, [
      'user'        => !empty($this->orderNode) ? \Drupal::service('entity_type.manager')->getStorage('user')->load($this->orderNode->get('uid')->target_id) : NULL,
      'node'        => $this->orderNode,
    ], [
      'clear'     => TRUE,
    ]
    );
    return [
      '#markup'        => Markup::create($html),
      '#prefix'        => '<div id="order_info_block">',
      '#suffix'        => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getTabs($loadTab) {
    $items = [
      'order_data'    => [
        'name'            => $this->basket->Translate()->t('Order data'),
        'disabled'        => FALSE,
      ],
      'products'        => [
        'name'            => $this->basket->Translate()->t('Products'),
        'disabled'        => $this->order->id == 'NEW',
      ],
    ];
    $items[$loadTab]['class'][] = 'is-active';
    return [
      '#type'                    => 'inline_template',
      '#template'            => '<div class="order_tabs">
				{% for id, item in items %}
					<a href="javascript:void(0);" class="{{ item.class|join(\' \') }}" onclick="{% if not item.disabled %}{{ onclick }}{% endif %}" data-tab_id="{{ id }}" data-post="{{ {\'tab\': id, \'orderId\': order.id }|json_encode }}">{{ item.name }}</a>
				{% endfor %}
			</div>',
      '#context'            => [
        'items'                    => $items,
        'onclick'                => 'basket_order_edit_load_tab(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-load_tab'])->toString() . '\')',
        'order'                    => $this->order,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getContent($loadTab) {
    $element = [
      'tab'        => [
        '#prefix'        => '<div class="tab_content" data-tab-content="' . $loadTab . '">',
        '#suffix'        => '</div>',
      ],
    ];
    if (!empty($this->order->is_delete)) {
      $element['tab'][] = $this->basket->getError(403);
      return $element;
    }
    if (empty($this->orderNode)) {
      $element['tab'][] = $this->basket->getError(404);
    }
    else {
      switch ($loadTab) {
        case'order_data':
          $element['tab'][] = \Drupal::service('entity.form_builder')->getForm($this->orderNode);
          break;

        case'products':
          if (empty($this->orderNode->id())) {
            $element['tab'][] = $this->basket->getError(404);
          }
          else {

            $form = new ItemsForm($this->order);
            $element['tab'][] = \Drupal::formBuilder()->getForm($form);
          }
          break;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeFormAlter(&$form, $form_state) {
    $storage = $form_state->getStorage();
    if (empty($storage['FormOrder']) && !empty($this->order)) {
      $storage['FormOrder'] = $this->order;
      $form_state->setStorage($storage);
    }
    $form['tab'] = [
      '#type'                => 'hidden',
      '#value'            => 'order_data',
    ];
    $form['orderId'] = [
      '#type'                => 'hidden',
      '#value'            => !empty($this->order) ? $this->order->id : 'NEW',
    ];
    $form['actions']['submit']['#name'] = 'adminSave';
    $form['actions']['submit']['#submit'][] = __CLASS__ . '::orderFullSubmit';
    $form['actions']['submit']['#ajax'] = [
      'wrapper'       => 'basket_node_basket_order_form_ajax_wrap',
      'callback'      => __CLASS__ . '::submitAjax',
      'url'           => new Url('basket.admin.pages', ['page_type' => 'api-orders-basket_order_form']),
      'options'       => [
        'query'         => \Drupal::request()->query->All() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE],
      ],
    ];
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    if (!empty($entity->basket_create_order)) {
      $options = $this->basket->Currency()->getOptions();
      $form['order_set_currency'] = [
        '#type'                => 'select',
        '#title'            => $this->basket->Translate()->t('Currency'),
        '#required'        => TRUE,
        '#options'        => $options,
        '#weight'            => 100,
        '#default_value' => !empty($options) ? key($options) : NULL,
      ];
      $form['actions']['submit']['#value'] = $this->basket->Translate()->t('Save and add products');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function orderFullSubmit($form, $form_state) {
    // Finish save order.
    $storage = $form_state->getStorage();
    if (!empty($storage['FormOrder'])) {
      $orderClass = \Drupal::service('Basket')->Orders($storage['FormOrder']->id);
      $order = $orderClass->load();
      if (!empty($order)) {

        $storage['FormOrder'] = clone $order;

        $orderClass->refresh();
        $orderClass->replaceOrder($storage['FormOrder'], TRUE);
        $orderClass->save();

        $form_state->setStorage($storage);
      }
    }
    // ---
  }

  /**
   * {@inheritdoc}
   */
  public static function submitAjax($form, $form_state) {
    $response = new AjaxResponse();
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    $basket = \Drupal::service('Basket');
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $response->addCommand(new HtmlCommand('#' . $form['actions']['submit']['#ajax']['wrapper'], $form));
    }
    else {
      if (!empty($entity->basket_create_order)) {
        // Create order.
        $orderId = \Drupal::database()->insert('basket_orders')
          ->fields([
            'nid'           => $entity->id(),
            'price'         => 0,
            'goods'         => 0,
            'currency'      => $form_state->getValue('order_set_currency'),
            'status'        => $basket->Term()->getDefaultNewOrder('status'),
            'fin_status'    => $basket->Term()->getDefaultNewOrder('fin_status'),
          ])
          ->execute();
        // Message.
        \Drupal::messenger()->deleteAll();
        $response->addCommand(new RedirectCommand(Url::fromRoute('basket.admin.pages', [
          'page_type'       => 'orders-edit-' . $orderId,
        ], [
          'query'           => [
            'tab'             => 'products',
          ],
        ])->toString()));
      }
      else {
        // Message.
        \Drupal::messenger()->deleteAll();
        $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
          'status',
          $basket->Translate()->t('Settings saved.'),
        ]));
        // Return form.
        unset($form['#prefix'], $form['#suffix']);
        $response->addCommand(new HtmlCommand('#' . $form['actions']['submit']['#ajax']['wrapper'], $form));
        $response->addCommand(new InvokeCommand('input[name="changed"]', 'val', [$entity->get('changed')->value]));

        $cls = new static($entity->basket_admin_process->id);
        $response->addCommand(new ReplaceCommand('#order_info_block', $cls->getOrderInfoBlock()));
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter($response, $api_type = NULL, $api_subtype = NULL) {
    switch ($api_type) {
      case'orders':
        switch ($api_subtype) {
          case'delete':
            if (!empty($this->order->nid) && \Drupal::currentUser()->hasPermission('basket access_delete_order')) {
              if (!empty($_POST['confirm'])) {
                /*Update order*/
                $this->orderClass->set('is_delete', 1);
                $this->orderClass->save();
                /*END update*/
                \Drupal::messenger()->addMessage(
                $this->basket->Translate()->t('Order №@num@ deleted successfully.', ['@num@' => $this->basket->Orders($this->order->id)->getId()]),
                'status'
                );
                $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
              }
              else {
                \Drupal::service('BasketPopup')->openModal(
                  $response,
                  $this->basket->Translate()->t('Delete') . ' "' . $this->basket->Translate()->t('Order ID: @num@', ['@num@' => $this->basket->Orders($this->order->id)->getId()]) . '"',
                  BasketDeleteConfirm::confirmContent([
                    'onclick'            => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-delete'])->toString() . '\')',
                    'post'                => json_encode([
                      'orderId'            => $this->order->id,
                      'confirm'            => 1,
                    ]),
                  ]),
                  [
                    'width' => 400,
                    'class' => [],
                  ]
                );
              }
            }
            break;

          case'permanently_delete':
            if (!empty($this->order->nid) && \Drupal::currentUser()->hasPermission('basket access_trash_clear_page')) {
              if (!empty($_POST['confirm'])) {
                /*Update order*/
                $this->orderClass->delete();
                /*END update*/
                \Drupal::messenger()->addMessage(
                $this->basket->Translate()->t('Order №@num@ deleted successfully.', ['@num@' => $this->basket->Orders($this->order->id)->getId()]),
                'status'
                );
                $response->addCommand(new RedirectCommand(Url::fromRoute('basket.admin.pages', ['page_type' => 'trash'])->toString()));
              }
              else {
                \Drupal::service('BasketPopup')->openModal(
                    $response,
                  $this->basket->Translate()->t('Delete') . ' "' . $this->basket->Translate()->t('Order ID: @num@', ['@num@' => $this->basket->Orders($this->order->id)->getId()]) . '"',
                    BasketDeleteConfirm::confirmContent([
                      'onclick'            => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-permanently_delete'])->toString() . '\')',
                      'post'                => json_encode([
                        'orderId'            => $this->order->id,
                        'confirm'            => 1,
                      ]),
                    ]),
                    [
                      'width' => 400,
                      'class' => [],
                    ]
                   );
              }
            }
            break;

          case'restore':
            if (!empty($this->order->nid) && \Drupal::currentUser()->hasPermission('basket access_restore_order')) {
              /*Update order*/
              $this->orderClass->set('is_delete', NULL);
              $this->orderClass->save();
              /*END update*/
              \Drupal::messenger()->addMessage(
              $this->basket->Translate()->t('Order №@num@ successfully restored.', ['@num@' => $this->basket->Orders($this->order->id)->getId()]),
              'status'
              );
              $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
            }
            break;

          case'load_tab':
            if (!empty($_POST['tab']) && !empty($this->order)) {
              $content = $this->getContent($_POST['tab']);
              $response->addCommand(new AppendCommand('#basket_order_edit_tab_content', $content));
            }
            break;

          case'basket_order_items_form':
            if (!empty($_POST['orderId'])) {
              $form = new ItemsForm($this->order);
              return \Drupal::formBuilder()->getForm($form);
            }
            break;

          case'basket_order_form':
            if (!empty($this->orderNode)) {
              return \Drupal::service('entity.form_builder')->getForm($this->orderNode);
            }
            break;

          case'add_goods_popup':
            \Drupal::service('BasketPopup')->openModal(
              $response,
              $this->basket->Translate()->t('Add a product'),
              [
                '#prefix'        => '<div class="basket_table_wrap">',
                '#suffix'        => '</div>',
                  [
                    '#prefix'        => '<div class="b_content">',
                    '#suffix'        => '</div>',
                    'view'            => $this->basket->getView('basket', 'block_2'),
                  ],
              ],
              [
                'width'     => 960,
                'class'     => [],
              ]
            );
            break;

          case'addOrderItem':
            $response->addCommand(new AppendCommand('body', '<script>' . \Drupal::service('BasketPopup')->getCloseOnclick() . '</script>'));
            if (!empty($_POST['addItem'])) {
              $response->addCommand(new InvokeCommand('textarea[name="addItem"]', 'val', [json_encode($_POST['addItem'])]));
              $response->addCommand(new InvokeCommand('textarea[name="addItem"]', 'trigger', ['change']));
            }
            break;
        }
        break;
    }
  }

  /**
   * Views table alter block 2.
   */
  public function viewsTableAlterBlock2(&$vars) {
    $cart = $this->basket->Cart();
    $vars['header'][] = '';
    foreach ($vars['rows'] as $key => &$line) {
      if (empty($line['columns'])) {
        continue;
      }
      if (empty($vars['view']->result[$key]->_entity)) {
        continue;
      }
      // ---
      $cartLine = (object) [
        'nid'            => $vars['view']->result[$key]->_entity->id(),
        'id'            => $key,
      ];
      // ---
      foreach ($line['columns'] as $keyField => &$row) {
        switch ($keyField) {
          case'title':
            $row['content'][0]['field_output'] = [
              '#type'                    => 'inline_template',
              '#template'            => '<a href="{{ url(\'entity.node.canonical\', {\'node\':entity.id}) }}">{% if image %}{{ basket_image(image, \'thumbnail\') }}{% endif %}  <b>{{ entity.getTitle() }}</a></b>',
              '#context'            => [
                'image'                    => $cart->getItemImg($cartLine),
                'entity'                => $vars['view']->result[$key]->_entity,
              ],
            ];
            break;
        }
      }
      $line['columns'][] = [
        'content'                => [
       [
         'field_output'        => [
           '#type'                    => 'inline_template',
           '#template'            => '<a href="javascript:void(0);" class="form-submit nowrap" onclick="{{onclick}}" data-post="{{post}}">+ {{text}}</a>',
           '#context'                => [
             'text'                        => $this->basket->Translate()->t('Add to order'),
             'onclick'                => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-orders-addOrderItem'])->toString() . '\')',
             'post'                        => json_encode([
               'orderId'                => $this->order->id,
               'addItem'                => [
                 'nid'                        => $vars['view']->result[$key]->_entity->id(),
                 'params'                    => [],
               ],
             ]),
           ],
         ],
       ],
        ],
      ];
    }
  }

}
