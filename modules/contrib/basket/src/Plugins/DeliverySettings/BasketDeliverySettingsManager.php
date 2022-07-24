<?php

namespace Drupal\basket\Plugins\DeliverySettings;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket DeliverySettings plugin manager.
 *
 * @see \Drupal\basket\Plugins\DeliverySettings\Annotation\BasketDeliverySettings
 * @see \Drupal\basket\Plugins\DeliverySettings\BasketDeliverySettingsInterface
 * @see plugin_api
 */
class BasketDeliverySettingsManager extends DefaultPluginManager {

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
      'Plugin/Basket/DeliverySettings',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\DeliverySettings\BasketDeliverySettingsInterface',
      'Drupal\basket\Plugins\DeliverySettings\Annotation\BasketDeliverySettings'
    );
    $this->alterInfo('basket_delivery_settings_info');
    $this->setCacheBackend($cache_backend, 'basket_delivery_settings_info_plugins');
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
  public function deliverySettingsFormAlter(&$form, $form_state) {
    if (!empty($deliverySettingSystems = $this->getDefinitions())) {
      $form['service'] += [
        '#ajax'     => [
          'wrapper'   => 'basket_delivery_settings_form_ajax_wrap',
          'callback'  => __CLASS__ . '::ajaxReload',
        ],
      ];
      $activeSystem = $form_state->getValue(['service']);
      if (empty($activeSystem) && empty($form_state->getValues()) && !empty($form['service']['#default_value'])) {
        $activeSystem = $form['service']['#default_value'];
      }
      if (!empty($activeSystem)) {
        foreach ($deliverySettingSystems as $keySystem => $deliverySettingSystem) {
          if (empty($deliverySettingSystem['parent_field'])) {
            continue;
          }
          if ($deliverySettingSystem['parent_field'] == $activeSystem) {
            $this->getInstanceById($keySystem)->settingsFormAlter($form, $form_state);
            break;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxReload($form, $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsInfoList($tid, $system) {
    $items = [];
    if (!empty($deliverySettingSystems = $this->getDefinitions())) {
      foreach ($deliverySettingSystems as $keySystem => $deliverySettingSystem) {
        if (empty($deliverySettingSystem['parent_field'])) {
          continue;
        }
        if ($deliverySettingSystem['parent_field'] == $system['id']) {
          $items = $this->getInstanceById($keySystem)->getSettingsInfoList($tid);
          break;
        }
      }
    }
    return $items;
  }

}
