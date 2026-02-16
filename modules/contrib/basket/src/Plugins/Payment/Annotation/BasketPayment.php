<?php

namespace Drupal\basket\Plugins\Payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Payment plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketPayment extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

}
