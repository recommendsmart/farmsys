<?php

namespace Drupal\basket\Plugins\Stock\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Stock plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketStockBulk extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

  /**
   * Set weight.
   *
   * @var array
   */
  public $weight;

  /**
   * Set color.
   *
   * @var string
   */
  public $color;

}
