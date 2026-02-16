<?php

namespace Drupal\designs\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\designs\DesignDefault;
use Drupal\designs\DesignDefinition;

/**
 * Defines a Design annotation object.
 *
 * Designs are used to define a list of regions and then output render arrays
 * in each of the regions, usually using a template.
 *
 * Plugin namespace: Plugin\designs\Design
 *
 * @see \Drupal\designs\DesignInterface
 * @see \Drupal\designs\DesignDefault
 * @see \Drupal\designs\DesignManager
 * @see plugin_api
 *
 * @Annotation
 */
class Design extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An optional description for advanced designs.
   *
   * Sometimes designs are so complex that the name is insufficient to describe
   * a design such that a visually impaired administrator could design a page
   * for a non-visually impaired audience. If specified, it will provide a
   * description that is used for accessibility purposes.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The human-readable category.
   *
   * @var string
   *
   * @see \Drupal\Component\Plugin\CategorizingPluginManagerInterface
   *
   * @ingroup plugin_translatable
   */
  public $category = 'Design';

  /**
   * The template file to render this design (relative to the 'path' given).
   *
   * @var string
   *
   * @see hook_theme()
   */
  public $template;

  /**
   * Path (relative to the module or theme) to resources like icon or template.
   *
   * @var string
   */
  public $path;

  /**
   * The asset libraries.
   *
   * @var string[]|array[]
   */
  public $libraries = [];

  /**
   * An associative array of settings in this design.
   *
   * The design settings. The keys of the array are the machine names of the
   * settings, and the values are an associative array with the following keys:
   * - type: (string) The plugin identifier for the setting.
   * - label: (string) The human-readable name of the setting.
   * - value: (string) The value for the setting.
   *
   * Any remaining keys may have special meaning for the given design plugin,
   * but are undefined here.
   *
   * @var array
   */
  public $settings = [];

  /**
   * An associative array of regions in this design.
   *
   * The key of the array is the machine name of the region, and the value is
   * an associative array with the following keys:
   * - label: (string) The human-readable name of the region.
   *
   * Any remaining keys may have special meaning for the given design plugin,
   * but are undefined here.
   *
   * @var array
   */
  public $regions = [];

  /**
   * An associative array of custom content in this design.
   *
   * The design custom content. The keys of the array are the machine names of
   * the content, and the values are an associative array with the following
   * keys:
   * - type: (string) The type for the custom content. One of 'default', 'twig',
   *   'token'.
   * - label: (string) The human-readable name of the custom content.
   * - value: (string) The value for the custom content.
   *
   * Any remaining keys may have special meaning for the given design plugin,
   * but are undefined here.
   *
   * @var array
   */
  public $custom = [];

  /**
   * The default region.
   *
   * @var string
   */
  public $defaultRegion;

  /**
   * The design plugin class.
   *
   * This default value is used for plugins defined in designs.yml that do not
   * specify a class themselves.
   *
   * @var string
   */
  public $class = DesignDefault::class;

  /**
   * {@inheritdoc}
   */
  public function get() {
    return new DesignDefinition($this->definition);
  }

}
