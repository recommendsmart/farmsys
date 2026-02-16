<?php

namespace Drupal\basket\Plugins\Params;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket Params plugin manager.
 *
 * @see \Drupal\basket\Plugins\Params\Annotation\BasketParams
 * @see \Drupal\basket\Plugins\Params\BasketParamsInterface
 * @see plugin_api
 */
class BasketParamsManager extends DefaultPluginManager {

  /**
   * Set Basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

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
      'Plugin/Basket/Params',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Params\BasketParamsInterface',
      'Drupal\basket\Plugins\Params\Annotation\BasketParams'
    );
    $this->alterInfo('basket_params_info');
    $this->setCacheBackend($cache_backend, 'basket_params_info_plugins');
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceByEntity($entity, $basketItem = NULL, $params = NULL, $display = NULL) {
    $defs = $this->getDefinitions();
    foreach ($defs as $plugin) {
      if (empty($plugin['node_type'])) {
        continue;
      }
      if (in_array($entity->bundle(), $plugin['node_type'])) {
        $plugin = new $plugin['class']($entity, $basketItem);
        if (!empty($params) && is_array($params)) {
          $plugin->setParams($params);
        }
        if ( !empty($display) ){
          $plugin->setDisplayMode($display);
        }
        return $plugin;
      }
    }
    return FALSE;
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
  public function getField($entity, $basketItem = NULL, $params = NULL, $display = NULL) {
    $form = $this->getInstanceByEntity($entity, $basketItem, $params, $display);
    if (!empty($form)) {
      return \Drupal::formBuilder()->getForm($form);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionParams($params, $nid, $isInline = FALSE) {
    $element = [];
    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if (!empty($entity)) {
      $class = $this->getInstanceByEntity($entity);
      if (!empty($class)) {
        $params['_entity'] = $entity;
        $class->getDefinitionParams($element, $params, $isInline);
      }
    }
    // Alter.
    \Drupal::moduleHandler()->alter('basket_params_definition', $element, $params, $isInline);
    // ---
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPopupButton($entity) {
    return $this->basket->getClass('Drupal\basket\BasketExtraFields')->BasketAddGenerate($entity, 'node_view');
  }

  /**
   * Validation of parameters when adding / updating an order item.
   */
  public function validParams(&$response, &$isValid, $post) {
    if (!empty($post['nid'])) {
      $post['entity'] = \Drupal::service('entity_type.manager')->getStorage('node')->load($post['nid']);
      if (!empty($post['entity'])) {
        $class = $this->getInstanceByEntity($post['entity']);
        if (!empty($class)) {
          $class->validParams($response, $isValid, $post);
        }
      }
    }
    // Alter.
    \Drupal::moduleHandler()->alter('basketValidParams', $response, $isValid, $post);
    // ---
  }

}
