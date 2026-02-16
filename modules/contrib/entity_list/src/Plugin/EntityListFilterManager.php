<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Entity list filter plugin manager.
 */
class EntityListFilterManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityListDisplayManager object.
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
    parent::__construct('Plugin/EntityListFilter', $namespaces, $module_handler, 'Drupal\entity_list\Plugin\EntityListFilterInterface', 'Drupal\entity_list\Annotation\EntityListFilter');

    $this->alterInfo('entity_list_entity_list_filter_info');
    $this->setCacheBackend($cache_backend, 'entity_list_entity_list_filter_form');
  }

}
