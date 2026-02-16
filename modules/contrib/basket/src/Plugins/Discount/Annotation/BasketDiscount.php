<?php

namespace Drupal\basket\Plugins\Discount\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Discount plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketDiscount extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

}
