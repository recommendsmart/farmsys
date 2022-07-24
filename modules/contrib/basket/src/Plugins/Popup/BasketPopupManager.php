<?php

namespace Drupal\basket\Plugins\Popup;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket Popup plugin manager.
 *
 * @see \Drupal\basket\Plugins\Popup\Annotation\BasketPopupSystem
 * @see \Drupal\basket\Plugins\Popup\BasketPopupSystemInterface
 * @see plugin_api
 */
class BasketPopupManager extends DefaultPluginManager {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set isSite.
   *
   * @var bool
   */
  protected $isSite;

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
      'Plugin/Basket/Popup',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Popup\BasketPopupSystemInterface',
      'Drupal\basket\Plugins\Popup\Annotation\BasketPopupSystem'
    );
    $this->alterInfo('basket_popup_system_info');
    $this->setCacheBackend($cache_backend, 'basket_popup_system_info_plugins');
    $this->basket = \Drupal::service('Basket');
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
  public function isSite() {
    $this->isSite = TRUE;
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
    $cache[$options['id']] = $instance;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = [];
    foreach ($this->getDefinitions() as $def) {
      $options[$def['id']] = $def['name'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function openModal(&$response, $title = '', $html = [], $options = []) {
    if (!empty($this->isSite)) {
      $popup_plugin = $this->basket->getSettings('popup_plugin', 'config.site');
    }
    else {
      $popup_plugin = $this->basket->getSettings('popup_plugin', 'config.admin');
    }
    $this->getInstanceById(trim($popup_plugin))->open($response, $title, $html, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCloseOnclick() {
    if (!empty($this->isSite)) {
      $popup_plugin = $this->basket->getSettings('popup_plugin', 'config.site');
    }
    else {
      $popup_plugin = $this->basket->getSettings('popup_plugin', 'config.admin');
    }
    return $this->getInstanceById($popup_plugin)->getCloseOnclick();
  }

}
