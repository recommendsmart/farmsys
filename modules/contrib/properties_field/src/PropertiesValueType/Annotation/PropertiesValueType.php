<?php

namespace Drupal\properties_field\PropertiesValueType\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a properties value type annotation object.
 *
 * Plugin Namespace: Plugin\PropertiesValueType
 *
 * @Annotation
 */
class PropertiesValueType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
