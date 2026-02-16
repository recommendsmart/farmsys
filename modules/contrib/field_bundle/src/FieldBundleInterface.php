<?php

namespace Drupal\field_bundle;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Interface for a field bundle entity.
 */
interface FieldBundleInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Gets the field bundle creation timestamp.
   *
   * @return int
   *   Creation timestamp of the field bundle.
   */
  public function getCreatedTime();

  /**
   * Sets the field bundle creation timestamp.
   *
   * @param int $timestamp
   *   The field bundle creation timestamp.
   *
   * @return \Drupal\field_bundle\FieldBundleInterface
   *   The called field bundle entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get a brief string representation of this field bundle.
   *
   * The returned string has a maximum length of 255 characters.
   * Warning: This might expose undesired field content.
   *
   * This method is not implemented as __toString(). Instead it is this method
   * name, to guarantee compatibility with future changes of the Entity API.
   * Another reason is, that this method is kind of a last resort for generating
   * the field bundle label, and is not supposed to be used for other purposes
   * like serialization.
   *
   * Modules may implement hook_field_bundle_get_string_representation() to
   * change the final result, which will be returned by this method.
   *
   * @return string
   *   The string representation of this field bundle.
   */
  public function getStringRepresentation();

  /**
   * Applies a label pattern to update the label property.
   *
   * Developers may define a custom label pattern by setting a public
   * "label_pattern" as string property or field. If it is not set, then the
   * configured label pattern in the corresponding bundle config will be used.
   */
  public function applyLabelPattern();

}
