<?php

namespace Drupal\basket;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;

/**
 * {@inheritdoc}
 */
class BasketOrderItems {

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
   * Set loadItems.
   *
   * @var array
   */
  protected $loadItems;

  /**
   * Set items.
   *
   * @var array
   */
  protected $items;

  /**
   * Set deleteFids.
   *
   * @var array
   */
  protected $deleteFids;

  /**
   * {@inheritdoc}
   */
  public function __construct($order) {
    $this->basket = \Drupal::service('Basket');
    $this->order = $order;
  }

  /**
   * LoadItems.
   */
  public function loadItems() {
    if (!empty($this->order->nid)) {
      $itemsBD = \Drupal::database()->select('basket_orders_item', 'i')
        ->fields('i')
        ->condition('i.order_nid', $this->order->nid)
        ->orderBy('i.id', 'ASC')
        ->execute()->fetchAll();
    }
    if (!empty($itemsBD)) {
      foreach ($itemsBD as &$item) {
        $this->basket->unserializeItem($item);
        $this->items[$item->id] = $item;
      }
    }
    if ($this->loadItems) {
      $this->loadItems = $this->items;
    }
    return $this->items;
  }

  /**
   * Save.
   */
  public function save($saveItems) {
    $updateItems = [];
    if (empty($saveItems)) {
      return $updateItems;
    }
    foreach ($saveItems as $item) {
      if (!empty($item->setUriByFid)) {
        $item->node_fields['img_uri'] = $this->getUriCopyFid($item->setUriByFid);
      }
      // ---
      $hookType = NULL;
      $hookItem = NULL;
      // ---
      $fields = [
        'nid'           => !empty($item->nid) ? $item->nid : 0,
        'order_nid'     => $this->order->nid,
        'price'         => !empty($item->price) ? $item->price : 0,
        'count'         => !empty($item->count) ? $item->count : 0,
        'fid'           => !empty($item->fid) ? $item->fid : 0,
        'params'        => !empty($item->params) ? $this->basket->Cart()->encodeParams($item->params) : NULL,
        'params_html'   => !empty($item->params_html) ? serialize($item->params_html) : NULL,
        'add_time'      => !empty($item->add_time) ? $item->add_time : NULL,
        'node_fields'   => !empty($item->node_fields) ? serialize($item->node_fields) : NULL,
        'discount'      => !empty($item->discount) ? serialize($item->discount) : serialize(['percent' => 0]),
      ];
      if (!empty($item->isDelete)) {
        $this->deleteItem($item);
        // Hook delete
        $hookItem = $item;
        $hookType = 'delete';
        // ---
      }
      elseif (!empty($item->isNew)) {
        $id = \Drupal::database()->insert('basket_orders_item')->fields($fields)->execute();
        $fields['id'] = $id;
        $updateItems[$id] = (object) $fields;
        // Hook insert
        $hookItem = $updateItems[$id];
        $hookType = 'insert';
        // ---
      }
      else {
        \Drupal::database()->update('basket_orders_item')
          ->fields($fields)
          ->condition('id', $item->id)
          ->execute();
        $fields['id'] = $item->id;
        $updateItems[$item->id] = (object) $fields;
        // Hook update
        $hookItem = $updateItems[$item->id];
        $hookType = 'update';
        // ---
      }
      if($hookType) {
        // Hook $hookType
        $hookItem->form_state = $item->form_state ?? NULL;
        $hookItem->form_state_val = $item->form_state_val ?? NULL;
        \Drupal::moduleHandler()->invokeAll('basket_item', [$hookItem, $hookType]);
        // ---
      }
    }
    $this->itemsDeleteFiles();
    if (!empty($updateItems)) {
      foreach ($updateItems as $updateItem) {
        $updateItem->isUpdate = TRUE;
        $this->basket->unserializeItem($updateItem);
      }
    }
    return $updateItems;
  }

  /**
   * Delete.
   */
  public function delete($deleteItems) {
    if (!empty($deleteItems)) {
      foreach ($deleteItems as $item) {
        $this->deleteItem($item);
      }
    }
    $this->itemsDeleteFiles();
  }

  /**
   * DeleteItem.
   */
  public function deleteItem($item) {
    \Drupal::database()->delete('basket_orders_item')
      ->condition('id', $item->id)
      ->execute();
    if (!empty($item->fid) && !empty($item->node_fields['img_uri'])) {
      $this->deleteFids[$item->fid] = $item->node_fields['img_uri'];
    }
  }

  /**
   * GetUriCopyFid.
   */
  public function getUriCopyFid($fid) {
    $fileUri = '';
    if (!empty($fid)) {
      $file = File::load($fid);
      if (!empty($file) && file_exists($file->getFileUri())) {
        $dir = BASKET_FOLDER;
        \Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
        $fileUri = $dir . '/' . $fid . '.' . pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
        if (!file_exists($fileUri)) {
          \Drupal::service('file_system')->copy($file->getFileUri(), $fileUri);
        }
      }
    }
    return $fileUri;
  }

  /**
   * ItemsDeleteFiles.
   */
  public function itemsDeleteFiles() {
    if (!empty($this->deleteFids)) {
      $query = \Drupal::database()->select('basket_orders_item', 'i');
      $query->fields('i', ['fid']);
      $query->addExpression('COUNT(1)', 'count');
      $query->condition('i.fid', array_keys($this->deleteFids), 'in');
      $results = $query->groupBy('i.fid')->execute()->fetchAllKeyed();
      foreach ($this->deleteFids as $fid => $uri) {
        if (empty($results[$fid]) && file_exists($uri)) {
          unlink($uri);
        }
      }
    }
    $this->deleteFids = [];
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter(&$response, $page_type, $basketItem, $defaultParams = NULL) {
    switch ($page_type) {
      case'edit_params':
        $entity = Node::load($basketItem->nid);
        $addParams = [];
        if (!empty($entity)) {
          $entity->basketAddParams = TRUE;
          $addParams = \Drupal::service('BasketParams')->getField($entity, $basketItem, $defaultParams);
        }
        $basketPopup = \Drupal::service('BasketPopup');
        $basketPopup->openModal(
          $response,
          $this->basket->Translate()->t('Extra options'),
          [
            'form'      => $addParams,
            'button'    => [
              '#type'     => 'inline_template',
              '#template' => '<a href="javascript:void(0);" class="button" onclick="{{ onclick }}" data-post="{{ post }}">{{ text }}</a>',
              '#context'  => [
                'text'          => $this->basket->Translate()->t('Save'),
                'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', [
                  'page_type'     => 'api-orders-change_params',
                ])->toString() . '\')',
                'post'          => json_encode([
                  'basketItemId'  => $basketItem->id,
                  'paramsKey'     => !empty($addParams['#attributes']['data-params_key']) ? $addParams['#attributes']['data-params_key'] : '',
                  'orderId'       => $basketItem->orderId ?? NULL,
                ]),
              ],
            ],
          ], [
            'width' => 960,
            'class' => ['basket_popup_view'],
          ]
        );
        break;

      case'change_params':
        $response->addCommand(new InvokeCommand('[name="items[' . $basketItem->id . '][editParams]"]', 'val', [json_encode((!empty($_POST['set_params']) ? $_POST['set_params'] : []))]));
        $response->addCommand(new InvokeCommand('[name="items[' . $basketItem->id . '][editParams]"]', 'change', []));
        $response->addCommand(new InvokeCommand('body', 'append', ['<script>' . \Drupal::service('BasketPopup')->getCloseOnclick() . ';</script>']));
        break;
    }
  }

}
