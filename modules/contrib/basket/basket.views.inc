<?php

/**
 * @file
 * Provide views data for basket.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data().
 */
function basket_views_data() {

  $data = [];

  $data['basket']['table']['group'] = t('Basket');
  $data['basket']['table']['wizard_id'] = 'basket';

  $data['basket']['table']['base'] = [
    'field'     => 'id',
    'title'     => t('ID'),
    'help'      => '',
  ];
  foreach ([
    'id'            => [
      'title'         => t('ID', [], ['context' => 'basket']),
      'field_id'      => 'standard',
    ],
    'add_time'      => [
      'title'         => t('Add time', [], ['context' => 'basket']),
      'field_id'      => 'date',
      'sort_id'       => 'basket_add_time',
    ],
    'nid'           => [
      'title'         => t('Nid', [], ['context' => 'basket']),
      'field_id'      => 'standard',
      'sort_id'       => 'standard',
      'relationship'  => [
        'label'         => 'Ref node',
        'id'            => 'standard',
        'base'          => 'node_field_data',
        'base field'    => 'nid',
        'relationship field' => 'nid',
      ],
    ],
    'img'           => [
      'title'         => t('Photo', [], ['context' => 'basket']),
      'field_id'      => 'basket_cart_img',
    ],
    'sid'           => [
      'title'         => t('Current user', [], ['context' => 'basket']),
      'filter_id'     => 'basket_cart_sid',
    ],
    'price'         => [
      'title'         => t('Price', [], ['context' => 'basket']),
      'field_id'      => 'basket_cart_price',
    ],
    'sum'           => [
      'title'         => t('Sum', [], ['context' => 'basket']),
      'field_id'      => 'basket_cart_sum',
    ],
    'count'         => [
      'title'         => t('Quantity', [], ['context' => 'basket']),
      'field_id'      => 'basket_cart_quantity',
    ],
    'all_params'    => [
      'title'         => t('Extra options', [], ['context' => 'basket']),
      'field_id'      => 'basket_cart_all_params',
    ],
    'delete'        => [
      'title'         => t('Delete', [], ['context' => 'basket']),
      'field_id'      => 'basket_cart_delete',
    ],
    'cart_result'   => [
      'title'         => t('Basket data', [], ['context' => 'basket']),
      'area_id'       => 'basket_cart_result',
    ],
    'cart_clear_all' => [
      'title'         => t('Clear the entire basket', [], ['context' => 'basket']),
      'area_id'       => 'basket_cart_clear_all',
    ],
  ] as $fieldName => $field) {
    $data['basket'][$fieldName] = [
      'title'             => $field['title'],
      'help'              => '',
    ];
    if (!empty($field['field_id'])) {
      $data['basket'][$fieldName]['field']['id'] = $field['field_id'];
    }
    if (!empty($field['sort_id'])) {
      $data['basket'][$fieldName]['sort']['id'] = $field['sort_id'];
    }
    if (!empty($field['filter_id'])) {
      $data['basket'][$fieldName]['filter']['id'] = $field['filter_id'];
    }
    if (!empty($field['area_id'])) {
      $data['basket'][$fieldName]['area']['id'] = $field['area_id'];
    }
    if (!empty($field['relationship'])) {
      $data['basket'][$fieldName]['relationship'] = $field['relationship'];
    }
  }

  $data['basket_orders']['table']['group'] = t('Basket order');
  $data['basket_orders']['table']['base'] = [
    'field'         => 'nid',
    'title'         => 'Basket orders',
    'help'          => '',
    'weight'        => -10,
  ];
  $data['basket_orders']['table']['join'] = [
    'node_field_data'    => [
      'left_field'         => 'nid',
      'field'              => 'nid',
    ],
  ];
  foreach ([
    'id'            => [
      'title'         => 'ID',
      'field_id'      => 'basket_order_id',
      'sort_id'       => 'standard',
      'filter_id'     => 'string',
      'basket_views'  => ['basket'],
    ],
    'goods'         => [
      'title'         => 'Goods',
      'field_id'      => 'basket_order_goods',
      'sort_id'       => 'standard',
      'filter_id'     => 'string',
      'basket_views'  => ['basket'],
    ],
    'price'         => [
      'title'         => 'Price',
      'field_id'      => 'basket_order_price',
      'sort_id'       => 'standard',
      'filter_id'     => 'string',
      'basket_views'  => ['basket'],
    ],
    'currency'      => [
      'title'         => 'Currency',
      'field_id'      => 'basket_order_currency',
      'sort_id'       => 'standard',
      'filter_id'     => 'string',
      'basket_views'  => ['basket'],
    ],
    'status'        => [
      'title'         => 'Status',
      'field_id'      => 'basket_order_term',
      'sort_id'       => 'standard',
      'filter_id'     => 'string',
      'basket_views'  => ['basket'],
    ],
    'fin_status'    => [
      'title'         => 'Financial status',
      'field_id'      => 'basket_order_term',
      'sort_id'       => 'standard',
      'filter_id'     => 'string',
      'basket_views'  => ['basket'],
    ],
    'orders_filter'  => [
      'title'         => 'Orders filter',
      'filter_id'     => 'basket_order_filter_fields',
      'basket_views'  => ['basket'],
    ],
    'goods_block_2_filter'  => [
      'title'         => 'Basket goods filter block 2',
      'help'          => 'Filter is only for basket:block_2',
      'filter_id'     => 'basket_goods_filter_block2',
      'basket_views'  => ['basket'],
    ],
    'goods_table'   => [
      'title'         => 'Goods table',
      'field_id'      => 'basket_goods_table_field',
      'basket_views'  => ['basket'],
    ],
  ] as $field_name => $row) {
    $data['basket_orders'][$field_name] = [
      'title'             => $row['title'],
      'help'              => !empty($row['help']) ? $row['help'] : '',
      'basket_views'      => !empty($row['basket_views']) ? $row['basket_views'] : [],
    ];
    if (!empty($row['field_id'])) {
      $data['basket_orders'][$field_name]['field'] = [
        'id'                => $row['field_id'],
      ];
    }
    if (!empty($row['sort_id'])) {
      $data['basket_orders'][$field_name]['sort'] = [
        'id'                => $row['sort_id'],
      ];
    }
    if (!empty($row['filter_id'])) {
      $data['basket_orders'][$field_name]['filter'] = [
        'id'                => $row['filter_id'],
      ];
    }
  }
  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function basket_views_data_alter(array &$data) {
  
  // Node
  $data['node']['basket_is_add_cart'] = [
    'title'         => 'Basket (Node is add to cart)',
    'filter'        => [
      'id'            => 'basket_is_add_cart',
    ],
  ];
  $data['node']['basket_add_field'] = [
    'title'         => 'Basket (Add button)',
    'field'         => [
      'title'         => 'Basket (Add button)',
      'help'          => '',
      'id'            => 'basket_add_field',
      'click sortable' => FALSE,
    ],
  ];
  $data['node']['basket_get_price_field'] = [
    'title'         => 'Basket (Node get price)',
    'field'         => [
      'title'         => 'Basket (Node get price)',
      'help'          => '',
      'id'            => 'basket_get_price_field',
      'click sortable' => TRUE,
    ],
    'sort'          => [
      'id'            => 'basket_get_price_field',
    ],
    'filter'        => [
      'id'            => 'basket_get_price_field',
    ],
  ];
  $data['node']['basket_filter_title_field'] = [
    'title'         => 'Basket (Search title field)',
    'filter'        => [
      'id'            => 'basket_filter_title_field',
    ],
  ];
  $data['node']['basket_product_status'] = [
    'title'         => 'Basket (Node get status)',
    'field'         => [
      'title'         => 'Basket (Node get status)',
      'help'          => '',
      'id'            => 'basket_product_status_field',
      'click sortable' => TRUE,
    ],
    'filter'        => [
      'id'            => 'basket_product_status_field',
    ],
    'basket_views'  => ['basket'],
  ];
  $data['node']['basket_product_checked'] = [
    'title'         => 'Basket (Node checked)',
    'field'         => [
      'title'         => 'Basket (Node checked)',
      'help'          => '',
      'id'            => 'basket_product_checked_field',
      'click sortable' => FALSE,
    ],
    'basket_views'  => ['basket'],
  ];
  $data['node']['basket_product_counts'] = [
    'title'         => 'Basket (Node get counts)',
    'field'         => [
      'title'         => 'Basket (Node get counts)',
      'help'          => '',
      'id'            => 'basket_product_counts_field',
      'click sortable' => TRUE,
    ],
    'sort'          => [
      'id'            => 'basket_product_counts_field',
    ],
    'filter'        => [
      'id'            => 'basket_product_counts_field',
    ],
  ];
  $data['node']['basket_product_in_stock'] = [
    'title'         => 'Basket (Node In stock)',
    'sort'          => [
      'id'            => 'basket_product_in_stock',
    ]
  ];
  $data['node']['basket_product_img'] = [
    'title'         => 'Basket (Node get first img)',
    'field'         => [
      'title'         => 'Basket (Node get first img)',
      'help'          => '',
      'id'            => 'basket_product_img_field',
      'click sortable' => FALSE,
    ],
  ];
  $data['node']['basket_operations_buttons'] = [
    'title'         => 'Basket (Node operations buttons)',
    'area'          => [
      'id'            => 'basket_operations_buttons',
    ],
  ];
  
  // Users
  $data['users']['basket_user_percent'] = [
    'title'         => 'Basket (User get percent)',
    'field'         => [
      'title'         => 'Basket (User get percent)',
      'help'          => '',
      'id'            => 'basket_user_percent_field',
      'click sortable' => TRUE,
    ],
  ];
  $data['users']['basket_user_sum'] = [
    'title'         => 'Basket (User get total sum orders)',
    'field'         => [
      'title'         => 'Basket (User get total sum orders)',
      'help'          => '',
      'id'            => 'basket_user_sum_field',
      'click sortable' => TRUE,
    ],
    'filter'        => [
      'id'            => 'basket_user_sum_field',
    ],
  ];
  $data['users']['basket_buyers_buttons'] = [
    'title'         => 'Basket (Buyers buttons)',
    'area'          => [
      'id'            => 'basket_buyers_buttons',
    ],
  ];
}

/**
 * Implements hook_field_views_data_alter().
 */
function basket_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'basket_price_field') {
    foreach ($data as &$dataField) {
      foreach ($dataField as &$fieldInfo) {
        if (!empty($fieldInfo['field']['id']) && $fieldInfo['field']['id'] == 'field') {
          $fieldInfo['field']['id'] = 'basket_price_field';
        }
      }
    }
  }
}
