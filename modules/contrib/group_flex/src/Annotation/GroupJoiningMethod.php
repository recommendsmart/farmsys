<?php

namespace Drupal\group_flex\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Group joining method item annotation object.
 *
 * @see \Drupal\group_flex\Plugin\GroupJoiningMethodManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupJoiningMethod extends Plugin {

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

  /**
   * The visibility options where plugin should be available.
   *
   * Defaults to false which reflects all.
   *
   * @var array|false
   */
  public $visibilityOptions = FALSE;

}
