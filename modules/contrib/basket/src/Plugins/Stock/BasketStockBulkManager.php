<?php

namespace Drupal\basket\Plugins\Stock;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket Stock plugin manager.
 *
 * @see \Drupal\basket\Plugins\Stock\Annotation\BasketStockBulk
 * @see \Drupal\basket\Plugins\Stock\BasketStockBulkInterface
 * @see plugin_api
 */
class BasketStockBulkManager extends DefaultPluginManager {

  /**
   * Constructs a ParamsManager object.
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
      'Plugin/Basket/Stock',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Stock\BasketStockBulkInterface',
      'Drupal\basket\Plugins\Stock\Annotation\BasketStockBulk'
    );
    $this->alterInfo('basket_stock_bulk_info');
    $this->setCacheBackend($cache_backend, 'basket_stock_bulk_info_plugins');
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
  public function getIco($id) {
    $service = $this->getInstanceById($id);
    if ($service) {
      return $service->getIcoContent();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm($id, $nids) {
    foreach ($this->getDefinitions() as $plugin) {
      if ($plugin['id'] == $id) {
        $form = new $plugin['class']([
          'nids'      => $nids,
          'service'   => $plugin,
        ]);
      }
    }
    if (!empty($form)) {
      return \Drupal::formBuilder()->getForm($form);
    }
    return [];
  }

}
