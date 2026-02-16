<?php

namespace Drupal\group_flex\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Group visibility item annotation object.
 *
 * @see \Drupal\group_flex\Plugin\GroupVisibilityManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupVisibility extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight = 0;

}
