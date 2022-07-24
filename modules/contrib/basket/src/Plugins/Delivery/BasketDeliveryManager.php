<?php

namespace Drupal\basket\Plugins\Delivery;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\basket\Ajax\BasketReplaceCommand;

/**
 * Provides an Basket Delivery plugin manager.
 *
 * @see \Drupal\basket\Plugins\Delivery\Annotation\BasketDelivery
 * @see \Drupal\basket\Plugins\Delivery\BasketDeliveryInterface
 * @see plugin_api
 */
class BasketDeliveryManager extends DefaultPluginManager {

  /**
   * Constructs a DeliveryManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Basket/Delivery',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Delivery\BasketDeliveryInterface',
      'Drupal\basket\Plugins\Delivery\Annotation\BasketDelivery'
    );
    $this->alterInfo('basket_delivery_info');
    $this->setCacheBackend($cache_backend, 'basket_delivery_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceById(string $id) {
    $defs = $this->getDefinitions();
    if (!isset($defs[$id])) {
      return FALSE;
    }
    return $this->getInstance($defs[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (!$this->providerExists($options['provider'])) {
      return FALSE;
    }
    static $cache;
    if (isset($cache[$options['id']])) {
      return $cache[$options['id']];
    }

    $cls = $options['class'];
    $instance = new $cls();
    // @todo .
    $cache[$options['id']] = $instance;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function extraFieldFormAlter(&$form, &$form_state, $entity) {
    if (empty(\Drupal::service('Basket')->getSettings('enabled_services', 'delivery'))) {
      return [];
    }
		$ajaxSuffix = $entity->basketFormAjaxSuffix ?? '';
    $form['#attached']['library'][] = 'basket/basket.js';
    // Alter.
    \Drupal::moduleHandler()->alter('basket_delivery_preInit', $form, $form_state);
    // ---
    $storage = $form_state->getStorage();
    if (empty($form_state->getValue('basket_delivery'))) {
      if (!empty($storage['basket_delivery'])) {
        $form_state->setValue('basket_delivery', $storage['basket_delivery']);
      }
      elseif (empty($entity->id())) {
        $deliveryDefault = \Drupal::service('Basket')->getSettings('active_services', 'delivery_default');
        if (!empty($deliveryDefault) && is_array($deliveryDefault)) {
          $form_state->setValue(['basket_delivery', 'value'], key($deliveryDefault));
        }
      }
    }
    $storage['basket_delivery'] = $form_state->getValue('basket_delivery');
    $form_state->setStorage($storage);
    // ---
    $access = !empty($entity->BasketDeliveryAccess);
    if (!$access) {
      $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.' . $entity->getType() . '.default');
      if ($form_display->getComponent('basket_delivery')) {
        $access = TRUE;
      }
    }
    if ($access) {
      $options = \Drupal::service('Basket')->Term()->getOptions('delivery');
      if (!empty($options)) {
        $activeDelivery = \Drupal::service('Basket')->getSettings('active_services', 'delivery');
        foreach ($options as $key => $value) {
          $access = !empty($activeDelivery[$key]);
          // Alter.
          if ($access && empty($entity->id())) {
            \Drupal::moduleHandler()->alter('basket_delivery_option_access', $access, $key, $form_state);
          }
          // ---
          if (!$access) {
            if (\Drupal::currentUser()->hasPermission('basket access_page order_page')) {
              $options[$key] .= ' (' . \Drupal::service('Basket')->Translate()->t('Not active on site') . ')';
            }
            else {
              unset($options[$key]);
            }
          }
        }
      }
      $did = $form_state->getValue(['basket_delivery', 'value']);
			$deliveryWidget = \Drupal::service('Basket')->getSettings('enabled_services', 'delivery_widget');
			$form['basket_delivery'] = [
				'#prefix'       => '<div id="delivery_ajax_wrap'.$ajaxSuffix.'">',
				'#suffix'       => '</div>',
				'#tree'         => TRUE,
				'#type'         => 'container',
				'value'         => [
					'#type'         => !empty($deliveryWidget) ? $deliveryWidget : 'select',
					'#title'        => \Drupal::service('Basket')->Translate()->t('Delivery'),
					'#options'      => $options,
					'#empty_option' => \Drupal::service('Basket')->Translate()->t('Not specified'),
					'#ajax'         => [
						'wrapper'       => 'delivery_ajax_wrap'.$ajaxSuffix,
						'callback'      => __CLASS__ . '::ajaxReloadDelivery',
						'progress'      => ['type' => 'fullscreen'],
						'disable-refocus' => TRUE
					],
					'#required'     => !empty($options),
					'#default_value' => $did,
				],
				'fields'        => [
					'#type'         => 'container',
					'#ajax_wrap'    => [
						'wrapper'       => 'delivery_ajax_wrap'.$ajaxSuffix,
						'callback'      => __CLASS__ . '::ajaxReloadDelivery',
						'progress'      => ['type' => 'fullscreen'],
						'disable-refocus' => TRUE
					],
				],
			];
			$_SESSION['delivery_tid'] = $did;
      if (!empty($entity->id()) && empty($form_state->getValues())) {
        $order = \Drupal::service('Basket')->Orders(NULL, $entity->id())->load();
        if (!empty($order->delivery_id)) {
          $form['basket_delivery']['value']['#default_value'] = $order->delivery_id;
          $form_state->setValue('basket_delivery', [
            'value'     => $order->delivery_id,
          ]);
        }
      }
      $deliveryValue = $form_state->getValue(['basket_delivery', 'value']);
      if (!empty($deliveryValue)) {
        $deliveryService = $this->loadService($deliveryValue);
        if (!empty($deliveryService)) {
          $form['basket_delivery']['fields']['#parents'] = $deliveryService->basketFieldParents();
          $deliveryService->basketFormAlter($form['basket_delivery']['fields'], $form_state);
          if (!empty($form['basket_delivery']['fields']['#validate'])) {
            foreach ($form['basket_delivery']['fields']['#validate'] as $validate) {
              $form['#validate'][] = $validate;
            }
          }
        }
      }
      $form['actions']['submit']['#submit'][] = __CLASS__ . '::basketDeliverySubmit';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxReloadDelivery($form, $form_state) {
    $did = $form_state->getValue(['basket_delivery', 'value']);
    $_SESSION['delivery_tid'] = $did;
    $response = new AjaxResponse();
    if(!empty($form['basket_delivery']['#group'])) {
      unset($form['basket_delivery']['#group']);
    }
		$entity = $form_state->getBuildInfo()['callback_object']->getEntity();
		$ajaxSuffix = $entity->basketFormAjaxSuffix ?? '';
    $response->addCommand(new BasketReplaceCommand('#delivery_ajax_wrap'.$ajaxSuffix, $form['basket_delivery']));
    if (!empty($form['basket_payment'])) {
      if(!empty($form['basket_payment']['#group'])) {
        unset($form['basket_payment']['#group']);
      }
      $response->addCommand(new BasketReplaceCommand('#payment_ajax_wrap'.$ajaxSuffix, $form['basket_payment']));
    }
    // Alter.
    \Drupal::moduleHandler()->alter('basket_ajaxReloadDelivery', $response);
    // ---
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function loadService($deliveryValue) {
    if (!empty($serviceId = \Drupal::service('Basket')->getSettings('delivery_services', $deliveryValue))) {
      return $this->getInstanceById($serviceId);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function basketDeliverySubmit($form, $form_state) {
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    $deliveryService = \Drupal::service('BasketDelivery');
    if (!empty($entity->id())) {
      /*Delete delivery services info all*/
      foreach ($deliveryService->getDefinitions() as $delivery) {
        $deliveryService->getInstanceById($delivery['id'])->basketDelete($entity, FALSE);
      }
      // Save delivery service info.
      $deliveryServiceCurrent = NULL;
      $deliveryValue = $form_state->getValue(['basket_delivery', 'value']);
      if (!empty($deliveryValue)) {
        $deliveryServiceCurrent = $deliveryService->loadService($deliveryValue);
        if (!empty($deliveryServiceCurrent)) {
          $deliveryServiceCurrent->basketSave($entity, $form_state);
        }
      }
      \Drupal::database()->merge('basket_orders_delivery')
        ->key([
          'nid'       => $entity->id(),
        ])
        ->fields([
          'did'       => !empty($deliveryValue) ? $deliveryValue : NULL,
          'address'   => !empty($deliveryServiceCurrent) ? serialize($deliveryServiceCurrent->basketGetAddress($entity)) : NULL,
        ])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDeliveryInfo($entity = NULL) {
    $info = [
      'sum'           => 0,
      'isPay'         => TRUE,
      'description'   => NULL,
      'entity'        => $entity,
    ];
    if (!empty($_SESSION['delivery_tid'])) {
      $delivery = \Drupal::service('Basket')->Term()->load($_SESSION['delivery_tid']);
      if (!empty($delivery->delivery_sum)) {
        \Drupal::service('Basket')->Currency()->PriceConvert($delivery->delivery_sum, $delivery->delivery_currency);
        $info['sum'] = $delivery->delivery_sum;
      }
      $deliveryService = $this->loadService($_SESSION['delivery_tid']);
      if (!empty($deliveryService)) {
        $deliveryService->deliverySumAlter($info);
      }
    }
    return $info;
  }

}
