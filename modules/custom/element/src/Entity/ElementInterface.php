<?php

namespace Drupal\element\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining element entities.
 *
 * @ingroup element
 */
interface ElementInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the element title.
   *
   * @return string
   *   Title of the element.
   */
  public function getTitle();

  /**
   * Sets the element title.
   *
   * @param string $title
   *   The element title.
   *
   * @return \Drupal\element\Entity\ElementInterface
   *   The called element entity.
   */
  public function setTitle($title);

  /**
   * Gets the element creation timestamp.
   *
   * @return int
   *   Creation timestamp of the element.
   */
  public function getCreatedTime();

  /**
   * Sets the element creation timestamp.
   *
   * @param int $timestamp
   *   The element creation timestamp.
   *
   * @return \Drupal\element\Entity\ElementInterface
   *   The called element entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the element published status indicator.
   *
   * Unpublished element are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the element is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a element.
   *
   * @param bool $published
   *   TRUE to set this element to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\element\Entity\ElementInterface
   *   The called element entity.
   */
  public function setPublished($published);

  /**
   * Gets the element revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the element revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\element\Entity\ElementInterface
   *   The called element entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the element revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the element revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\element\Entity\ElementInterface
   *   The called element entity.
   */
  public function setRevisionUserId($uid);

}
