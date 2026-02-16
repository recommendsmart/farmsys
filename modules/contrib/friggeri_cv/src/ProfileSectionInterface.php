<?php

namespace Drupal\friggeri_cv;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a profile section entity type.
 */
interface ProfileSectionInterface extends ContentEntityInterface {

  /**
   * Gets the profile section title.
   *
   * @return string
   *   Title of the profile section.
   */
  public function getTitle();

  /**
   * Sets the profile section title.
   *
   * @param string $title
   *   The profile section title.
   *
   * @return \Drupal\profile_section\ProfileSectionInterface
   *   The called profile section entity.
   */
  public function setTitle($title);

  /**
   * Sets the profile section entity_boxes.
   *
   * @param array $entity_boxes
   *   The profile section entity_boxes.
   *
   * @return \Drupal\profile_section\ProfileSectionInterface
   *   The called profile section entity.
   */
  public function setEntityBox(array $entity_boxes);

}
