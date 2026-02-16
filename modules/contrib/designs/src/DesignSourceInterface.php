<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for design source content.
 */
interface DesignSourceInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * Get the contexts used by the source.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface[]
   *   The contexts.
   */
  public function getFormContexts();

  /**
   * Get the contexts for the render element.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The contexts for the design.
   */
  public function getContexts(array &$element);

  /**
   * The indexes from a source render array element.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An associative array, indexed by the source render element index. This
   *   may include render element hash indexes.
   */
  public function getSources();

  /**
   * Get the element sources.
   *
   * @param array $sources
   *   The sources.
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The content indexed by source index.
   */
  public function getElementSources(array $sources, array $element);

  /**
   * Check the design source allows for custom content.
   *
   * @return bool
   *   The result.
   */
  public function usesCustomContent();

  /**
   * Check the design source allows for internal content configuration.
   *
   * @return bool
   *   The result.
   */
  public function usesRegionsForm();

  /**
   * Get the default sources used by the default region.
   *
   * This is used for the initial setup of content within a new design.
   *
   * @return string[]
   *   The keys of the sources to add to the default region.
   */
  public function getDefaultSources();

}
