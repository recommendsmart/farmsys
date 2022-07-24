<?php

namespace Drupal\basket;

use Drupal\views\Views;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\basket\Admin\Page\Order;
use Drupal\basket\Admin\OrdersViewsAlters;
use Drupal\basket\Admin\Page\Trash;
use Drupal\basket\Query\BasketGetNodeImgQuery;

/**
 * {@inheritdoc}
 */
class ViewsAlters {

  /**
   * {@inheritdoc}
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * ViewsViewTableAlter.
   */
  public function viewsViewTableAlter(&$vars) {
    if (!empty($vars['header'])) {
      $this->tableHeaderAlter($vars['header']);
    }
    /*
     * Alter Basket Block_1
     */
    if ($vars['view']->id() == 'basket' && $vars['view']->current_display == 'block_1') {
      if (!empty($vars['view']->total_rows) && !empty($vars['rows'])) {
        $vars['header'][] = [];
        foreach ($vars['rows'] as $key => $row) {
          $this->tableRowsAlter($vars['rows'][$key], $vars['view']->result[$key]);
        }
      }
      $this->baskerPage1Caption($vars);
    }
    /*
     * Alter Basket Block_2
     */
    if ($vars['view']->current_display == 'block_2') {
      $request = \Drupal::request()->request->all();
      if (!empty($request['orderId'])) {
        if (!empty($vars['view']->total_rows) && !empty($vars['rows'])) {
          $order = new Order($request['orderId']);
          $order->viewsTableAlterBlock2($vars);
        }
      }
    }
    /*
     * Alter Basket Block_3
     */
    if ($vars['view']->id() == 'basket' && $vars['view']->current_display == 'block_3') {
      if (!empty($vars['view']->total_rows) && !empty($vars['rows'])) {
        $vars['header'][] = [];
        foreach ($vars['rows'] as $key => $row) {
          if (empty($vars['rows'][$key]) || empty($vars['view']->result[$key])) {
            continue;
          }
          $this->tableRows3Alter($vars['rows'][$key], $vars['view']->result[$key]);
        }
      }
    }
    /*
     * Alter Basket Users Block_1
     */
    if ($vars['view']->id() == 'basket_users' && $vars['view']->current_display == 'block_1') {
      if (!empty($vars['view']->total_rows) && !empty($vars['rows'])) {
        $vars['header'][] = [];
        foreach ($vars['rows'] as $key => $row) {
          $this->tableBasketUsersRowAlter($vars['rows'][$key], $vars['view']->result[$key]);
        }
      }
    }
    /*
     * Empty text
     */
    if (empty($vars['view']->total_rows) && !empty($vars['rows'])) {
      $vars['rows'][0]['columns'][0]['content'][1]['field_output'] = [
        '#markup'        => $this->basket->Translate()->t('The list is empty.'),
      ];
    }
  }

  /**
   * ViewsViewAlter.
   */
  public function viewsViewAlter(&$vars) {
    $vars['attributes']['data-basketid'] = 'view_wrap-' . $vars['view']->id() . '-' . $vars['view']->current_display;
    if ($vars['view']->current_display == 'block_1') {
      OrdersViewsAlters::viewAlter($vars);
    }
  }

  /**
   * TableHeaderAlter.
   */
  public function tableHeaderAlter(&$headers, $contextModule = 'basket') {
    foreach ($headers as $key => $header) {
      if (!empty($header['url'])) {
        $class = ['sort_arrow'];
        if (!empty($header['sort_indicator']['#style'])) {
          $class[] = $header['sort_indicator']['#style'];
        }
        $headers[$key]['sort_indicator'] = [
          '#markup'        => Markup::create('<span class="' . implode(' ', $class) . '">' . $this->basket->getIco('sort.svg', 'base') . '</span>'),
        ];
      }
      if (!empty($header['content']) && is_string($header['content'])) {
        $headers[$key]['content'] = $this->basket->Translate($contextModule)->trans(trim($header['content']));
      }
    }
  }

  /**
   * TableRowsAlter.
   */
  private function tableRowsAlter(&$row, $row_result) {
    if (!empty($row['columns'])) {
      foreach ($row['columns'] as $key => $field) {
        switch ($key) {
          case'status':
          case'fin_status':
            break;

          default:
            if (!empty($row_result->basket_orders_id)) {
              $row['columns'][$key]['content'][1]['field_output'] = [
                '#type'            => 'link',
                '#title'        => '',
                '#url'            => new Url('basket.admin.pages', [
                  'page_type'        => 'orders-view-' . $row_result->basket_orders_id,
                ], [
                  'attributes'    => [
                    'class'            => ['edit_order_link'],
                    'title'            => $this->basket->Translate()->t('View order'),
                  ],
                ]),
              ];
            }
            break;
        }
      }
      $order = new Order($row_result->basket_orders_id, 'preview');
      $row['columns'][] = [
        'content'        => [
       [
         'field_output'        => [
           '#type'                => 'inline_template',
           '#template'            => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
							<div class="tooltipster_content">
								{% for link in links %}
									{% if link[\'#context\'] %}
										<a href="{% if link[\'#context\'].url %}{{ link[\'#context\'].url }}{% else %}javascript:void(0);{% endif %}" class="button--link" target="{% if link[\'#context\'].target %}{{ link[\'#context\'].target }}{% else %}_self{% endif %}" onclick="{{ link[\'#context\'].onclick }}" data-post="{{ link[\'#context\'].post }}"><span class="ico">{{ link[\'#context\'].ico|raw }}</span> {{ link[\'#context\'].text }}</a><br/>
									{% endif %}
								{% endfor %}
							</div>',
           '#context'            => [
             'ico'                => $this->basket->getIco('settings_row.svg', 'base'),
             'links'                => $order->allLinks($row_result->_entity),
           ],
         ],
       ],
        ],
        'attributes'    => new Attribute([
          'class'            => 'td_settings_row',
        ]),
        'default_classes' => FALSE,
      ];
    }
  }

  /**
   * TableRows3Alter.
   */
  private function tableRows3Alter(&$row, $row_result) {
    $links = [
      'view'        => [
        'text'          => $this->basket->Translate()->t('View'),
        'ico'           => $this->basket->getIco('eye.svg'),
        'attributes'    => new Attribute([
          'href'          => Url::fromRoute('entity.node.canonical', ['node' => $row_result->nid])->toString(),
          'target'        => '_blank'
        ])
      ],
    ];
    $operations = \Drupal::service('entity_type.manager')->getListBuilder($row_result->_entity->getEntityTypeId())->getOperations($row_result->_entity);
    if (!empty($operations['edit'])) {
      $links['edit'] = [
        'text'           => $this->basket->Translate()->t('Edit'),
        'ico'            => $this->basket->getIco('edit.svg'),
        'attributes'    => new Attribute([
          'href'          => Url::fromRoute('basket.admin.pages', [
            'page_type'        => 'stock-edit-' . $row_result->nid,
          ], [
            'query'        => [
              'destination'    => Url::fromRoute('basket.admin.pages', ['page_type' => 'stock-product'])->toString(),
            ],
          ])->toString()
        ])
      ];
    }
    if (!empty($operations['quick_clone'])) {
      $links['quick_clone'] = [
        'text'          => $operations['quick_clone']['title'],
        'ico'           => $this->basket->getIco('clone.svg'),
        'attributes'    => new Attribute([
          'href'          => $operations['quick_clone']['url']->toString(),
          'target'        => '_blank',
        ])
      ];
    }
    // Alter
    \Drupal::moduleHandler()->alter('stockProductLinks', $links, $row_result->_entity);
    // ---
    $row['columns'][] = [
      'content'        => [
        [
          'field_output'        => [
            '#type'                => 'inline_template',
            '#template'            => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
							<div class="tooltipster_content">
								{% for link in links %}
									{% if link %}
                    <a{{ link.attributes.addClass(\'button--link\') }}><span class="ico">{{ link.ico|raw }}</span> {{ link.text }}</a><br/>
									{% endif %}
								{% endfor %}
							</div>',
            '#context'            => [
              'ico'                => $this->basket->getIco('settings_row.svg', 'base'),
              'links'              => $links,
            ],
          ],
        ],
      ],
      'attributes'    => new Attribute([
        'class'            => 'td_settings_row',
      ]),
      'default_classes' => FALSE,
    ];
    // Popup img.
    if (!empty($row['columns']['title']['content'][0]['field_output']['#markup']) && !empty($row_result->_entity)) {
      $row['columns']['title']['content'][0]['field_output'] = [
        'title'            => [
          '#markup'        => $row['columns']['title']['content'][0]['field_output']['#markup'],
        ],
      ];
      $row['columns']['title']['attributes']->addClass('title_img_wrap');
      $getFid = BasketGetNodeImgQuery::getNodeImgFirst($row_result->_entity);
      if (!empty($getFid)) {
        $file = \Drupal::service('entity_type.manager')->getStorage('file')->load($getFid);
        if (!empty($file)) {
          $row['columns']['title']['content'][0]['field_output'][] = [
            '#prefix'        => '<div class="img_hover_wrap">',
            '#suffix'        => '</div>',
              [
                '#prefix'       => '<div class="img">',
                '#suffix'       => '</div>',
                '#theme'        => 'image_style',
                '#style_name'   => 'thumbnail',
                '#uri'          => $file->getFileUri(),
              ],
          ];
        }
      }
    }
  }

  /**
   * BaskerPage1Caption.
   */
  private function baskerPage1Caption(&$vars) {
    if (!empty($vars['view']->args[0]) && $vars['view']->args[0] == 'is_delete') {
      if (!empty($vars['view']->result)) {
        $trash = new Trash();
        $vars['caption'] = [
          '#theme'        => 'basket_admin_basket_block_caption',
          '#info'            => [
            'items'            => $trash->getCaptionItems(),
          ],
        ];
        $vars['caption_needed'] = TRUE;
      }
      return FALSE;
    }
    $vars['caption_needed'] = TRUE;
    $edit_tab = [];
    if (\Drupal::currentUser()->hasPermission('basket edit_orders_settings_tabs_access')) {
      $edit_tab = [
        '#type'            => 'inline_template',
        '#template'        => '<a href="javascript:void(0);" class="field_settings_link" onclick="{{onclick}}" title="{{title}}">{{ico|raw}}</a>',
        '#context'        => [
          'ico'            => $this->basket->getIco('settings_row.svg', 'base'),
          'title'            => $this->basket->Translate()->t('Tab setting'),
          'onclick'        => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', [
            'page_type'        => 'api-orders_tabs_settings',
          ])->toString() . '\')',
        ],
      ];
    }
    // ---
    $request = \Drupal::request()->query->all();
    if (!empty($request['combo_status'])) {
      $combo_status = json_decode($request['combo_status'], TRUE);
    }
    // ---
    $items = [];
    $combo = '';
    $config = $this->basket->getSettings('orders_tabs_settings', 'config');
    if (!empty($config)) {
      foreach ($config as $key => $item) {
        if (!is_array($item)) {
          continue;
        }
        $items[$key] = [
          'name'        => $this->basket->Translate()->trans(trim($item['name'])),
          'onclick'    => 'basket_set_combo_status(this)',
          'post'        => json_encode([
            'name'        => 'tab_' . $key,
            'status'    => $item['status'],
          ]),
          '#weight'    => isset($item['weight']) ? $item['weight'] : 1000,
        ];
        if (!empty($combo_status['name']) && $combo_status['name'] == 'tab_' . $key) {
          $items[$key]['class'][] = 'is-active';
          $combo = $items[$key]['name'];
        }
      }
      uasort($items, 'Drupal\Component\Utility\SortArray::sortByWeightProperty');
    }
    if (!empty($combo_status['name']) && empty($combo)) {
      $combo = $this->basket->getSettings('orders_stat_block_settings', 'config.' . $combo_status['name'] . '.title');
      if (!empty($combo)) {
        $combo = $this->basket->translate()->trans(trim($combo));
      }
    }
    $export = [];
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') && \Drupal::currentUser()->hasPermission('basket access_export_order')) {
      $export = [
        '#type'            => 'inline_template',
        '#template'        => '<a href="{{url}}" class="export_link" target="_blank" title="{{ title }}"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
        '#context'         => [
          'ico'             => $this->basket->getIco('export.svg'),
          'text'            => $this->basket->Translate()->t('Export'),
          'url'             => Url::fromRoute('basket.admin.pages', [
            'page_type'       => 'orders-export',
          ], [
            'query'           => \Drupal::request()->query->all() + ['not_delete' => TRUE],
          ])->toString(),
          'title'           => $this->basket->translate()->t('Export the current page')
        ],
      ];
    }
    $vars['caption'] = [
      '#theme'        => 'basket_admin_basket_block_caption',
      '#info'         => [
        'items'           => $items,
        'edit_tab'      => $edit_tab,
        'export'        => $export,
        'combo'         => $combo,
      ],
    ];
    $vars['summary_element'] = [];
  }

  /**
   * ViewsPreBuidAlter.
   */
  public function viewsPreBuidAlter($view) {
    if ($view->id() == 'basket' && $view->current_display == 'block_1' && empty($_POST)) {
      $config = $this->basket->getSettings('orders_tabs_settings', 'config');
      $request = \Drupal::request()->query->all();
      if (!isset($request['combo_status']) && isset($config['default']) && !empty($config[$config['default']])) {
        \Drupal::request()->query->set('combo_status', json_encode([
          'name'        => 'tab_' . $config['default'],
          'status'        => $config[$config['default']]['status'],
        ]));
      }
    }
  }

  /**
   * ViewsQueryAlter.
   */
  public function viewsQueryAlter(&$view, &$query) {
    if ($view->id() == 'content' && $view->current_display == 'page_1') {
      $nodeTypes = ['basket_order'];
      if (!empty($settingsNodeTypes = $this->basket->getNodeTypes())) {
        foreach ($settingsNodeTypes as $info) {
          $nodeTypes[] = $info->type;
        }
      }
      $query->addWhere(1, 'node_field_data.type', $nodeTypes, 'NOT IN');
    }
    if ($view->id() == 'basket' && $view->current_display == 'block_1') {
      if (!empty($view->args[0])) {
        switch ($view->args[0]) {
          case'not_delete':
            $query->addWhere(NULL, 'basket_orders.is_delete', NULL, 'IS NULL');
            break;

          case'is_delete':
            $query->addWhere(NULL, 'basket_orders.is_delete', NULL, 'IS NOT NULL');
            break;
        }
      }
    }
    if ($view->id() == 'basket' && $view->current_display == 'block_3') {
      $settingsNodeTypes = $this->basket->getNodeTypes();
      $nodeTypes = !empty($settingsNodeTypes) ? array_keys($settingsNodeTypes) : ['---'];
      $query->addWhere(NULL, 'node_field_data.type', $nodeTypes, 'in');
      // basket_node_delete.
      $join = Views::pluginManager('join')->createInstance('standard', [
        'type'       => 'LEFT',
        'table'      => 'basket_node_delete',
        'field'      => 'nid',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'operator'   => '=',
      ]);
      $query->addRelationship('basket_node_delete', $join, 'node_field_data');
      if (!empty($view->args[0]) && $view->args[0] == 'is_delete') {
        $query->addWhere(NULL, 'basket_node_delete.nid', NULL, 'IS NOT NULL');
      }
      else {
        $query->addWhere(NULL, 'basket_node_delete.nid', NULL, 'IS NULL');
      }
    }
  }

  /**
   * TableBasketUsersRowAlter.
   */
  private function tableBasketUsersRowAlter(&$row, $row_result) {
    $links = [
      'edit'        => [
        'text'           => $this->basket->Translate()->t('Edit'),
        'ico'            => $this->basket->getIco('edit.svg'),
        'url'            => Url::fromRoute('basket.admin.pages', [
          'page_type'        => 'statistics-buyers-edit',
        ], [
          'query'         => [
            'uid'             => $row_result->uid,
          ],
        ])->toString(),
      ],
    ];
    $row['columns'][] = [
      'content'        => [
        [
          'field_output'        => [
            '#type'                => 'inline_template',
            '#template'            => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
							<div class="tooltipster_content">
								{% for link in links %}
									{% if link %}
										<a href="{% if link.url %}{{ link.url }}{% else %}javascript:void(0);{% endif %}" class="button--link" target="{% if link.target %}{{ link.target }}{% else %}_self{% endif %}" onclick="{{ link.onclick }}" data-post="{{ link.post }}"><span class="ico">{{ link.ico|raw }}</span> {{ link.text }}</a><br/>
									{% endif %}
								{% endfor %}
							</div>',
            '#context'            => [
              'ico'                => $this->basket->getIco('settings_row.svg', 'base'),
              'links'                => $links,
            ],
          ],
        ],
      ],
      'attributes'    => new Attribute([
        'class'            => 'td_settings_row',
      ]),
      'default_classes' => FALSE,
    ];
  }

}
