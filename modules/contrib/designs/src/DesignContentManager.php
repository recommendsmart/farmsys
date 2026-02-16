<?php

namespace Drupal\designs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\designs\Annotation\DesignContent;

/**
 * Manages design content plugins.
 *
 * @see hook_design_content_info_alter()
 * @see \Drupal\designs\Annotation\DesignContent
 * @see \Drupal\designs\DesignContentInterface
 * @see \Drupal\designs\DesignContentBase
 * @see plugin_api
 */
class DesignContentManager extends DefaultPluginManager implements DesignContentManagerInterface {

  /**
   * Constructs a new DesignContentManager.
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
    parent::__construct('Plugin/designs/content', $namespaces, $module_handler, DesignContentInterface::class, DesignContent::class);

    $this->alterInfo('design_content_info');
    $this->setCacheBackend($cache_backend, 'design_content_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceDefinitions($target, $source) {
    $definitions = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      if (empty($definition[$target])) {
        continue;
      }
      if (!empty($definition['sources']) && !in_array($source, $definition['sources'])) {
        continue;
      }
      $definitions[$id] = $definition;
    }
    return $definitions;
  }

}
