<?php

namespace Drupal\basket;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class BasketCart {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set $basketQuery.
   *
   * @var object
   */
  protected $basketQuery;
  
  /**
   * Set db.
   *
   * @var object
   */
  protected $db;

  /**
   * Set sid.
   *
   * @var string
   */
  protected $sid = NULL;

  /**
   * Set sidCreate.
   *
   * @var bool
   */
  protected $sidCreate = FALSE;

  /**
   * Set getItem.
   *
   * @var object
   */
  protected $getItem;

  /**
   * Set setItem.
   *
   * @var object
   */
  protected $setItem;

  /**
   * Set getCount.
   *
   * @var int
   */
  protected $getCount = 0;

  /**
   * Set getItemsInBasket.
   *
   * @var array
   */
  protected $getItemsInBasket = NULL;

  /**
   * Set getTotalSum.
   *
   * @var int
   */
  protected $getTotalSum = NULL;

  /**
   * Set loadItem.
   *
   * @var array
   */
  protected $loadItem = NULL;

  /**
   * Set getItemPrice.
   *
   * @var array
   */
  protected $getItemPrice = NULL;

  /**
   * Set getItemDiscount.
   *
   * @var array
   */
  protected $getItemDiscount = NULL;

  /**
   * Set getItemImg.
   *
   * @var array
   */
  protected $getItemImg = NULL;

  /**
   * Set getNodeEntity.
   *
   * @var array
   */
  protected $getNodeEntity = NULL;

  /**
   * Set getCurrencyName.
   *
   * @var string
   */
  protected $getCurrencyName;

  const MAX_INT = 2147483647;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketQuery = \Drupal::getContainer()->get('BasketQuery');
    $this->db = \Drupal::getContainer()->get('database');
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (isset($this->{$name})) {
      return $this->{$name};
    }
    return NULL;
  }

  /**
   * The processing process of adding goods to the cart.
   */
  public function add($add) {
    // ---
    if (empty($add['nid'])) {
      return FALSE;
    }
    // ---
    $this->sidCreate = TRUE;
    $params = $this->encodeParams(!empty($add['params']) ? $add['params'] : []);
    $this->getItem = $this->getItem($add['nid'], $params);
    // ---
    $addCount = !empty($add['count']) && $add['count'] > 0 ? $add['count'] : 1;
    if (!empty($this->getItem->id)) {
      $entityAdd = \Drupal::entityTypeManager()->getStorage('node')->load($add['nid']);
      if (!empty($entityAdd)) {
        $nodeTypes = $this->basket->getNodeTypes(FALSE);
        if (!empty($nodeTypes[$entityAdd->bundle()]->extra_fields)) {
          $extraFields = unserialize($nodeTypes[$entityAdd->bundle()]->extra_fields);
          if (!empty($extraFields['add_count_sum']['on'])) {
            $addCount = $addCount + $this->getItem->count;
          }
        }
      }
    }

    $this->setItem = (object) [
      'id'        => !empty($this->getItem->id) ? $this->getItem->id : NULL,
      'sid'       => $this->getSid(),
      'nid'       => $add['nid'],
      'count'     => $addCount,
      'add_time'  => time(),
      'all_params' => $params,
    ];
    $update_array = (array) $this->setItem;
    if ($update_array['count'] >= $this::MAX_INT) {
      $update_array['count'] = $this::MAX_INT;
    }
    unset($update_array['id']);
    if (empty($this->getItem->id)) {
      $this->setItem->id = $this->db->insert('basket')
        ->fields($update_array)
        ->execute();
    }
    else {
      $this->db->update('basket')
        ->fields($update_array)
        ->condition('id', $this->getItem->id)
        ->execute();
    }
    if (isset($GLOBALS['BasketCartSetItemID'])) {
      $GLOBALS['BasketCartSetItemID'] = $this->setItem->id;
    }
    // Remove basket items cache.
    $this->getItemsInBasket = NULL;
    $this->getItemDiscount = NULL;
    $this->getItemPrice = NULL;
    $this->getTotalSum = NULL;
    // Hook cart add.
    $cartItem = !empty($this->getItem->id) ? $this->loadItem($this->getItem->id) : $this->loadItem($this->setItem->id);
    \Drupal::moduleHandler()->invokeAll('basket_cart', [$cartItem, 'add']);
    // ---
  }

  /**
   * Search for a product in the cart.
   */
  public function getItem($nid, $params) {
    return $this->db->select('basket', 'b')
      ->fields('b')
      ->condition('b.sid', $this->getSid())
      ->condition('b.nid', $nid)
      ->condition('b.all_params', $params)
      ->execute()->fetchObject();
  }

  /**
   * The process of updating the product to the cart.
   */
  public function updateCount($update) {
    if (empty($update['update_id'])) {
      return FALSE;
    }
    if (empty($update['count'])) {
      return FALSE;
    }
    $this->sidCreate = FALSE;
    if ($update['count'] >= $this::MAX_INT) {
      $update['count'] = $this::MAX_INT;
    }
    $this->db->update('basket')
      ->fields([
        'count'     => $update['count'] > 0 ? $update['count'] : 1,
      ])
      ->condition('sid', $this->getSid())
      ->condition('id', $update['update_id'])
      ->execute();

    // Remove basket items cache.
    $this->reset();
    // Hook cart updateCount.
    $cartItem = $this->loadItem($update['update_id']);
    \Drupal::moduleHandler()->invokeAll('basket_cart', [
      $cartItem,
      'updateCount',
    ]);
    // ---
  }

  /**
   * The process of removing goods in the basket.
   *
   * @param int|array $delete
   *   Item to delete.
   *
   *   Examples:
   *   $this->basket->Cart()->loadItem(12); // API, recomended
   *   $this->basket->Cart()->loadItem(['delete_item' => 12]); // Alternative.
   */
  public function deleteItem($delete) {
    if (is_array($delete) && isset($delete['delete_item'])) {
      $delete = $delete['delete_item'];
    }
    if (!is_numeric($delete)) {
      return FALSE;
    }

    $this->sidCreate = FALSE;
    $sid = $this->getSid();

    // Hook cart updateCount.
    $cartItem = $this->loadItem($delete);
    \Drupal::moduleHandler()->invokeAll('basket_cart', [$cartItem, 'delete']);
    // ---
    // If anonymous, the SID is not created and NULL is returned.
    if (!empty($sid)) {
      $this->db->delete('basket')
        ->condition('sid', $sid)
        ->condition('id', $delete)
        ->execute();

      // Remove basket items cache.
      $this->reset();
    }
  }

  /**
   * Update item.
   *
   * @param object $item
   *   Cart item.
   */
  public function updateItem($item) {
    if (empty($item->nid)) {
      return FALSE;
    }
    $params = $this->encodeParams(!empty($item->all_params) ? $item->all_params : []);
    $update_array = [
      'sid'       => $this->getSid(),
      'nid'       => $item->nid,
      'count'     => !empty($item->count) ? $item->count : 1,
      'all_params' => $params,
    ];

    $id = $this->db->update('basket')
      ->fields($update_array)
      ->condition('id', $item->id)
      ->execute();

    // Remove basket items cache.
    $this->reset();
    return $id;
  }

  /*
   * Remove basket items cache.
   */
  public function reset() {
    $this->getItemsInBasket = NULL;
    $this->getItemPrice = NULL;
    $this->getTotalSum = NULL;
    $this->loadItem = NULL;
  }

  /**
   * The process of returning parameters popup after adding to the basket.
   */
  public function getPopupInfo() {
    $info = [
      'popup_title'       => $this->basket->translate()->t('Item successfully added'),
      'links'             => [
        'basket'            => [
          'text'              => $this->basket->translate()->t('Checkout'),
          'attributes'        => new Attribute([
            'href'              => Url::fromRoute('basket.pages', [
              'page_type'         => 'view',
            ])->toString(),
            'class'             => ['button', 'basket-popup-link'],
          ]),
        ],
        'close'             => [
          'text'              => $this->basket->translate()->t('Continue shopping'),
          'attributes'        => new Attribute([
            'href'              => 'javascript:void(0);',
            'class'             => ['button', 'basket-popup-link', 'close-link'],
            'onclick'           => \Drupal::getContainer()->get('BasketPopup')->getCloseOnclick(),
          ]),
        ],
      ],
      'getItem'           => $this->getItem,
      'setItem'           => $this->setItem,
    ];
    // Alter.
    \Drupal::moduleHandler()->alter('basket_add_popup', $info);
    // ---
    return $info;
  }

  /**
   * Quantity in the basket.
   */
  public function getCount() {
    if (!empty($this->getSid())) {
      $query = $this->db->select('basket', 'b');
      $query->addExpression('SUM(b.count)', 'count');
      $query->condition('b.sid', $this->getSid());
      // ---
      $getNodeTypes = $this->basket->getNodeTypes();
      if (!empty($getNodeTypes)) {
        $get_types = [];
        foreach ($getNodeTypes as $getNodeType) {
          $get_types[$getNodeType->type] = $getNodeType->type;
        }
        $query->innerJoin('node_field_data', 'n', 'n.nid = b.nid');
        $query->condition('n.type', $get_types, 'in');
        $query->condition('n.status', 1);
        $query->condition('n.default_langcode', 1);
      }
      // ---
      $getCount = $query->execute()->fetchField();
    }
    $this->getCount = !empty($getCount) ? $getCount : 0;
    return $this->getCount;
  }

  /**
   * The total amount of orders basket.
   */
  public function getTotalSum($notDiscount = FALSE, $notDelivety = FALSE) {
		$tKey = 'total_' . ($notDiscount ? 1 : 0) . '_' . ($notDelivety ? 1 : 0);
		if(!empty($GLOBALS['replaceGetItemsInBasket'])) {
			$tKey .= '_' . md5(serialize($GLOBALS['replaceGetItemsInBasket']));
		}
    if (isset($this->getTotalSum[$tKey])) {
      return $this->getTotalSum[$tKey];
    }
    $getTotalSum = 0;
    if (!empty($this->getItemsInBasket())) {
      $getItemsInBasket = $this->getItemsInBasket();
      foreach ($getItemsInBasket as $row) {
        $getItemPrice = $this->getItemPrice($row);
        if (empty($notDiscount)) {
          $getItemDiscount = $this->getItemDiscount($row);
          if (!empty($getItemDiscount)) {
            $getItemPrice = $getItemPrice - ($getItemPrice / 100 * $getItemDiscount);
          }
        }
        $getTotalSum += $getItemPrice * $row->count;
      }
      // Add delivery.
      if (empty($notDelivety)) {
        $deliveryInfo = \Drupal::getContainer()->get('BasketDelivery')->getDeliveryInfo();
        if (!empty($deliveryInfo['sum']) && !empty($deliveryInfo['isPay'])) {
          $getTotalSum += $deliveryInfo['sum'];
        }
      }
      // Alter.
      \Drupal::moduleHandler()->alter('basket_getTotalSum', $getTotalSum, $getItemsInBasket, $notDiscount, $notDelivety);
      // End alter.
    }
    $this->getTotalSum[$tKey] = $getTotalSum;
    return $getTotalSum;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayInfo() {
    $info = [
      'price'     => $this->getTotalSum(),
      'currency'  => $this->basket->currency()->getPayCurrency(),
    ];
    $cur = $this->basket->currency()->getCurrent();
    $this->basket->currency()->priceConvert($info['price'], $cur, FALSE, $info['currency']);
    $info['currency'] = $this->basket->currency()->load($info['currency']);
    // Alter.
    \Drupal::moduleHandler()->alter('basket_getPayInfo', $info);
    // End alter.
    return $info;
  }

  /**
   * Receipt of item cost.
   */
  public function getItemPrice($row, $returnInfo = FALSE, $keyPriceField = 'MIN') {
    if (is_array($row) && !empty($row['id'])) {
      $row = $this->loadItem($row['id']);
    }
    if (!empty($row)) {
      $key = implode('-', [$row->nid, $row->id, $keyPriceField]);
      if (!isset($this->getItemPrice[$key])) {
        $price = NULL;
        // Alter.
        \Drupal::moduleHandler()->alter('basket_getItemPrice', $price, $row);
        // Is empty price.
        if (is_null($price) && !empty($this->getNodeEntity($row->nid))) {
          $getInfo = $this->basketQuery->getNodePriceMin($this->getNodeEntity($row->nid), $keyPriceField);
          if ($returnInfo) {
            return $getInfo;
          }
          if (!empty($getInfo->priceConvert)) {
            $price = $getInfo->priceConvert;
          }
          $row->priceOld = $getInfo->priceConvertOld ?? 0;
        }
        $row->priceItem = $price;
        // Alter.
        \Drupal::moduleHandler()->alter('basket_getItemPriceAfter', $price, $row);
        // ---
        $this->getItemPrice[$key] = $price;
      }
      if(is_null($this->getItemPrice[$key])) {
        $this->getItemPrice[$key] = 0;
      }
      return round($this->getItemPrice[$key], 2);
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function loadItem($id) {
    if (!isset($this->loadItem[$id])) {
      $this->loadItem[$id] = $this->db->select('basket', 'b')
        ->fields('b')
        ->condition('b.id', $id)
        ->execute()->fetchObject();
      if (!empty($this->loadItem[$id]->all_params)) {
        $this->loadItem[$id]->all_params = $this->decodeParams($this->loadItem[$id]->all_params);
      }
    }
    return $this->loadItem[$id];
  }

  /**
   * Receipt of item discount.
   */
  public function getItemDiscount($row) {
    if (is_array($row) && !empty($row['id'])) {
      $row = $this->loadItem($row['id']);
    }
    if (!empty($row)) {
      if (!isset($this->getItemDiscount[$row->nid . '-' . $row->id])) {
        $this->getItemDiscount[$row->nid . '-' . $row->id] = NULL;
        // Alter.
        \Drupal::moduleHandler()->alter('basket_getItemDiscount', $this->getItemDiscount[$row->nid . '-' . $row->id], $row);
        // --
        if (is_null($this->getItemDiscount[$row->nid . '-' . $row->id])) {
          $discounts = \Drupal::getContainer()->get('BasketDiscount')->getDiscounts($row);
          $this->getItemDiscount[$row->nid . '-' . $row->id] = max($discounts);
        }
      }
      return $this->getItemDiscount[$row->nid . '-' . $row->id];
    }
    return 0;
  }

  /**
   * Getting a picture of the item.
   */
  public function getItemImg($row) {
    if (is_array($row) && !empty($row['id'])) {
      $row = $this->loadItem($row['id']);
    }
    if (!empty($row)) {
      if (!isset($this->getItemImg[$row->nid . '-' . $row->id])) {
        $fid = 0;
        // Alter.
        \Drupal::moduleHandler()->alter('basket_getItemImg', $fid, $row);
        // Is empty FID.
        if (empty($fid) && !empty($this->getNodeEntity($row->nid))) {
          $fid = $this->basketQuery->getNodeImgFirst($this->getNodeEntity($row->nid));
        }
        $this->getItemImg[$row->nid . '-' . $row->id] = !empty($fid) ? $fid : 0;
      }
      return $this->getItemImg[$row->nid . '-' . $row->id];
    }
    return 0;
  }

  /**
   * List of items added to shopping cart.
   */
  public function getItemsInBasket() {
    if (!empty($GLOBALS['replaceGetItemsInBasket'])) {
			return $GLOBALS['replaceGetItemsInBasket'];
    }
		if (!empty($this->getSid()) && empty($this->getItemsInBasket)) {
      $query = NULL;
      // Alter
      \Drupal::moduleHandler()->alter('basket_getItemsInBasketQuery', $query);
      // ---
      if(is_null($query)) {
        $query = $this->db->select('basket', 'b');
        $query->fields('b');
        $query->condition('b.sid', $this->getSid());
        // ---
        $getNodeTypes = $this->basket->getNodeTypes();
        if (!empty($getNodeTypes)) {
          $get_types = [];
          foreach ($getNodeTypes as $getNodeType) {
            $get_types[$getNodeType->type] = $getNodeType->type;
          }
          $query->innerJoin('node_field_data', 'n', 'n.nid = b.nid');
          $query->condition('n.type', $get_types, 'in');
          $query->condition('n.status', 1);
          $query->condition('n.default_langcode', 1);
        }
        // ---
        $query->orderBy('b.add_time', 'DESC');
      }
      $this->getItemsInBasket = $query->execute()->fetchAll();
      if (!empty($this->getItemsInBasket)) {
        foreach ($this->getItemsInBasket as &$row) {
          $row->all_params = $this->decodeParams($row->all_params);
        }
      }
    }
    return $this->getItemsInBasket ?? [];
  }

  /**
   * Current currency name.
   */
  public function getCurrencyName() {
    if (!isset($this->getCurrencyName)) {
      $currency = $this->basket->Currency();
      $this->getCurrencyName = $currency->load($currency->getCurrent())->name;
    }
    return $this->getCurrencyName;
  }

  /**
   * Unique user ID in the cart.
   */
  public function getSid() {
    if (!isset($this->sid)) {
      if (!\Drupal::currentUser()->isAnonymous()) {
        $this->sid = \Drupal::currentUser()->id();
      }
      else {
        if (empty($_SESSION['basket_user_sid']) && !empty($this->sidCreate)) {
          $_SESSION['basket_user_sid'] = \Drupal::service('session')->getId();
        }
        $this->sid = !empty($_SESSION['basket_user_sid']) ? $_SESSION['basket_user_sid'] : NULL;
      }
    }
    return $this->sid;
  }

  /**
   * {@inheritdoc}
   */
  public function clearAll() {
    if (!empty($this->getSid())) {
      $this->db->delete('basket')
        ->condition('sid', $this->getSid())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function movingItems($uid) {
    if (!empty($_SESSION['basket_user_sid'])) {
      $this->db->update('basket')
        ->fields([
          'sid'       => $uid,
        ])
        ->condition('sid', $_SESSION['basket_user_sid'])
        ->execute();
    }
  }

  /**
   * Conversion to parameter string.
   */
  public function encodeParams($params) {
    if (!is_array($params)) {
      return $params;
    }
    if (!empty($params)) {
      $this->sortParams($params);
    }
    return Yaml::encode($params);
  }

  /**
   * {@inheritdoc}
   */
  public function decodeParams($params) {
    if (is_array($params)) {
      return $params;
    }
    return Yaml::decode($params);
  }

  /**
   * Sorting parameter keys.
   */
  public function sortParams(&$params) {
    foreach ($params as &$param) {
      if (is_array($param)) {
        $this->sortParams($param);
      }
      else {
        $param = (string) $param;
      }
    }
    ksort($params);
  }

  /**
   * {@inheritdoc}
   */
  private function getNodeEntity($nid) {
    if (empty($this->getNodeEntity[$nid])) {
      $this->getNodeEntity[$nid] = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    }
    return $this->getNodeEntity[$nid];
  }

}
