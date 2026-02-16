<?php

namespace Drupal\friggeri_cv;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a profile footer item entity type.
 */
interface ProfileFooterItemInterface extends ContentEntityInterface {

  /**
   * Gets the profile footer item text.
   *
   * @return string
   *   Text of the profile footer item.
   */
  public function getText();

  /**
   * Sets the profile footer item text.
   *
   * @param string $text
   *   The profile footer item text.
   *
   * @return self
   *   The called profile footer item entity.
   */
  public function setText($text);

}
