<?php

namespace Drupal\basket\Admin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\basket\Admin\Page\Order;
use Drupal\basket\Admin\Page\Trash;
use Drupal\basket\Admin\Page\StatisticsBuyers;
use Drupal\basket\Admin\Page\NodeEdit;
use Drupal\basket\Admin\Page\StockProduct;
use Drupal\basket\Admin\Form\ConfirmDeleteForm;
use Drupal\basket\Admin\Page\PaymentPage;
use Drupal\basket\Plugin\views\filter\BasketOrderFilter;
use Drupal\basket\Admin\Page\FreeAdditional;

/**
 * Admin page controller.
 */
class Pages {

  /**
   * Set createLink.
   *
   * @var array
   */
  protected static $createLink;

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;
  
	/**
   * Set basketAccess.
   *
   * @var Drupal\basket\BasketAccess
   */
	protected $basketAccess;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketAccess = \Drupal::getContainer()->get('BasketAccess');
  }
  
  /**
   * {@inheritdoc}
   */
  public function access($page_type = NULL) {
    return AccessResult::allowedIf($this->basketAccess->hasPermission('basket order_access', [
      'page_type' => $page_type
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function page($page_type = 'orders', $setContent = NULL) {
    $page_subtype = $page_subtype1 = NULL;
    if (strpos($page_type, '-') !== FALSE) {
      @list($page_type, $page_subtype, $page_subtype1) = explode('-', $page_type);
    }
    $element = [
      '#theme'        => 'basket_admin_page',
      '#info'         => [
        'menu'          => ManagerMenu::block(),
        'header'        => [],
        'content'       => [
          'add_block'     => $this->addBlockLine(),
        ],
      ],
    ];
    switch ($page_type) {
      case'orders':
        switch ($page_subtype) {
          case'edit':
          case'view':
            $order = new Order($page_subtype1);
            $element['#info']['content'][] = $order->{$page_subtype}();
            break;

          case'add':
            $order = new Order('NEW');
            $element['#info']['content'][] = $order->edit();
            break;

          case'waybill':
            $element['#info']['content'][] = $this->basket->Waybill($page_subtype1)->getPdfView();
            break;

          case'export':
            $element['#info']['content'][] = $this->basket->getClass('Drupal\basket\BasketExport')->run($page_subtype1);
            break;

          default:
            $element['#info']['content'][] = $this->basket->getClass('Drupal\basket\Admin\Page\Orders')->page();
            break;
        }
        break;

      case'trash':
        $trash = new Trash();
        switch ($page_subtype) {
          case'restore':
            $element['#info']['content'][] = $trash->restoreBath($page_subtype1);
            break;

          case'delete':
            $element['#info']['content'][] = $trash->deleteBath($page_subtype1);
            break;

          default:
            $element['#info']['content'][] = $trash->page();
            break;
        }
        break;

      case'statistics':
        if (!empty($page_subtype)) {
          switch ($page_subtype) {
            case'buyers':
              if (!empty($page_subtype1)) {
                switch ($page_subtype1) {
                  case'add':
                  case'edit':
                    if (!\Drupal::currentUser()->hasPermission('administer users')) {
                      $element['#info']['content']['view'] = $this->basket->getError(403);
                    }
                    else {
                      switch ($page_subtype1) {
                        case'add':
                          $entity = \Drupal::service('entity_type.manager')->getStorage('user')->create([]);
                          $entity->basket_create_user = TRUE;
                          $formObject = \Drupal::service('entity_type.manager')->getFormObject('user', 'register')->setEntity($entity);
                          $element['#info']['content']['view'] = [
                            '#prefix'       => '<div class="basket_table_wrap">',
                            '#suffix'       => '</div>',
                            'title'         => [
                              '#prefix'       => '<div class="b_title">',
                              '#suffix'       => '</div>',
                              '#markup'       => $this->basket->Translate()->t('Add a user'),
                            ],
                            'content'       => [
                              '#prefix'       => '<div class="b_content">',
                              '#suffix'       => '</div>',
                              'form'          => \Drupal::formBuilder()->getForm($formObject),
                            ],
                          ];
                          break;

                        case'edit';
                          $editUser = NULL;
                          $uid = \Drupal::request()->query->get('uid');
                          if (!empty($uid)) {
                            $editUser = \Drupal::service('entity_type.manager')->getStorage('user')->load($uid);
                          }
                          if (empty($editUser)) {
                            $element['#info']['content']['view'] = $this->basket->getError(404);
                          }
                          else {
                            $editUser->basket_create_user = TRUE;
                            $formObject = \Drupal::service('entity_type.manager')->getFormObject('user', 'default')->setEntity($editUser);
                            $element['#info']['content']['view'] = [
                              '#prefix'       => '<div class="basket_table_wrap">',
                              '#suffix'       => '</div>',
                              'title'         => [
                                '#prefix'       => '<div class="b_title">',
                                '#suffix'       => '</div>',
                                '#markup'       => $this->basket->Translate()->t('Add a user'),
                              ],
                              'content'       => [
                                '#prefix'       => '<div class="b_content">',
                                '#suffix'       => '</div>',
                                'form'          => \Drupal::formBuilder()->getForm($formObject),
                              ],
                            ];
                          }
                          break;
                      }
                    }
                    break;
                }
              }
              else {
                $statisticsBuyers = new StatisticsBuyers();
                $element['#info']['content']['view'] = $statisticsBuyers->page();
              }
              break;
          }
        }
        break;

      case'stock':
        if (!empty($page_subtype)) {
          switch ($page_subtype) {
            case'create':
              if (!empty($page_subtype1)) {
                $nodeType = \Drupal::service('entity_type.manager')->getStorage('node_type')->load($page_subtype1);
                if (!empty($nodeType)) {
                  $entity = \Drupal::entityTypeManager()
                    ->getStorage('node')
                    ->create(['type' => $page_subtype1]);
                  $element['#info']['content']['view'] = [
                    '#prefix'       => '<div class="basket_table_wrap">',
                    '#suffix'       => '</div>',
                    'title'         => [
                      '#prefix'       => '<div class="b_title">',
                      '#suffix'       => '</div>',
                      '#markup'       => $this->basket->Translate()->t('Add a product') . ' "' . $nodeType->label() . '"',
                    ],
                    'content'       => [
                      '#prefix'       => '<div class="b_content">',
                      '#suffix'       => '</div>',
                      'form'          => \Drupal::service('entity.form_builder')->getForm($entity),
                    ],
                  ];
                }
              }
              break;

            case'edit':
              if (!empty($page_subtype1)) {
                $nodeEdit = new NodeEdit($page_subtype1);
                $element['#info']['content']['view'] = $nodeEdit->editPage();
              }
              break;

            case'product':
              $stockProduct = new StockProduct();
              $element['#info']['content']['view'] = $stockProduct->page();
              break;

            case'delete':
              $element['#info']['content']['view'] = [
                '#prefix'       => '<div class="basket_table_wrap">',
                '#suffix'       => '</div>',
                'content'       => [
                  '#prefix'       => '<div class="b_content">',
                  '#suffix'       => '</div>',
                  'form'          => \Drupal::formBuilder()->getForm(new ConfirmDeleteForm($page_subtype1)),
                ],
              ];
              break;
          }
        }
        break;

      case'batch':
        $current_batch = _batch_current_set();
        $setContent['#prefix'] = '<div class="b_content">';
        $setContent['#suffix'] = '</div>';
        $element['#info']['content'][] = [
          '#prefix'       => '<div class="basket_table_wrap">',
          '#suffix'       => '</div>',
          [
            '#prefix'        => '<div class="b_title">',
            '#suffix'        => '</div>',
            '#markup'        => $current_batch['title'],
          ],
        ];
        $element['#info']['content'][][] = $setContent;
        break;

      case'settings':
        if (!empty($page_subtype)) {
          switch ($page_subtype) {
            case'extra':
            case'base':
            case'statuses':
              $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\SubPages')->list($page_subtype);
              break;

            case'status':
            case'fin_status':
              if (!\Drupal::currentUser()->hasPermission('basket access_page ' . $page_subtype)) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\StatusPage')->table($page_subtype);
              }
              break;

            case'delivery':
              if (!\Drupal::currentUser()->hasPermission('basket access_page delivery')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\DeliveryPage')->table();
              }
              break;

            case'payment':
              if (!\Drupal::currentUser()->hasPermission('basket access_page payment')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = (new PaymentPage())->table();
              }
              break;

            case'text':
              if (!\Drupal::currentUser()->hasPermission('basket access_page text')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\TextSettingsForm'),
                  ],
                ];
              }
              break;

            case'currency':
              if (!\Drupal::currentUser()->hasPermission('basket access_page currency')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\CurrencyPage')->table();
              }
              break;

            case'node_types':
              if (!\Drupal::currentUser()->hasPermission('basket access_page node_types')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\NodeTypes')->table();
              }
              break;

            case'templates':
              if (!\Drupal::currentUser()->hasPermission('basket access_page template')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\Templates')->page($page_subtype1);
              }
              break;

            case'templates_preview':
              if (!\Drupal::currentUser()->hasPermission('basket access_page template')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = $this->basket->getClass('Drupal\basket\Admin\Page\Templates')->preview($page_subtype1);
              }
              break;

            case'order_form':
              if (!\Drupal::currentUser()->hasPermission('basket access_page order_form')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\SettingsOrderForm'),
                  ],
                ];
              }
              break;

            case'order_page':
              if (!\Drupal::currentUser()->hasPermission('basket access_page order_page')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\SettingsOrderPage'),
                  ],
                ];
              }
              break;

            case'notifications':
              if (!\Drupal::currentUser()->hasPermission('basket access_page notifications')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\SettingsNotificationsForm'),
                  ],
                ];
              }
              break;

            case'export_orders':
              if (!\Drupal::currentUser()->hasPermission('basket access_page export_orders')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\SettingsExportOrdersForm'),
                  ],
                ];
              }
              break;

            case'permissions':
              if (!\Drupal::currentUser()->hasPermission('basket settings permissions rights')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\UserPermissionsForm'),
                  ],
                ];
              }
              break;

            case'popup_plugin':
              if (!\Drupal::currentUser()->hasPermission('basket access_page popup_plugin')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\PopupPluginForm'),
                  ],
                ];
              }
              break;

            case'discount_system':
            case'discount_range':
              if (!\Drupal::currentUser()->hasPermission('basket access_page discount_system')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                switch ($page_subtype) {
                  case'discount_system':
                    $element['#info']['content']['view'] = [
                      '#prefix'       => '<div class="basket_table_wrap">',
                      '#suffix'       => '</div>',
                      'content'       => [
                        '#prefix'       => '<div class="b_content">',
                        '#suffix'       => '</div>',
                        'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\DiscountSystemForm'),
                      ],
                    ];
                    break;

                  case'discount_range':
                    $element['#info']['content']['view'] = [
                      '#prefix'       => '<div class="basket_table_wrap">',
                      '#suffix'       => '</div>',
                      'content'       => [
                        '#prefix'       => '<div class="b_content">',
                        '#suffix'       => '</div>',
                        'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Plugin\Basket\Discount\Form\DiscountRangeForm'),
                      ],
                    ];
                    break;
                }
              }
              break;

            case'empty_trash':
              if (!\Drupal::currentUser()->hasPermission('basket access_page empty_trash')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\EmptyTrashSettingsForm'),
                  ],
                ];
              }
              break;

            case'appearance':
              if (!\Drupal::currentUser()->hasPermission('basket access_page appearance')) {
                $element['#info']['content']['view'] = $this->basket->getError(403);
              }
              else {
                $element['#info']['content']['view'] = [
                  '#prefix'       => '<div class="basket_table_wrap">',
                  '#suffix'       => '</div>',
                  'content'       => [
                    '#prefix'       => '<div class="b_content">',
                    '#suffix'       => '</div>',
                    'form'          => \Drupal::formBuilder()->getForm('\Drupal\basket\Admin\Form\AppearanceSettingsForm'),
                  ],
                ];
              }
              break;
          }
        }
        break;

      case'additional':
        if (!empty($page_subtype)) {
          switch ($page_subtype) {
            /*
             * Free additional features
             */
            case'free':
              $element['#info']['content']['view'] = (new FreeAdditional)->view();
              break;
          }
        }
        break;

      case'api':
        $response = new AjaxResponse();
        if (!empty($page_subtype)) {
          switch ($page_subtype) {
            /*
             * Orders
             */
            case'order_change_status':
              if (!empty($_POST['orderID']) && !empty($_POST['status_type']) && !empty($_POST['set_val'])) {
                $term = $this->basket->term()->load($_POST['set_val']);
                if ($term->type == $_POST['status_type']) {
                  if ($this->basketAccess->hasPermission('basket edit_' . $_POST['status_type'] . '_order_access', [
										'orderId'     => $_POST['orderID']
                  ])) {
                    $order = $this->basket->orders($_POST['orderID']);
                    $order->set($_POST['status_type'], $_POST['set_val']);
                    $order->save();
                  }
                  if (!empty($term)) {
                    $new_html = $this->basket->textColor(
                      $this->basket->translate()->trans($term->name),
                      $term->color,
                      [
                        'class'     => ['status_' . $term->type . '_' . $_POST['orderID']],
                      ]
                    );
                    $response->addCommand(new InvokeCommand('.status_' . $_POST['status_type'] . '_' . $_POST['orderID'], 'replaceWith', [render($new_html)]));
                  }
                }
                if ($_POST['status_type'] == 'status') {
                  $block = $this->basket->full('getStatisticsBlock', [TRUE]);
                  if (!empty($block)) {
                    $response->addCommand(new ReplaceCommand('.basket_admin_orders_stat_block', \Drupal::service('renderer')->render($block)));
                  }
                }
              }
              break;

            case'orders_filter_settings':
              BasketOrderFilter::apiResponseAlter($response);
              break;

            case'orders_tabs_settings':
            case'orders_stat_block_settings':
              $this->basket->getClass('Drupal\basket\Admin\Page\Orders')->apiResponseAlter($response, $page_subtype);
              break;

            case'orders':
              $order = NULL;
              if (!empty($_POST['orderId'])) {
                $order = $this->basket->orders($_POST['orderId'])->load();
                $orderClass = new Order($_POST['orderId']);
                $orderClass->apiResponseAlter($response, $page_subtype, $page_subtype1);
              }
              if (!empty($_POST['basketItemId'])) {
                $basketItem = $this->basket->loadBasketItem($_POST['basketItemId'], $_POST['orderId'] ?? NULL);
                if(empty($basketItem)) {
                  $basketItem = (object) [
                    'id'          => $_POST['basketItemId'],
                    'nid'         => $_POST['basketItemNid'],
                    'order_nid'   => $order->nid ?? 0
                  ];
                }
                $basketItem->orderId = $_POST['orderId'] ?? NULL;
                if (!empty($basketItem)) {
                  $orderItem = $this->basket->BasketOrderItems($this->basket->orders('NEW')->load());
                  $params = $_POST['params'] ?? NULL;
                  $orderItem->apiResponseAlter($response, $page_subtype1, $basketItem, $params);
                }
              }
              break;

            /*
             * Text settings
             */
            case'text_get_translate':
            case'translation_popup':
            case'text_delete_string':
              $this->basket->getClass('Drupal\basket\Admin\Form\TextSettingsForm')->apiResponseAlter($response);
              break;

            /*
             * Terms
             */
            case'create_term':
            case'edit_term':
            case'delete_term':
              if (\Drupal::currentUser()->hasPermission('basket access_page ' . $_POST['type'])) {
                $this->basket->getClass('Drupal\basket\Admin\Page\StatusPage')->apiResponseAlter($response);
              }
              break;

            /*
             * Currency
             */
            case'create_currency':
            case'edit_currency':
            case'delete_currency':
              if (\Drupal::currentUser()->hasPermission('basket access_page currency')) {
                $this->basket->getClass('Drupal\basket\Admin\Page\CurrencyPage')->apiResponseAlter($response);
              }
              break;

            /*
             * Node type settings
             */
            case'add_node_type':
            case'delete_node_type':
              if (\Drupal::currentUser()->hasPermission('basket access_page node_types')) {
                $this->basket->getClass('Drupal\basket\Admin\Page\NodeTypes')->apiResponseAlter($response);
              }
              break;

            /*
             * Delivery
             */
            case'create_delivery':
            case'edit_delivery':
              if (\Drupal::currentUser()->hasPermission('basket access_page delivery')) {
                $this->basket->getClass('Drupal\basket\Admin\Page\DeliveryPage')->apiResponseAlter($response, $page_subtype);
              }
              break;

            /*
             * Payment
             */
            case'create_payment':
            case'edit_payment':
              if (\Drupal::currentUser()->hasPermission('basket access_page payment')) {
                $paymentPage = new PaymentPage();
                $paymentPage->apiResponseAlter($response, $page_subtype);
              }
              break;

            /*
             * Operations
             */
            case'operations':
              $operations = new Operations();
              $operations->apiResponseAlter($response, $page_subtype);
              break;

            /*
             * Help
             */
            case'help':
              \Drupal::service('BasketPopup')->openModal(
                $response,
                $this->basket->Translate()->t('Functional activation'),
                [
                  '#prefix'        => '<div class="help_content">',
                  '#suffix'        => '</div>',
                  '#markup'        => $this->basket->Translate()->t('To activate the functionality, write @mail@', [
                    '@mail@'        => Markup::create('<a href="mailto:' . $this->basket->getMail() . '">' . $this->basket->getMail() . '</a>'),
                  ]),
                ], [
                  'width' => 960,
                  'class' => [],
                ]
              );
              break;

            /*
             * Restore nodes
             */
            case'node_restore':
              if (!empty($_POST['nid'])) {
                \Drupal::database()->delete('basket_node_delete')
                  ->condition('nid', $_POST['nid'])
                  ->execute();
                $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
                \Drupal::messenger()->addMessage($this->basket->Translate()->t('Item restored but not active!'), 'warning');
              }
              break;
          }
        }
        // Hide tooltipster.
        $response->addCommand(new InvokeCommand('.tooltipster-show', 'addClass', ['tooltipster-duing']));
        $response->addCommand(new InvokeCommand('.tooltipster-show', 'removeClass', ['tooltipster-show']));
        /*Alter*/
        $params = [
          'page_type'       => $page_type,
          'page_subtype'    => $page_subtype,
          'page_subtype1'   => $page_subtype1,
        ];
        \Drupal::moduleHandler()->alter('basket_admin_page', $response, $params);
        /*END alter*/
        return $response;

    }
    /*Alter*/
    $params = [
      'page_type'         => $page_type,
      'page_subtype'      => $page_subtype,
      'page_subtype1'     => $page_subtype1,
    ];
    \Drupal::moduleHandler()->alter('basket_admin_page', $element, $params);
    /*END alter*/
    if (!empty($element['#info']['content']['view']['CreateLink'])) {
      self::$createLink = $element['#info']['content']['view']['CreateLink'];
      unset($element['#info']['content']['view']['CreateLink']);
    }
    $managerHeader = new ManagerHeader();
    $element['#info']['header'] = $managerHeader->block(self::$createLink);

    $attached = !empty($element['#attached']) ? $element['#attached'] : [];
    // ---
    $servicePopup = \Drupal::service('BasketPopup')->getInstanceById($this->basket->getSettings('popup_plugin', 'config.admin'));
    if (!empty($servicePopup)) {
      $servicePopup->attached($attached);
    }
    // ---
    $attached['library'][] = 'basket/admin';
    $element['#attached'] = $attached;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  private function addBlockLine() {
    return [
      '#prefix'       => '<div class="add_block_list">',
      '#suffix'       => '</div>',
    [
      $this->addGoodItems() + [
        '#prefix'       => '<div class="item">',
        '#suffix'       => '</div>',
      ],
    ], [
      '#type'         => 'link',
      '#title'        => $this->basket->Translate()->t('Add an order'),
      '#url'          => new Url('basket.admin.pages', [
        'page_type'     => 'orders-add',
      ], [
        'ico_name'      => 'orders.svg',
      ]),
      '#prefix'       => '<div class="item">',
      '#suffix'       => '</div>',
    ], [
      '#type'         => 'link',
      '#title'        => $this->basket->Translate()->t('Add a user'),
      '#url'          => new Url('basket.admin.pages', [
        'page_type'     => 'statistics-buyers-add',
      ], [
        'ico_name'      => 'visitors.svg',
      ]),
      '#prefix'       => '<div class="item">',
      '#suffix'       => '</div>',
      '#access'       => \Drupal::currentUser()->hasPermission('administer users'),
    ], [
      '#type'         => 'link',
      '#title'        => $this->basket->Translate()->t('Trash can'),
      '#url'          => new Url('basket.admin.pages', [
        'page_type'     => 'trash',
      ], [
        'ico_name'      => 'trash.svg',
      ]),
      '#prefix'       => '<div class="item">',
      '#suffix'       => '</div>',
      '#access'       => \Drupal::currentUser()->hasPermission('basket access_trash_page'),
    ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function addGoodItems() {
    $element = [
      '#type'            => 'inline_template',
      '#template'        => '<a href="{{url}}" class="{{class|join(\' \')}}" data-position="bottom"><span class="ico">{{ico|raw}}</span>{{text}}</a><div class="tooltipster_content">{{tooltipster|raw}}</div>',
      '#context'        => [
        'text'            => $this->basket->Translate()->t('Add a product'),
        'ico'            => $this->basket->getIco('product.svg'),
        'url'            => 'javascript:void(0);',
        'class'            => [],
        'tooltipster'    => [],
      ],
      '#access'        => FALSE,
    ];
    $nodeTypes = $this->basket->getNodeTypes();
    if (!empty($nodeTypes)) {
      if (count($nodeTypes) == 1) {
        $nodeType = reset($nodeTypes);
        if (\Drupal::currentUser()->hasPermission('create ' . $nodeType->type . ' content')) {
          $element['#context']['url'] = Url::fromRoute('basket.admin.pages', [
            'page_type'        => 'stock-create-' . $nodeType->type,
          ])->toString();
          $element['#access'] = TRUE;
        }
      }
      else {
        $element['#context']['class'][] = 'tooltipster_init';
        foreach ($nodeTypes as $nodeType) {
          if (empty($nodeType->NodeType)) {
            continue;
          }
          if (\Drupal::currentUser()->hasPermission('create ' . $nodeType->type . ' content')) {
            $element['#context']['tooltipster'][] = [
              '#type'            => 'link',
              '#title'        => $nodeType->NodeType->label(),
              '#url'            => new Url('basket.admin.pages', [
                'page_type'        => 'stock-create-' . $nodeType->type,
              ]),
              '#suffix'        => '<br/>',
            ];
            $element['#access'] = TRUE;
          }
        }
      }
    }
    return $element;
  }

}
