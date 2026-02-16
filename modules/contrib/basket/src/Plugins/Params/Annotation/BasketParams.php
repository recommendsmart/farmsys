<?php

namespace Drupal\basket\Plugins\Params\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket Params plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketParams extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

  /**
   * Set node_type.
   *
   * @var array
   */
  public $node_type;

}
