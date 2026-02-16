<?php

namespace Drupal\basket\Plugins\Delivery\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Delivery plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketDelivery extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

}
