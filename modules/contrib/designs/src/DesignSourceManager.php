<?php

namespace Drupal\designs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\designs\Annotation\DesignSource;

/**
 * Manages design source plugins.
 *
 * @see hook_design_source_info_alter()
 * @see \Drupal\designs\Annotation\DesignSource
 * @see \Drupal\designs\DesignSourceInterface
 * @see \Drupal\designs\DesignSourceBase
 * @see plugin_api
 */
class DesignSourceManager extends DefaultPluginManager implements DesignSourceManagerInterface {

  /**
   * Constructs a new DesignSourceManager.
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
    parent::__construct('Plugin/designs/source', $namespaces, $module_handler, DesignSourceInterface::class, DesignSource::class);

    $this->alterInfo('design_source_info');
    $this->setCacheBackend($cache_backend, 'design_source_plugins');
  }

}
