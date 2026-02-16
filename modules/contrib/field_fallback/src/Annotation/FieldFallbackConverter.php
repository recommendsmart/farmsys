<?php

namespace Drupal\field_fallback\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldFallbackConverter item annotation object.
 *
 * @see \Drupal\field_fallback\FieldFallbackconverterManager
 *
 * @Annotation
 */
class FieldFallbackConverter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The source fields for which the plugin calculates the fallback value.
   *
   * @var string[]
   */
  public $source = [];

  /**
   * The target fields for which the plugin calculates the fallback value.
   *
   * @var string[]
   */
  public $target = [];

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight;

}
