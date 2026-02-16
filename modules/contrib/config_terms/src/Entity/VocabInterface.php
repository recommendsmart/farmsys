<?php

namespace Drupal\config_terms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Config term vocab entities.
 */
interface VocabInterface extends ConfigEntityInterface {

  /**
   * Denotes that no term in the vocab has a parent.
   */
  const HIERARCHY_DISABLED = 0;

  /**
   * Denotes that one or more terms in the vocab has a single parent.
   */
  const HIERARCHY_SINGLE = 1;

  /**
   * Denotes that one or more terms in the vocab have multiple parents.
   */
  const HIERARCHY_MULTIPLE = 2;

  /**
   * Returns the vocab label.
   *
   * @return string
   *   The vocab label.
   */
  public function getLabel();

  /**
   * Returns the vocab hierarchy.
   *
   * @return int
   *   The vocab hierarchy.
   */
  public function getHierarchy();

  /**
   * Returns the vocab description.
   *
   * @return string
   *   The vocab description.
   */
  public function getDescription();

  /**
   * Returns the vocab weight.
   *
   * @return int
   *   The vocab weight.
   */
  public function getWeight();

  /**
   * Sets the vocab hierarchy.
   *
   * @param int $hierarchy
   *   The hierarchy type of vocab.
   *   Possible values:
   *    - self::HIERARCHY_DISABLED: No parents.
   *    - self::HIERARCHY_SINGLE: Single parent.
   *    - self::HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @return $this
   */
  public function setHierarchy($hierarchy);

}
