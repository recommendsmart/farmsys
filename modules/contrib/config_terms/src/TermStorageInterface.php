<?php

namespace Drupal\config_terms;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Provides an interface for configuration entity storage.
 */
interface TermStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Finds all children of a term ID.
   *
   * @param string $tid
   *   Term ID to retrieve parents for.
   * @param string $vid
   *   An optional vocab ID to restrict the child search.
   *
   * @return \Drupal\config_terms\Entity\TermInterface[]
   *   An array of term objects that are the children of the term $tid.
   */
  public function loadChildren($tid, $vid = NULL);

  /**
   * Finds all parents of a given term ID.
   *
   * @param string $tid
   *   Term ID to retrieve parents for.
   *
   * @return \Drupal\config_terms\Entity\TermInterface[]
   *   An array of term objects which are the parents of the term $tid.
   */
  public function loadParents($tid);

  /**
   * Finds all terms in a given vocab ID.
   *
   * @param string $vid
   *   Vocab ID to retrieve terms for.
   * @param string $parent
   *   The term ID under which to generate the tree. If 0, generate the tree
   *   for the entire vocab.
   * @param int $max_depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   *
   * @return \Drupal\config_terms\Entity\TermInterface[]
   *   An array of term objects that are in vocab $vid.
   */
  public function loadTree($vid, $parent = '0', $max_depth = NULL);

  /**
   * Reset the weights for a given vocab ID.
   *
   * @param string $vid
   *   Vocab ID to retrieve terms for.
   */
  public function resetWeights($vid);

  /**
   * Get the tree for a given vocab in array format, ready to use as '#options'.
   *
   * @param string $vid
   *   Vocab ID to retrieve term options for.
   *
   * @return array
   *   Array of associated terms with the key being the TID and the value being
   *   the term label, using '-' to denote depth.
   */
  public function getTermOptions($vid);

}
