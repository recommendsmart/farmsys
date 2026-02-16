<?php

namespace Drupal\designs;

use Drupal\Core\Render\Element;

/**
 * Provides handling of a design region.
 */
class DesignRegion {

  /**
   * Get the definition of the region.
   *
   * @var array
   */
  protected $definition;

  /**
   * The sources for the region.
   *
   * @var string[]
   */
  protected $sources;

  /**
   * DesignRegion constructor.
   *
   * @param array $definition
   *   The definition.
   * @param array $sources
   *   The sources for the region.
   */
  public function __construct(array $definition, array $sources) {
    $this->definition = $definition;
    $this->sources = $sources;
  }

  /**
   * Get the definition of the region.
   *
   * @return array
   *   The definition.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Get the sources used by the region.
   *
   * @return string[]
   *   The sources.
   */
  public function getSources() {
    return $this->sources;
  }

  /**
   * Build the region content.
   *
   * @param array $element
   *   The source render element.
   * @param array $custom
   *   The custom render sources.
   *
   * @return array
   *   The region render array.
   */
  public function build(array $element, array $custom) {
    $content = [];
    foreach ($this->sources as $source) {
      if (isset($element[$source])) {
        $content[] = $element[$source];
      }
      elseif (isset($custom[$source])) {
        $content[] = $custom[$source];
      }
    }

    // There are already enough sources, so leave as list.
    if (count($this->sources) > 1) {
      return $content;
    }

    // Source only contains one item, it may be a list so use that if possible.
    $children = $content[0] ?? [];
    if (count(Element::children($children)) > 0) {
      return $children;
    }
    return $content;
  }

}
