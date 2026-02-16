<?php

namespace Drupal\friggeri_cv;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a profile entity type.
 */
interface ProfileInterface extends ContentEntityInterface {

  /**
   * Gets the profile name.
   *
   * @return string
   *   The profile name
   */
  public function getName();

  /**
   * Sets the profile name.
   *
   * @param string $name
   *   The profile name.
   *
   * @return self
   *   This profile.
   */
  public function setName(string $name);

  /**
   * Gets the profile title.
   *
   * @return string
   *   The profile title.
   */
  public function getTitle();

  /**
   * Sets the profile title.
   *
   * @param string $title
   *   The profile title.
   *
   * @return self
   *   This profile.
   */
  public function setTitle(string $title);

  /**
   * Gets the default picture.
   *
   * @return array
   *   The picture build array markup.
   */
  public function getDefaultPicture();

  /**
   * Build array of the picture.
   *
   * @return array
   *   The image_style build array.
   */
  public function getPicture();

  /**
   * Gets the sections.
   *
   * @return \Drupal\friggeri_cv\Entity\ProfileSection[]
   *   The profile sections array.
   */
  public function getSections();

}
