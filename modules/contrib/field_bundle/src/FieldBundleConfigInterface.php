<?php

namespace Drupal\field_bundle;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Interface for a field bundle config.
 */
interface FieldBundleConfigInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface, EntityDescriptionInterface {

  /**
   * Get the default status for field bundles that use this configuration.
   *
   * @return bool
   *   The default status value.
   */
  public function getStatus();

  /**
   * Set the default status value.
   *
   * @param bool $status
   *   The default status value.
   */
  public function setStatus($status);

  /**
   * Get the label pattern to use for creating the label of the field bundle.
   *
   * @return string
   *   The label pattern.
   */
  public function getLabelPattern();

  /**
   * Set the label pattern to use for creating the label of the field bundle.
   *
   * @param string $pattern
   *   The pattern to set.
   */
  public function setLabelPattern($pattern);

}
