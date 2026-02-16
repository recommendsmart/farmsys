<?php

namespace Drupal\storage\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Storage entities.
 *
 * @ingroup storage
 */
interface StorageInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Storage name.
   *
   * @return string
   *   Name of the Storage.
   */
  public function getName();

  /**
   * Sets the Storage name.
   *
   * @param string $name
   *   The Storage name.
   *
   * @return \Drupal\storage\Entity\StorageInterface
   *   The called Storage entity.
   */
  public function setName($name);

  /**
   * Gets the Storage creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Storage.
   */
  public function getCreatedTime();

  /**
   * Sets the Storage creation timestamp.
   *
   * @param int $timestamp
   *   The Storage creation timestamp.
   *
   * @return \Drupal\storage\Entity\StorageInterface
   *   The called Storage entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Storage revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Storage revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\storage\Entity\StorageInterface
   *   The called Storage entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Storage revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Storage revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\storage\Entity\StorageInterface
   *   The called Storage entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Get a brief string representation of this Storage item.
   *
   * The returned string has a maximum length of 255 characters.
   * Warning: This might expose undesired field content.
   *
   * This method is not implemented as __toString(). Instead it is this method
   * name, to guarantee compatibility with future changes of the Entity API.
   * Another reason is, that this method is kind of a last resort for generating
   * the Storage name, and is not supposed to be used for other purposes
   * like serialization.
   *
   * Modules may implement hook_storage_get_string_representation() to
   * change the final result, which will be returned by this method.
   *
   * @return string
   *   The string representation of this Storage item.
   */
  public function getStringRepresentation();

  /**
   * Applies a pattern to update the name property.
   *
   * Developers may define a custom name pattern by setting a public
   * "name_pattern" as string property or field. If it is not set, then the
   * configured name pattern in the corresponding type config will be used.
   */
  public function applyNamePattern();

}
