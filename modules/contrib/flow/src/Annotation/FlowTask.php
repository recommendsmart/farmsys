<?php

namespace Drupal\flow\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for Flow task plugins.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FlowTask extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the task plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The entity type ID of a subject this task acts upon.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The entity bundle of a subject this task acts upon.
   *
   * @var string
   */
  public $bundle;

  /**
   * A list of supported task modes.
   *
   * When this list is empty, then all available task modes are supported.
   *
   * @var string[]
   *
   * Example for supporting only save and delete operations:
   * @code
   * task_modes = {"save", "delete"}
   * @endcode
   */
  public array $task_modes = [];

  /**
   * A list of supported types of Flow targets, where this task is available.
   *
   * When this list is empty, then every entity type as target is supported.
   *
   * @var string[][]
   *
   * Example for tasks that are only available when Flow operates on a node
   * of any type:
   * @code
   * targets = {"node" = {}}
   * @endcode
   *
   * Example for only being available when Flow operates on an article and basic
   * page:
   * @code
   * targets = {"node" = {"article", "page"}}
   * @endcode
   */
  public array $targets = [];

}
