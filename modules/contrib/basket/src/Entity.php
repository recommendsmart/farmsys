<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class Entity {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set db.
   *
   * @var object
   */
  protected $db;

  /**
   * Set cart.
   *
   * @var object
   */
  protected $cart;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->db = \Drupal::database();
    $this->cart = $this->basket->Cart();
  }

  /**
   * Entiti removal process.
   */
  public function delete($entity) {
    switch ($entity->getEntityTypeId()) {
      case'node_type':
        $this->db->delete('basket_node_types')->condition('type', $entity->id())->execute();
        break;

      case'user':
        $this->db->delete('basket_user_percent')->condition('uid', $entity->id())->execute();
        break;

      case'node':
        $isBundle = $this->db->select('basket_node_types', 'n')
          ->fields('n')
          ->condition('n.type', $entity->bundle())
          ->execute()->fetchField();
        if (!empty($isBundle)) {
          $this->db->delete('basket')->condition('nid', $entity->id())->execute();
        }
        // Delete order.
        $orderClass = $this->basket->Orders(NULL, $entity->id());
        $orderClass->delete($entity);
        // Delete all.
        $this->db->delete('basket_node_delete')
          ->condition('nid', $entity->id())
          ->execute();
        break;
    }
  }

  /**
   * Checkout process.
   */
  public function insertOrder($entity, $form_state) {
    $_SESSION['delivery_tid'] = $form_state->getValue([
      'basket_delivery',
      'value',
    ]);
    
    // Not Discount
    $_SESSION['payment_tid'] = $form_state->getValue('basket_payment');
    $GLOBALS['cartNotDiscount'] = $this->basket->getSettings('payment_not_discounts', $_SESSION['payment_tid']);
    $this->cart->reset();
    
    // ---
    $cartItems = $this->cart->getItemsInBasket();
    
    // ---
    $countGoods = 0;
    $bd_nids = [0];
    foreach ($cartItems as $row) {
      $bd_nids[$row->nid] = $row->nid;
      $countGoods += $row->count;
    }
    $nodesAdd = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($bd_nids);

    $getPayInfo = $this->cart->getPayInfo();

    $basketOrderFields = [
      'nid'           => $entity->id(),
      'price'         => $this->cart->getTotalSum(),
      'goods'         => $countGoods,
      'currency'      => $this->basket->Currency()->getCurrent(),
      'status'        => $this->basket->Term()->getDefaultNewOrder('status'),
      'fin_status'    => $this->basket->Term()->getDefaultNewOrder('fin_status'),
      'pay_price'     => $getPayInfo['price'],
      'pay_currency'  => $getPayInfo['currency']->id,
      'add_price'     => 0,
      'delivery_price'  => 0,
	    'notClearAll'   => !empty($GLOBALS['cartNotClearAll'])
    ];
    // Delivery sum.
    $deliveryInfo = \Drupal::service('BasketDelivery')->getDeliveryInfo($entity);
    if (!empty($deliveryInfo['sum']) && !empty($deliveryInfo['isPay'])) {
      $basketOrderFields['delivery_price'] = $deliveryInfo['sum'];
    }
    // ---
    $basketItems = [];
    foreach ($cartItems as $row) {
      $basketItems[$row->id] = [
        'isNew'           => TRUE,
        'nid'             => $row->nid,
        'order_nid'       => $entity->id(),
        'price'           => $this->cart->getItemPrice($row),
        'discount'        => [
          'percent'         => $this->cart->getItemDiscount($row),
        ],
        'count'           => $row->count,
        'fid'             => $this->cart->getItemImg($row),
        'params'          => $row->all_params,
        'params_html'     => !empty($row->all_params) ? [
          'full'            => \Drupal::service('BasketParams')->getDefinitionParams($row->all_params, $row->nid),
          'inline'          => \Drupal::service('BasketParams')->getDefinitionParams($row->all_params, $row->nid, TRUE),
        ] : NULL,
        'add_time'        => $row->add_time,
        'node_fields'     => [
          'title'           => $nodesAdd[$row->nid]->getTitle(),
          'img_uri'         => '',
        ],
        'setUriByFid'     => NULL,
        'form_state'      => $form_state
      ];
      if (!empty($basketItems[$row->id]['fid'])) {
        $basketItems[$row->id]['setUriByFid'] = $basketItems[$row->id]['fid'];
      }
    }
    // Alters.
    \Drupal::moduleHandler()->alter('basket_insertOrder', $basketOrderFields, $basketItems, $entity);
    // --
    $orderId = \Drupal::database()->insert('basket_orders')
      ->fields([
        'nid'             => $entity->id(),
        'price'           => !empty($basketOrderFields['price']) ? $basketOrderFields['price'] : 0,
        'goods'           => !empty($basketOrderFields['goods']) ? $basketOrderFields['goods'] : 0,
        'currency'        => !empty($basketOrderFields['currency']) ? $basketOrderFields['currency'] : NULL,
        'status'          => !empty($basketOrderFields['status']) ? $basketOrderFields['status'] : NULL,
        'fin_status'      => !empty($basketOrderFields['fin_status']) ? $basketOrderFields['fin_status'] : NULL,
        'pay_price'       => !empty($basketOrderFields['pay_price']) ? $basketOrderFields['pay_price'] : 0,
        'pay_currency'    => !empty($basketOrderFields['pay_currency']) ? $basketOrderFields['pay_currency'] : NULL,
        'add_price'       => !empty($basketOrderFields['add_price']) ? $basketOrderFields['add_price'] : NULL,
        'delivery_price'  => !empty($basketOrderFields['delivery_price']) ? $basketOrderFields['delivery_price'] : NULL,
      ])
      ->execute();
    $form_state->setValue('orderId', $orderId);
    // Create order.
    if (!empty($orderId)) {
      $orderClass = $this->basket->Orders($orderId);
      $order = $orderClass->load();
      if (!empty($basketItems)) {
        foreach ($basketItems as $basketItem) {
          $order->items[] = (object) $basketItem;
        }
      }
      $form_state->set('BasketOrder', $order);
    }
    // Create payment.
    \Drupal::service('BasketPayment')->createPayment($entity, $form_state);
    // Save order.
    if (!empty($order)) {
      $orderClass->replaceOrder($order);
      $orderClass->save();
    }
    // Alters.
    \Drupal::moduleHandler()->alter('basket_postInsertOrder', $entity, $orderId);
    // Clear.
    if (empty($basketOrderFields['notClearAll'])) {
      $this->cart->clearAll();
    }
    // Send emails.
    $notifications = $this->basket->getSettings('notifications', 'config');
    // Admin.
    if (!empty($notifications['notification_order_admin']) && !empty($notifications['notification_order_admin_mails'])) {
      foreach (explode(PHP_EOL, trim($notifications['notification_order_admin_mails'])) as $email) {
        $this->basket->MailCenter()->send(trim($email), [
          'template'            => 'notification_order_admin',
          'nid'                => $entity->id(),
          'uid'                => $entity->get('uid')->target_id,
        ]);
      }
    }
    // User.
    if (!empty($notifications['notification_order_user']) &&
    !empty($notifications['notification_order_user_field']) &&
    !empty($entity->{$notifications['notification_order_user_field']}) &&
    !empty($userMail = $entity->get($notifications['notification_order_user_field'])->value)) {
      $this->basket->MailCenter()->send(trim($userMail), [
        'template'            => 'notification_order_user',
        'nid'                => $entity->id(),
        'uid'                => $entity->get('uid')->target_id,
      ]);
    }
    // END send emails.
  }

}
