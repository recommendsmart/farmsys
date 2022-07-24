<?php

namespace Drupal\basket;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * {@inheritdoc}
 */
class Basket {

  use DependencySerializationTrait;

  /**
   * Set database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Set fileSystem.
   *
   * @var object
   */
  protected $fileSystem;

  /**
   * Set getCurrentUserPercent.
   *
   * @var int
   */
  protected $getCurrentUserPercent;

  /**
   * Set getNodeTypes.
   *
   * @var array
   */
  protected $getNodeTypes;

  /**
   * Set getNodePrice.
   *
   * @var array
   */
  protected $getNodePrice;

  /**
   * Set getCounts.
   *
   * @var array
   */
  protected $getCounts;

  /**
   * Set getView.
   *
   * @var array
   */
  protected $getView;

  /**
   * Set getIco.
   *
   * @var array
   */
  protected $getIco;

  /**
   * Set subMethods.
   *
   * @var array
   */
  protected $subMethods;

  const M_MAIL = 'hello@alternativecommerce.org';
  const BASKET_FIELD_IMAGES = ['image'];
  const BASKET_FIELD_PRICES = ['basket_price_field'];
  const BASKET_FIELD_COUNT = ['decimal', 'float', 'integer', 'warehouses_field'];

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
    $this->fileSystem = \Drupal::service('file_system');
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    return defined(__CLASS__ . '::' . $name);
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (!defined(__CLASS__ . '::' . $name)) {
      return NULL;
    }
    return constant(__CLASS__ . '::' . $name);
  }

  /**
   * {@inheritdoc}
   */
  public function translate($contextModule = 'basket') {
    if (!isset($this->subMethods['Translate'][$contextModule])) {
      $this->subMethods['Translate'][$contextModule] = new BasketTranslate($contextModule);
    }
    return $this->subMethods['Translate'][$contextModule];
  }

  /**
   * {@inheritdoc}
   */
  public function currency() {
    if (!isset($this->subMethods['Currency'])) {
      $this->subMethods['Currency'] = new BasketCurrency();
    }
    return $this->subMethods['Currency'];
  }

  /**
   * {@inheritdoc}
   */
  public function term() {
    if (!isset($this->subMethods['Term'])) {
      $this->subMethods['Term'] = new BasketTerm();
    }
    return $this->subMethods['Term'];
  }

  /**
   * {@inheritdoc}
   */
  public function orders($orderID = NULL, $orderNID = NULL) {
    if (!isset($this->subMethods['Orders'][$orderID][$orderNID])) {
      $this->subMethods['Orders'][$orderID][$orderNID] = new BasketOrders($orderID, $orderNID);
    }
    return $this->subMethods['Orders'][$orderID][$orderNID];
  }

  /**
   * {@inheritdoc}
   */
  public function basketOrderItems($order) {
    if (!is_object($order)) {
      $order = (object) $order;
    }
    if (!isset($this->subMethods['BasketOrderItems'][$order->id])) {
      $this->subMethods['BasketOrderItems'][$order->id] = new BasketOrderItems($order);
    }
    return $this->subMethods['BasketOrderItems'][$order->id];
  }

  /**
   * {@inheritdoc}
   */
  public function cart() {
    if (!isset($this->subMethods['Cart'])) {
      $this->subMethods['Cart'] = new BasketCart();
    }
    return $this->subMethods['Cart'];
  }

  /**
   * {@inheritdoc}
   */
  public function mailCenter() {
    if (!isset($this->subMethods['MailCenter'])) {
      $this->subMethods['MailCenter'] = new BasketMailCenter();
    }
    return $this->subMethods['MailCenter'];
  }

  /**
   * {@inheritdoc}
   */
  public function token() {
    if (!isset($this->subMethods['Token'])) {
      $this->subMethods['Token'] = new BasketTokens();
    }
    return $this->subMethods['Token'];
  }

  /**
   * {@inheritdoc}
   */
  public function cron() {
    if (!isset($this->subMethods['Cron'])) {
      $this->subMethods['Cron'] = new BasketCron();
    }
    return $this->subMethods['Cron'];
  }

  /**
   * {@inheritdoc}
   */
  public function numberFormat() {
    if (!isset($this->subMethods['NumberFormat'])) {
      $this->subMethods['NumberFormat'] = new BasketNumberFormat();
    }
    return $this->subMethods['NumberFormat'];
  }

  /**
   * {@inheritdoc}
   */
  public function waybill($orderId = NULL) {
    if (!isset($this->subMethods['Waybill'][$orderId])) {
      $this->subMethods['Waybill'][$orderId] = new BasketWaybill($orderId);
    }
    return $this->subMethods['Waybill'][$orderId];
  }

  /**
   * {@inheritdoc}
   */
  public function getIco($icoName, $moduleName = 'basket', $isUseBase = FALSE) {
    if ($moduleName == 'base') {
      $isUseBase = TRUE;
      $moduleName = 'basket';
    }
    if ($isUseBase) {
      if (!isset($this->getIco[$icoName])) {
        $ico_url = drupal_get_path('module', $moduleName) . '/misc/images/' . $icoName;
        $this->getIco[$icoName] = file_exists($ico_url) ? file_get_contents($ico_url) : NULL;
      }
      return $this->getIco[$icoName];
    }
    return $this->full('getIco', [$icoName, $moduleName]);
  }

  /**
   * {@inheritdoc}
   */
  public function getView($view_name, $view_id) {
    if (!isset($this->getView[$view_name])) {
      $this->getView[$view_name] = \Drupal::service('entity_type.manager')->getStorage('view')->load($view_name);
    }
    if (!empty($this->getView[$view_name]) && !empty($this->getView[$view_name]->get('display')[$view_id])) {
      $args = func_get_args();
      unset($args[0], $args[1]);
      return [
        '#type'         => 'view',
        '#name'         => $view_name,
        '#display_id'   => $view_id,
        '#arguments'    => $args,
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function textColor($text = '', $color = NULL, $params = []) {
    $class = !empty($params['class']) ? $params['class'] : [];
    $class[] = 'term_color';
    return [
      '#type'         => 'inline_template',
      '#template'     => '<span class="{{ class }}" style="color:{{color}};">
      	{% if color %}
        	<span class="color_ico" style="background:{{color}};"></span>
        {% endif %}
        {{text|raw}}
      </span>',
      '#context'      => [
        'text'          => $text,
        'color'         => $color,
        'class'         => implode(' ', $class),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCounts($type, $thisView = NULL) {
    if (!isset($this->getCounts[$type])) {
      switch ($type) {
        case'new_orders':
          $this->getCounts[$type] = $this->database->select('basket_orders', 'b')
            ->isNull('b.first_view_uid')
            ->countQuery()
            ->execute()
            ->fetchField();
          break;

        case'goods':
          $this->getCounts[$type] = 0;
          $getNodeTypes = $this->getNodeTypes(FALSE);
          if (!empty($getNodeTypes)) {
            $query = $this->database->select('node_field_data', 'n');
            $query->condition('n.default_langcode', 1);
            $query->condition('n.type', array_keys($getNodeTypes), 'IN');
            $query->addExpression('COUNT(*)', 'total');
            // basket_node_delete.
            $query->leftJoin('basket_node_delete', 'nd', 'nd.nid = n.nid');
            $query->isNull('nd.nid');
            // ---
            $total = $query->execute()->fetchField();
            $this->getCounts[$type] = !empty($total) ? $total : 0;
          }
          break;

        case'goodsInfo':
          $this->getCounts[$type] = [
            'on'        => 0,
            'off'       => 0,
          ];
          if (!empty($thisView->query)) {
            $query = $thisView->query->query();
            // basket_node_delete.
            $query->leftJoin('basket_node_delete', 'nd', 'nd.nid = node_field_data.nid');
            $query->isNull('nd.nid');
            // ON.
            $queryON = clone $query;
            $queryON->condition('node_field_data.status', 1);
            $resultOn = $queryON->countQuery()->execute()->fetchField();
            // OFF.
            $queryOFF = clone $query;
            $queryOFF->condition('node_field_data.status', 0);
            $resultOFF = $queryOFF->countQuery()->execute()->fetchField();
            // ---
            $this->getCounts[$type] = [
              'on'        => !empty($resultOn) ? $resultOn : 0,
              'off'       => !empty($resultOFF) ? $resultOFF : 0,
            ];
          }
          break;

        default:
          $this->getCounts[$type] = 0;
          // Alter.
          \Drupal::moduleHandler()->alter('basket_get_new_count', $this->getCounts[$type], $type);
          // ---
          break;
      }
    }
    return isset($this->getCounts[$type]) ? $this->getCounts[$type] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTypes($loadType = TRUE) {
    if (!isset($this->getNodeTypes)) {
      $this->getNodeTypes = [];
      $query = $this->database->select('basket_node_types', 't');
      $query->fields('t');
      $results = $query->execute()->fetchAll();
      if (!empty($results)) {
        foreach ($results as &$result) {
          if ($loadType) {
            $result->NodeType = \Drupal::service('entity_type.manager')->getStorage('node_type')->load($result->type);
          }
          $this->getNodeTypes[$result->type] = $result;
        }
      }
    }
    return $this->getNodeTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings($type, $name, $settings) {
    $config = \Drupal::configFactory()->getEditable('basket.setting.' . $type);
    $config->set($name, $settings);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($type, $name = NULL) {
    return \Drupal::config('basket.setting.' . $type)->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTypeFields($nodeType, $fieldsTypes = []) {
    $options = [];
    foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('node', $nodeType) as $fieldName => $fieldDefinition) {
      if (!empty($fieldDefinition->getTargetBundle())) {
        if (in_array($fieldDefinition->getType(), $fieldsTypes)) {
          $options[$fieldName] = $fieldDefinition->getLabel() . ' [' . $fieldName . ']';
        }
        if ($fieldDefinition->getType() == 'entity_reference_revisions') {
          $settingsField = $fieldDefinition->getSettings();
          if (!empty($settingsField['handler_settings']['target_bundles'])) {
            foreach ($settingsField['handler_settings']['target_bundles'] as $keyBundle => $bundle) {
              foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', $keyBundle) as $fieldSubName => $fieldSubDefinition) {
                if (in_array($fieldSubDefinition->getType(), $fieldsTypes)) {
                  $options[$fieldName . '->' . $fieldSubName] = $fieldDefinition->getLabel() . ': ' . $fieldSubDefinition->getLabel() . ' [' . $fieldName . '->' . $fieldSubName . ']';
                }
              }
            }
          }
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getError($code = NULL, $smallText = NULL) {
    $element = [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
	    [
				'#prefix'       => '<div class="b_content">',
		    '#suffix'       => '</div>',
		    '#type'         => 'inline_template',
		    '#template'     => '<div class="basket_getError_page">
        	<div class="code">{{code}}</div>
          <div class="text">{{text}}</div>
          {% if smallText %}
          	<div class="small_text">{{ smallText }}</div>
          {% endif %}
        </div>',
		    '#context'      => [
					'code'          => $code,
			    'smallText'     => $smallText,
		    ],
	    ],
    ];
    switch ($code) {
      case 404:
        $element[0]['#context']['text'] = $this->Translate()->t('It seems something went wrong! The page you request does not exist. It may be outdated, deleted, or an invalid address was entered in the address bar.');
        break;

      case 403:
        $element[0]['#context']['text'] = $this->Translate()->t('Access is denied!');
        break;
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUserPercent($uid = NULL) {
    if (is_null($uid)) {
      $uid = \Drupal::currentUser()->id();
    }
    if (!isset($this->getCurrentUserPercent[$uid])) {
      $this->getCurrentUserPercent[$uid][$uid] = 0;
      if (!empty($uid)) {
        $this->getCurrentUserPercent[$uid][$uid] = $this->database->select('basket_user_percent', 'p')
          ->fields('p', ['percent'])
          ->condition('p.uid', $uid)
          ->execute()->fetchField();
        if (empty($this->getCurrentUserPercent[$uid][$uid])) {
          $this->getCurrentUserPercent[$uid][$uid] = 0;
        }
      }
    }
    return $this->getCurrentUserPercent[$uid][$uid];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodePrice($entity, $priceType, $filter = []) {
    $key = $entity->id() . '_' . $priceType;
    if (!empty($filter)) {
      $key .= '_' . implode('_', $filter);
    }
    if (!isset($this->getNodePrice[$key])) {
      $this->getNodePrice[$key] = \Drupal::getContainer()->get('BasketQuery')->getNodePriceMin($entity, $priceType, $filter);
    }
    return $this->getNodePrice[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function arrayMergeRecursive(array &$array1, array &$array2) {
    $merged = $array1;
    if (is_array($array2)) {
      foreach ($array2 as $key => $val) {
        if (is_array($array2[$key])) {
          $merged[$key] = $merged[$key] ?? [];
          $merged[$key] = is_array($merged[$key]) ? $this->arrayMergeRecursive($merged[$key], $array2[$key]) : $array2[$key];
        } else {
          $merged[$key] = $val;
        }
      }
    }
    return $merged;
  }

  /**
   * Load Basket Item.
   */
  public function loadBasketItem($id, $orderId = NULL) {
    if (!empty($orderId)) {
      $filePath = $this->fileSystem->realpath('temporary://OrderTempItems_' . date('d_m_Y') . '/' . $orderId);
      if (file_exists($filePath)) {
        $orderTempItems = @json_decode(file_get_contents($filePath), TRUE);
        if (!empty($orderTempItems[(string) $id])) {
          $item = (object) unserialize($orderTempItems[$id]);
          $item->params = !empty($item->params) ? $this->Cart()->decodeParams($item->params) : [];
        }
      }
    }
    if (empty($item)) {
      $item = $this->database->select('basket_orders_item', 'i')
        ->fields('i')
        ->condition('i.id', $id)
        ->execute()->fetchObject();
      if (!empty($item)) {
        $this->unserializeItem($item);
      }
    }
    return $item;
  }

  /**
   * UnserializeItem.
   */
  public function unserializeItem(&$item) {
    /*node_fields*/
    $item->node_fields = unserialize($item->node_fields);
    /*discount*/
    $item->discount = !empty($item->discount) ? unserialize($item->discount) : ['percent' => 0];
    // Params.
    $item->params = !empty($item->params) ? $this->Cart()->decodeParams($item->params) : [];
    // params_html.
    $item->params_html = !empty($item->params_html) ? unserialize($item->params_html) : NULL;
  }

  /**
   * Save Basket item temp.
   */
  public function addBasketItemTemp($item, $orderID) {
    $dir = $this->fileSystem->realpath('temporary://OrderTempItems_' . date('d_m_Y'));
    $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $filePath = $dir . '/' . $orderID;
    $orderTempItems = [];
    if (file_exists($filePath)) {
      $orderTempItems = @json_decode(file_get_contents($filePath), TRUE);
    }
    $orderTempItems[(string) $item->id] = serialize($item);
    file_put_contents($filePath, json_encode($orderTempItems));
  }

  /**
   * {@inheritdoc}
   */
  public function paymentFinish($nid) {
    /*Alter*/
    \Drupal::moduleHandler()->invokeAll('basket_paymentFinish', [$nid]);
    /*End alter*/
    $getPayInfo = \Drupal::service('BasketPayment')->getPayInfo(NULL, $nid);
    if (!empty($getPayInfo->pid)) {
      @list($paySystem, $payId) = explode('|', $getPayInfo->payInfo);
      \Drupal::service('BasketPayment')->getInstanceByID($paySystem)->updateOrderBySettings(
				$getPayInfo->pid,
	      $this->Orders(NULL, $getPayInfo->nid)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo() {
    $logo = $this->full('getLogo');
    return !empty($logo) ? $logo : $this->getIco('logo.svg', 'base');
  }

  /**
   * {@inheritdoc}
   */
  public function getMail() {
    $mail = $this->full('getMail');
    return !empty($mail) ? $mail : $this::M_MAIL;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass($className, $isDynamic = FALSE) {
    if (!isset($this->subMethods['getClass'][$className]) || $isDynamic) {
      $this->subMethods['getClass'][$className] = new $className();
    }
    return $this->subMethods['getClass'][$className];
  }

  /**
   * {@inheritdoc}
   */
  public function full($func, $args = []) {
    if (\Drupal::hasService('BasketFull')) {
      if (method_exists(\Drupal::service('BasketFull'), $func)) {
        return call_user_func_array([\Drupal::service('BasketFull'), $func], $args);
      }
    }
    return NULL;
  }

}
