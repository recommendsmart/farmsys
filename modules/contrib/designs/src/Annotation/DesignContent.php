<?php

namespace Drupal\designs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Design content object.
 *
 * Design contents are used to define methods of rendering custom content.
 *
 * Plugin namespace: Plugin\designs\Content
 *
 * @see \Drupal\designs\DesignContentInterface
 * @see \Drupal\designs\DesignContent
 * @see \Drupal\designs\DesignContentManager
 * @see plugin_api
 *
 * @Annotation
 */
class DesignContent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the design content.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Allowable for settings.
   *
   * @var bool
   */
  public $settings = TRUE;

  /**
   * Allowable for custom content.
   *
   * @var bool
   */
  public $content = TRUE;

  /**
   * Restricted to source plugins when not empty.
   *
   * @var string[]
   */
  public $sources = [];

}
