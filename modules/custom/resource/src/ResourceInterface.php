<?php

/**
 * @file
 * Contains \Drupal\resource\ResourceInterface.
 */

namespace Drupal\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Resource entities.
 *
 * @ingroup resource
 */
interface ResourceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the resource name.
   *
   * @return string
   *   The resource name.
   */
  public function getName();

  /**
   * Gets the resource type.
   *
   * @return string
   *   The resource type.
   */
  public function getType();

  /**
   * Gets the resource type name.
   *
   * @return string
   *   The resource type name.
   */
  public function getTypeName();

  /**
   * Gets the resource creation timestamp.
   *
   * @return int
   *   Creation timestamp of the resource.
   */
  public function getCreatedTime();
}
