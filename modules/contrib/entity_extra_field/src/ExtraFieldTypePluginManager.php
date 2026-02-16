<?php

namespace Drupal\entity_extra_field;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\entity_extra_field\Annotation\ExtraFieldType;

/**
 * Define the extra field type plugin manage.
 */
class ExtraFieldTypePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/ExtraFieldType',
      $namespaces,
      $module_handler,
      ExtraFieldTypePluginInterface::class,
      ExtraFieldType::class
    );

    $this->alterInfo('extra_field_type_info');
    $this->setCacheBackend($cache_backend, 'extra_field_type');
  }

}
