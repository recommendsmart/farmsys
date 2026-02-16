<?php

namespace Drupal\designs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a design setting annotation object.
 *
 * Design settings are used to define methods for providing content for HTML
 * attributes or template variables.
 *
 * Plugin Namespace: Plugin\designs\Setting
 *
 * @see \Drupal\designs\DesignSettingInterface
 * @see \Drupal\designs\DesignSettingBase
 * @see \Drupal\designs\DesignSettingManager
 * @see plugin_api
 *
 * @Annotation
 */
class DesignSetting extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the design setting.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An optional description for design settings form input.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
