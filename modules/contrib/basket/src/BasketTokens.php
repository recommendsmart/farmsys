<?php

namespace Drupal\basket;

use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class BasketTokens {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set getToken.
   *
   * @var array
   */
  protected $getToken;

  /**
   * Set orderLoadFull.
   *
   * @var object
   */
  protected $orderLoadFull;

  /**
   * {@inheritdoc}
   */
  public function __construct($isReset = FALSE) {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getToken($tokenName, $params) {
    $tokenName_ = $tokenName;
    if (!empty($params['order']->id)) {
      $tokenName_ .= 'ID:' . $params['order']->id;
    }
    if (!isset($this->getToken[$tokenName_])) {
      $this->getToken[$tokenName_] = '';
      switch ($tokenName) {
        case'logo':
          $this->getToken[$tokenName_] = '<img style="vertical-align:middle;max-height:100px;" src="' . $GLOBALS['base_url'] . theme_get_setting('logo.url', \Drupal::config('system.theme')->get('default')) . '" alt=""/>';
          break;

        case'theme_path':
          $this->getToken[$tokenName_] = $GLOBALS['base_url'] . '/' . drupal_get_path('theme', \Drupal::config('system.theme')->get('default'));
          break;

        case'basket_imgs':
          $this->getToken[$tokenName_] = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'basket') . '/misc/images/mail';
          break;

        case'appearance_color':
          $this->getToken[$tokenName_] = $this->basket->getSettings('appearance', 'config.temlates.color');
          break;

        case'appearance_color_text':
          $this->getToken[$tokenName_] = $this->basket->getSettings('appearance', 'config.temlates.color_text');
          break;

        case'soc_links':
          $this->getToken[$tokenName_] = $this->basket->getSettings('appearance', 'config.temlates.links');
          break;

        case'phone_html':
          $this->getToken[$tokenName_] = [
            '#type'            => 'inline_template',
            '#template'        => $this->basket->getSettings('appearance', 'config.temlates.phone_html'),
          ];
          break;

        case'work_html':
          $this->getToken[$tokenName_] = [
            '#type'            => 'inline_template',
            '#template'        => $this->basket->getSettings('appearance', 'config.temlates.work_html'),
          ];
          break;

        case'order_num':
          $this->getToken[$tokenName_] = !empty($params['order']->id) ? $this->basket->Orders($params['order']->id)->getId() : '';
          break;

        case'order_count':
          if (!empty($params['order']->id)) {
            $order = $this->basket->Orders($params['order']->id)->load();
            $this->getToken[$tokenName_] = !empty($order->goods) ? round($order->goods, 6) : 0;
          }
          break;

        case'order_status_color':
        case'order_fin_status_color':
          $tid = 0;
          switch ($tokenName) {
            case'order_status_color':
              $tid = $params['order']->status ?? 0;
              break;
            case'order_fin_status_color':
              $tid = $params['order']->fin_status ?? 0;
              break;
          }
          if (!empty($tid)) {
            $status = $this->basket->term()->load($tid);
            if (!empty($status)) {
              $this->getToken[$tokenName_] = '<span style="background:' . $status->color . ';padding:5px;border-radius:5px;font-size:14px;">' . $this->basket->translate()->trans(trim($status->name)) . '</span>';
            }
          }
          break;
        case'order_status_select':
        case'order_fin_status_select':
          $tid = 0;
          switch ($tokenName) {
            case'order_status_select':
              $tid = $params['order']->status ?? 0;
              break;
            case'order_fin_status_select':
              $tid = $params['order']->fin_status ?? 0;
              break;
          }
          if (!empty($tid)) {
            $term = $this->basket->term()->load($tid);
            if(!empty($term)) {
              $this->getToken[$tokenName_] = $this->basket->textColor(
                $this->basket->translate()->trans($term->name),
                $term->color,
                [
                  'class'     => ['status_' . $term->type . '_' . $params['order']->id],
                ]
              );
              if (\Drupal::getContainer()->get('BasketAccess')->hasPermission('basket edit_' . $term->type . '_order_access', [
                'orderId'       => $params['order']->id
              ])) {
                $this->getToken[$tokenName_] = [
                  'view'          => $this->getToken[$tokenName_],
                  'select'        => [
                    '#type'         => 'select',
                    '#options'      => $this->basket->term()->getOptions($term->type),
                    '#attributes'   => [
                      'class'         => ['term_change_select'],
                      'onchange'      => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', [
                        'page_type'     => 'api-order_change_status',
                      ])->toString() . '\')',
                      'data-post'     => json_encode([
                        'orderID'       => $params['order']->id,
                        'status_type'   => $term->type,
                        'set_val'       => $term->id,
                      ]),
                    ],
                    '#field_suffix' => ' ',
                    '#value'        => $term->id,
                  ],
                ];
              }
            }
          }
          break;

        case'order_status':
          if (!empty($params['order']->status)) {
            $status = $this->basket->Term()->load($params['order']->status);
            if (!empty($status)) {
              $this->getToken[$tokenName_] = $this->basket->Translate()->trans(trim($status->name));
            }
          }
          break;

        case'order_fin_status':
          if (!empty($params['order']->fin_status)) {
            $fin_status = $this->basket->Term()->load($params['order']->fin_status);
            if (!empty($fin_status)) {
              $this->getToken[$tokenName_] = $this->basket->Translate()->trans(trim($fin_status->name));
            }
          }
          break;

        case'order_list':
          if (!empty($params['order']->id)) {
            $settings_twig = $this->basket->getSettings('templates', 'order_table');
            $this->getToken[$tokenName_] = [
              '#type'            => 'inline_template',
              '#template'        => !empty($settings_twig['config']['template']) ? $settings_twig['config']['template'] : '',
              '#context'        => [
                'order_list'    => $this->orderLoadFull($params['order']->id),
              ],
            ];
          }
          break;

        case'order_fields':
          if (!empty($params['order']->nid)) {
            $settings_twig = $this->basket->getSettings('templates', 'order_fields');
            // --
            $this->getToken[$tokenName_] = [
              '#type'            => 'inline_template',
              '#template'        => !empty($settings_twig['config']['template']) ? $settings_twig['config']['template'] : '',
              '#context'        => [
                'node'            => $this->getToken('node', $params),
                'build'            => $this->getToken('build', $params),
                'order'            => $this->getToken('order', $params),
              ],
            ];
          }
          break;

        case'order_edit_link':
          if (!empty($params['order']->id)) {
            $this->getToken[$tokenName_] = Url::fromRoute('basket.admin.pages', [
              'page_type'        => 'orders-edit-' . $params['order']->id,
            ], [
              'absolute'        => TRUE,
            ])->toString();
          }
          break;

        case'node':
          if (!empty($params['order']->nid)) {
            $this->getToken[$tokenName_] = \Drupal::service('entity_type.manager')->getStorage('node')->load($params['order']->nid);
          }
          break;

        case'build':
          if (!empty($params['order']->nid)) {
            $orderNode = $this->getToken('node', $params);
            $build = [];
            if (!empty($orderNode)) {
              $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $orderNode->bundle() . '.basket');
              if (empty($display)) {
                $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $orderNode->bundle() . '.default');
              }
              if (!empty($display)) {
                if (!empty($fields = $display->getComponents())) {
                  foreach ($fields as $fieldName => $field) {
                    if (empty($orderNode->{$fieldName})) {
                      continue;
                    }
                    $build[$fieldName] = \Drupal::token()->replace('[node:' . $fieldName . ']', ['node' => $orderNode], ['clear' => TRUE]);
                  }
                }
              }
            }
            $this->getToken[$tokenName_] = $build;
          }
          break;

        case'order':
          if (!empty($params['order']->id)) {
            $this->getToken[$tokenName_] = $this->orderLoadFull($params['order']->id);
          }
          break;
      }
      if (strpos($tokenName, 'item_') !== FALSE && !empty($params['orderItem'])) {
        $keyField = str_replace(['item_', '.' . $params['orderItem']->id], '', $tokenName);
        switch ($keyField) {
          case'title':
            $this->getToken[$tokenName_] = !empty($params['orderItem']->node_fields['title']) ? $params['orderItem']->node_fields['title'] : '';
            break;

          case'price':
          case'count':
            $this->getToken[$tokenName_] = !empty($params['orderItem']->{$keyField}) ? $params['orderItem']->{$keyField} : 0;
            break;

          case'percent':
            $this->getToken[$tokenName_] = !empty($params['orderItem']->discount['percent']) ? $params['orderItem']->discount['percent'] : 0;
            break;

          case'sum':
            $price = !empty($params['orderItem']->price) ? $params['orderItem']->price : 0;
            $count = !empty($params['orderItem']->count) ? $params['orderItem']->count : 0;
            $this->getToken[$tokenName_] = $price * $count;
            if ($params['orderItem']->discount['percent']) {
              $this->getToken[$tokenName_] = $this->getToken[$tokenName_] - ($this->getToken[$tokenName_] / 100 * $params['orderItem']->discount['percent']);
            }
            break;

          case'currency':
            $this->getToken[$tokenName_] = !empty($params['order']->currency->name) ? $params['order']->currency->name : '';
            break;
        }
      }
      // Alter.
      \Drupal::moduleHandler()->alter('basketTokenValue', $this->getToken[$tokenName_], $tokenName, $params);
      // ---
    }
    return $this->getToken[$tokenName_];
  }

  /**
   * {@inheritdoc}
   */
  private function orderLoadFull($id) {
    if (!isset($this->orderLoadFull[$id])) {
      $this->orderLoadFull[$id] = $this->basket->Orders($id)->reLoad($id);
      /*
      Currency
       */
      if (!empty($this->orderLoadFull[$id]->currency)) {
        $this->orderLoadFull[$id]->currency = $this->basket->Currency()->load($this->orderLoadFull[$id]->currency);
      }
      if (!empty($this->orderLoadFull[$id]->pay_currency)) {
        $this->orderLoadFull[$id]->pay_currency = $this->basket->Currency()->load($this->orderLoadFull[$id]->pay_currency);
      }
      /*
      Delivery
       */
      if (!empty($this->orderLoadFull[$id]->delivery_id)) {
        $this->orderLoadFull[$id]->delivery_id = $this->basket->Term()->load($this->orderLoadFull[$id]->delivery_id);
      }
      if (!empty($this->orderLoadFull[$id]->delivery_address)) {
        $this->orderLoadFull[$id]->delivery_address = unserialize($this->orderLoadFull[$id]->delivery_address);
      }
      /*
      Payment
       */
      if (!empty($this->orderLoadFull[$id]->payment_id)) {
        $this->orderLoadFull[$id]->payment_id = $this->basket->Term()->load($this->orderLoadFull[$id]->payment_id);
      }
    }
    return $this->orderLoadFull[$id];
  }

}
