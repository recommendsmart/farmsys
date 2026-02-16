<?php

namespace Drupal\designs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\designs\Annotation\DesignSetting;

/**
 * Manages design setting plugins.
 *
 * @see hook_design_setting_info_alter()
 * @see \Drupal\designs\Annotation\DesignSetting
 * @see \Drupal\designs\DesignSettingInterface
 * @see \Drupal\designs\DesignSettingBase
 * @see plugin_api
 */
class DesignSettingManager extends DefaultPluginManager implements DesignSettingManagerInterface {

  /**
   * Constructs a new DesignSettingManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/designs/setting', $namespaces, $module_handler, DesignSettingInterface::class, DesignSetting::class);

    $this->alterInfo('design_setting_info');
    $this->setCacheBackend($cache_backend, 'design_setting_plugins');
  }

}
