<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Provides the interface for a plugin manager of designs.
 */
interface DesignManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Gets library implementations for designs.
   *
   * @return array
   *   An associative array of the same format as returned by
   *   hook_library_info_build().
   *
   * @see hook_library_info_build()
   */
  public function getLibraryImplementations();

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignInterface
   *   The design definition.
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition|null
   *   The design definition.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[]
   *   The design definitions.
   */
  public function getDefinitions();

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[]
   *   The sorted design definitions.
   */
  public function getSortedDefinitions(array $definitions = NULL);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[][]
   *   The grouped design definitions.
   */
  public function getGroupedDefinitions(array $definitions = NULL);

  /**
   * Returns an array of design labels grouped by category.
   *
   * @return string[][]
   *   A nested array of labels suitable for #options.
   */
  public function getDesignOptions();

  /**
   * Create a design with a source.
   *
   * @param string $design_id
   *   The design plugin identifier.
   * @param array $design_configuration
   *   The design configuration.
   * @param string $source_id
   *   The design source plugin identifier.
   * @param array $source_configuration
   *   The source configuration.
   *
   * @return \Drupal\designs\DesignInterface|null
   *   The design.
   */
  public function createSourcedInstance($design_id, array $design_configuration, $source_id, array $source_configuration);

}
