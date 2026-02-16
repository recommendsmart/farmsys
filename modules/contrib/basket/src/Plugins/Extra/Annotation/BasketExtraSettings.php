<?php

namespace Drupal\basket\Plugins\Extra\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Extra plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketExtraSettings extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

}
