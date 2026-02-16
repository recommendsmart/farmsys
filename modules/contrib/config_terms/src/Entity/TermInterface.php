<?php

namespace Drupal\config_terms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Config term entities.
 */
interface TermInterface extends ConfigEntityInterface {

  /**
   * Gets the name of the term.
   *
   * @return string
   *   The name of the term.
   */
  public function getName();

  /**
   * Get the config terms vocab id this term belongs to.
   *
   * @return int
   *   The id of the vocab.
   */
  public function getVid();

  /**
   * Gets the weight of this term.
   *
   * @return int
   *   The weight of the term.
   */
  public function getWeight();

  /**
   * Gets the parents of this term.
   *
   * @return \Drupal\config_terms\Entity\TermInterface[]
   *   An array of parent terms for the term.
   */
  public function getParents();

  /**
   * Get the max steps to reach the top parent.
   *
   * @return int
   *   The depth of the term.
   */
  public function getDepth();

  /**
   * Gets the term's description.
   *
   * @return string
   *   The term description.
   */
  public function getDescription();

  /**
   * Sets the term's description.
   *
   * @param string $description
   *   The term's description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Sets the weight of this term.
   *
   * @param int $weight
   *   The term's weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Sets the depth of this term.
   *
   * @param int $depth
   *   The term's depth.
   *
   * @return $this
   */
  public function setDepth($depth);

  /**
   * Sets the depth of this term.
   *
   * @param \Drupal\config_terms\Entity\TermInterface[] $parents
   *   An array of parent terms for the term.
   *
   * @return $this
   */
  public function setParents(array $parents);

}
