<?php

namespace Drupal\entity_extra_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define extra field type plugin annotation.
 *
 * @Annotation
 */
class ExtraFieldType extends Plugin {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

}
