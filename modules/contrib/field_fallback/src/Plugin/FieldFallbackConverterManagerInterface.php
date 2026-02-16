<?php

namespace Drupal\field_fallback\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for the field_fallback converter manager.
 */
interface FieldFallbackConverterManagerInterface extends PluginManagerInterface {

  /**
   * Get field fallback converter definitions by the source and target type.
   *
   * @param string $source
   *   The source type.
   * @param string $target
   *   The target type.
   *
   * @return array
   *   An array of definitions filtered by the given source and target.
   */
  public function getDefinitionsBySourceAndTarget(string $source, string $target): array;

  /**
   * Get all available sources by target.
   *
   * The available sources are calculated based on the converters.
   *
   * @param string $target
   *   The target type.
   *
   * @return array
   *   A list of available sources.
   */
  public function getAvailableSourcesByTarget(string $target): array;

  /**
   * Get all available targets by source.
   *
   * The available targets are calculated based on the converters.
   *
   * @param string $source
   *   The source type.
   *
   * @return array
   *   A list of available target.
   */
  public function getAvailableTargetsBySource(string $source): array;

}
