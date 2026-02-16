<?php

namespace Drupal\field_fallback\Plugin;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\field_fallback\Annotation\FieldFallbackConverter;

/**
 * The field fallback converter manager.
 */
class FieldFallbackConverterManager extends DefaultPluginManager implements FieldFallbackConverterManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldFallbackConverter', $namespaces, $module_handler, FieldFallbackConverterInterface::class, FieldFallbackConverter::class);

    $this->alterInfo('field_fallback_converter_info');
    $this->setCacheBackend($cache_backend, 'field_fallback_converters');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsBySourceAndTarget(string $source, string $target): array {
    $definitions = array_filter($this->getDefinitions(), static function (array $definition) use ($target, $source) {
      $source_check = in_array('*', $definition['source'], TRUE) || in_array($source, $definition['source'], TRUE);
      $target_check = in_array('*', $definition['target'], TRUE) || in_array($target, $definition['target'], TRUE);
      return $source_check && $target_check;
    });

    uasort($definitions, [SortArray::class, 'sortByWeightElement']);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableSourcesByTarget(string $target): array {
    $sources = [];
    foreach ($this->getDefinitions() as $definition) {
      if (is_array($definition) && (in_array('*', $definition['target'], TRUE) || in_array($target, $definition['target'], TRUE))) {
        foreach ($definition['source'] as $source) {
          if ($source !== '*') {
            $sources[] = $source;
          }
        }
      }
    }

    return array_unique($sources);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTargetsBySource(string $source): array {
    $targets = [];
    foreach ($this->getDefinitions() as $definition) {
      if (is_array($definition) && (in_array('*', $definition['source'], TRUE) || in_array($source, $definition['source'], TRUE))) {
        foreach ($definition['target'] as $target) {
          if ($target !== '*') {
            $targets[] = $target;
          }
        }
      }
    }

    return array_unique($targets);
  }

}
