<?php

namespace Drupal\designs;

/**
 * Interface for the design properties service.
 */
interface DesignPropertiesInterface {

  /**
   * Get the markup from the property value.
   *
   * @param mixed $value
   *   A property value.
   *
   * @return array
   *   The render array.
   */
  public function getMarkup($value);

}
