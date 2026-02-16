<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

/**
 * A single existing entity.
 */
interface EntityInheritSingleExistingEntityInterface extends EntityInheritExistingEntityInterface {

  /**
   * Process this entity based on a changed parent.
   *
   * @param array $parent
   *   A parent information, as a queueable item. The format for this needs to
   *   look like:
   *      (
   *          [id] => node:22
   *          [original] => Array
   *              (
   *                  [node.body] => Array
   *                      (
   *                          [0] => Array
   *                              (
   *                                  [value] => Changed in parent,
   *                                             should propagate to child.
   *                                  [summary] =>
   *                                  [format] => full_html
   *                              )
   *
   *                      )
   *
   *                  [node.field_parents] => Array
   *                      (
   *                      )
   *
   *              )
   *
   *          [changed] => Array
   *              (
   *                  [node.body] => Array
   *                      (
   *                          [0] => Array
   *                              (
   *                                  [format] => full_html
   *                                  [summary] =>
   *                                  [value] => Whats up?
   *                              )
   *
   *                      )
   *
   *                  [node.field_parents] => Array
   *                      (
   *                      )
   *
   *              )
   *
   *      ).
   */
  public function process(array $parent);

  /**
   * Get a unique string which identifies this object.
   *
   * @return string
   *   A unique string.
   */
  public function toStorageId() : string;

}
