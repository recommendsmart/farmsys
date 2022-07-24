<?php

namespace Drupal\basket\Plugins\DeliverySettings\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Basket DeliverySettings plugins.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class BasketDeliverySettings extends Plugin {

  /**
   * Set name.
   *
   * @var string
   */
  public $name;

  /**
   * Set parent_field.
   *
   * @var string
   */
  public $parent_field;

}
