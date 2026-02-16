<?php

namespace Drupal\designs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a design source annotation object.
 *
 * Design sources are used to define the sources of content.
 *
 * Plugin Namespace: Plugin\designs\Source
 *
 * @see \Drupal\designs\DesignSourceInterface
 * @see \Drupal\designs\DesignSourceBase
 * @see \Drupal\designs\DesignSourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class DesignSource extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the design source.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Allows the use of custom content.
   *
   * @var bool
   */
  public $usesCustomContent = TRUE;

  /**
   * Uses the plugin form for regions.
   *
   * @var bool
   */
  public $usesRegionsForm = TRUE;

  /**
   * The default sources for the design source.
   *
   * @var array
   */
  public $defaultSources = [];

}
