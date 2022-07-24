<?php

namespace Drupal\basket\Plugins\Discount;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket Discount plugin manager.
 *
 * @see \Drupal\basket\Plugins\Discount\Annotation\BasketDiscount
 * @see \Drupal\basket\Plugins\Discount\BasketDiscountInterface
 * @see plugin_api
 */
class BasketDiscountManager extends DefaultPluginManager {

  /**
   * Set Basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Constructs a PaymentManager object.
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
      'Plugin/Basket/Discount',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Discount\BasketDiscountInterface',
      'Drupal\basket\Plugins\Discount\Annotation\BasketDiscount'
    );
    $this->alterInfo('basket_discount_info');
    $this->setCacheBackend($cache_backend, 'basket_discount_info_plugins');
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
  public function getDiscounts($row) {
    $discounts = ['_empty_' => 0];
    $GLOBALS['cartNotDiscountMessage'] = FALSE;
    if ($this->basket->getSettings('enabled_services', 'discount_system')) {
      $actives = $this->basket->getSettings('discount_system', 'config');
      foreach ($this->getDefinitions() as $service) {
        if (empty($actives[$service['id']]['active'])) {
          continue;
        }
        if (!empty($GLOBALS['cartNotDiscount'][$service['id']])) {
          $GLOBALS['cartNotDiscountMessage'] = TRUE;
          continue;
        }
        $discounts[$service['id']] = $this->getInstanceById($service['id'])->discountItem($row);
      }
    }
    return $discounts;
  }

}
