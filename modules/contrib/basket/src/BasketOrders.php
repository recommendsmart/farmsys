<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketOrders {

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
   * Set orderOld.
   *
   * @var object
   */
  protected $orderOld;

  /**
   * Set basketOrderItems.
   *
   * @var array
   */
  protected $basketOrderItems;

  /**
   * Set getIds.
   *
   * @var array
   */
  protected $getIds;

  /**
   * {@inheritdoc}
   */
  public function __construct($id = NULL, $nid = NULL) {
    if (is_null($id) && is_null($nid)) {
      return FALSE;
    }
    if ($id == 'NEW') {
      $this->order = (object) [
        'id'            => 'NEW',
        'goods'         => 0,
      ];
    }
    $this->basket = \Drupal::service('Basket');

    if (!$this->order) {
      $this->order = $this->reLoad($id, $nid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh() {
    if (!empty($this->order->id)) {
      $this->order = $this->reLoad($this->order->id, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reLoad($id = NULL, $nid = NULL) {
    $query = \Drupal::database()->select('basket_orders', 'b');
    $query->fields('b');
    if (!empty($id)) {
      $query->condition('b.id', $id);
    }
    if (!empty($nid)) {
      $query->condition('b.nid', $nid);
    }
    // basket_orders_delivery.
    $query->leftJoin('basket_orders_delivery', 'd', 'd.nid = b.nid');
    $query->addField('d', 'did', 'delivery_id');
    $query->addField('d', 'address', 'delivery_address');
    // basket_orders_payment.
    $query->leftJoin('basket_orders_payment', 'p', 'p.nid = b.nid');
    $query->addField('p', 'pid', 'payment_id');

    // ---
    $this->order = $query->execute()->fetchObject();
    if (!empty($this->order)) {
      $this->basketOrderItems = $this->basket->BasketOrderItems($this->order);
      $this->order->items = $this->basketOrderItems->loadItems();
      $this->orderOld = clone $this->order;
    }
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    $id = !empty($this->order) ? $this->order->id : '- - -';
    if (!isset($this->getIds)) {
      $this->getIds[$id] = $id;
      // Alter.
      \Drupal::moduleHandler()->alter('basket_order_get_id', $this->getIds[$id]);
      // ---
    }
    return $this->getIds[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return empty($this->order->first_view_uid);
  }

  /**
   * {@inheritdoc}
   */
  public function set($field_key, $field_val) {
    if (isset($this->order->{$field_key}) || is_null($this->order->{$field_key})) {
      $this->order->{$field_key} = $field_val;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function replaceOrder($setOrder, $isOld = FALSE) {
    if ($isOld) {
      $this->orderOld = clone $setOrder;
    }
    else {
      $this->order = $setOrder;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if (!empty($this->order)) {
      $updateOrder = clone $this->order;
      $update_fields = (array) $this->order;
      $fields = [];
      foreach ([
        'nid',
        'price',
        'add_price',
        'delivery_price',
        'goods',
        'currency',
        'status',
        'fin_status',
        'first_view_uid',
        'is_delete',
      ] as $updateKey) {
        $fields[$updateKey] = !empty($update_fields[$updateKey]) ? $update_fields[$updateKey] : NULL;
        $updateOrder->{$updateKey} = $fields[$updateKey];
      }
      \Drupal::database()->update('basket_orders')
        ->fields($fields)
        ->condition('id', $this->order->id)
        ->execute();
      if (!empty($this->order->items)) {
        if ($this->basketOrderItems) {
          $updateOrder->items = $this->basketOrderItems->save($this->order->items);
        }
      }
      // Alter.
      \Drupal::moduleHandler()->alter('basketOrderUpdate', $updateOrder, $this->orderOld);
      // ---
      return $updateOrder;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entityOrder = NULL) {
    if (!empty($this->order)) {
      if (!empty($this->order->nid)) {
        $entity = !empty($entityOrder) ? $entityOrder : \Drupal::service('entity_type.manager')->getStorage('node')->load($this->order->nid);
        if (!empty($entity)) {
          /*Delete delivery services info all*/
          foreach (\Drupal::service('BasketDelivery')->getDefinitions() as $delivery) {
            \Drupal::service('BasketDelivery')->getInstanceByID($delivery['id'])->basketDelete($entity, TRUE);
          }
          /*Delete basket delivery info*/
          \Drupal::database()->delete('basket_orders_delivery')->condition('nid', $entity->id())->execute();
          /*Delete basket payment info*/
          \Drupal::database()->delete('basket_orders_payment')->condition('nid', $entity->id())->execute();
          /*Delete entity*/
          if (empty($entityOrder)) {
            $entity->delete();
          }
        }
      }
      /*Items delete*/
      if (!empty($this->order->items)) {
        if ($this->basketOrderItems) {
          $this->basketOrderItems->delete($this->order->items);
        }
      }
      /*Order delete*/
      \Drupal::database()->delete('basket_orders')->condition('id', $this->order->id)->execute();
    }
  }

}
