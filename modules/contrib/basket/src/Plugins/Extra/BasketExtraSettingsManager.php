<?php

namespace Drupal\basket\Plugins\Extra;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Basket Extra plugin manager.
 *
 * @see \Drupal\basket\Plugins\Extra\Annotation\BasketExtraSettings
 * @see \Drupal\basket\Plugins\Extra\BasketExtraSettingsInterface
 * @see plugin_api
 */
class BasketExtraSettingsManager extends DefaultPluginManager {

  /**
   * Constructs a ExtraManager object.
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
      'Plugin/Basket/Extra',
      $namespaces,
      $module_handler,
      'Drupal\basket\Plugins\Extra\BasketExtraSettingsInterface',
      'Drupal\basket\Plugins\Extra\Annotation\BasketExtraSettings'
    );
    $this->alterInfo('basket_extra_settings_info');
    $this->setCacheBackend($cache_backend, 'basket_extra_settings_info_plugins');
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
   * Gets extra field settings form.
   *
   * @param string $field_name
   *   The extra field machine name.
   *
   * @return array
   *   Array with form fields or empty array.
   */
  public function getSettingsForm($fieldName, $params = []) {
    $form = [];
    $service = $this->getInstanceById($fieldName);
    if(!empty($service)) {
      $form = $service->getSettingsForm($params);
    }
    return $form;
  }

  /**
   * Gets extra field settings summary.
   *
   * @return string
   */
  public function getSettingsSummary($fieldName, $settings, $params = []) {
    $sumary = NULL;
    $service = $this->getInstanceById($fieldName);
    if(!empty($service)) {
      $sumary = $service->getSettingsSummary($settings, $params);
    }
    return $sumary;
  }
}
