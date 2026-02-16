<?php

namespace Drupal\entity_list\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Entity list filter item annotation object.
 *
 * @see \Drupal\entity_list\Plugin\EntityListDisplayManager
 * @see plugin_api
 *
 * @Annotation
 */
class EntityListFilter extends Plugin {

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
   * The content type ID's.
   *
   * @var array
   */
  public $content_type;

  /**
   * The entity type ID's.
   *
   * @var array
   */
  public $entity_type;

}
