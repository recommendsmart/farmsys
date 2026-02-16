<?php

declare(strict_types = 1);

namespace Drupal\entity_version\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for the entity_version field item.
 */
interface EntityVersionItemInterface extends FieldItemInterface {

  /**
   * Increase the given version number category.
   *
   * @param string $category
   *   The version number category.
   */
  public function increase(string $category);

  /**
   * Decrease the given version number category.
   *
   * @param string $category
   *   The version number category.
   */
  public function decrease(string $category);

  /**
   * Reset the given version number category to zero.
   *
   * @param string $category
   *   The version number category.
   */
  public function reset(string $category);

}
