<?php

namespace Drupal\basket\Plugins\Popup\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Popup plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketPopupSystem extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

}
