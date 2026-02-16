<?php

namespace Drupal\config_terms;

use Drupal\config_terms\Entity\TermInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a storage handler class for config_terms vocabularies.
 */
class TermStorage extends ConfigEntityStorage implements TermStorageInterface {

  /**
   * Array of loaded parents keyed by child term ID.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * Array of all loaded term ancestry keyed by ancestor term ID.
   *
   * @var array
   */
  protected $parentsAll = [];

  /**
   * Array of child terms keyed by parent term ID.
   *
   * @var array
   */
  protected $children = [];

  /**
   * Array of term parents keyed by vocab ID and child term ID.
   *
   * @var array
   */
  protected $treeParents = [];

  /**
   * Array of term ancestors keyed by vocab ID and parent term ID.
   *
   * @var array
   */
  protected $treeChildren = [];

  /**
   * Array of terms in a tree keyed by vocab ID and term ID.
   *
   * @var array
   */
  protected $treeTerms = [];

  /**
   * Array of loaded trees keyed by a cache id matching tree arguments.
   *
   * @var array
   */
  protected $trees = [];

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    if (!isset($this->children[$tid])) {
      $children = [];

      $query = $this->getQuery();
      if ($vid) {
        $query->condition('vid', $vid);
      }

      $tids = $query->execute();

      /**
       * @var \Drupal\config_terms\Entity\TermInterface $term
       */
      foreach ($this->loadMultiple($tids) as $term) {
        $term_parents = (array) $term->getParents();
        if (in_array($tid, $term_parents)) {
          $children[$term->id()] = $term;
        }
      }
      // @see https://www.drupal.org/node/2862699
      // Multiple sorts don't work for config entities, so implement it here.
      uasort($children, [$this, 'weightNameCmp']);
      $this->children[$tid] = $children;
    }

    return $this->children[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid, $vid = NULL) {
    if (!isset($this->parents[$tid])) {

      /**
       * @var \Drupal\config_terms\Entity\TermInterface $term
       */
      $term = $this->load($tid);

      $parents = [];
      foreach ((array) $term->getParents() as $parent_tid) {
        $parents[$tid][$parent_tid] = $this->load($parent_tid);
      }

      $this->parents[$tid] = $parents;
    }

    return $this->parents[$tid];
  }

  /**
   * Comparison function for weight and label.
   *
   * @param \Drupal\config_terms\Entity\TermInterface $a
   *   First term.
   * @param \Drupal\config_terms\Entity\TermInterface $b
   *   Second term.
   *
   * @return int
   *   0 if the two terms sort the same, -1 if $a is less than $b, 1 otherwise.
   */
  protected function weightNameCmp(TermInterface $a, TermInterface $b) {
    if ($a->getWeight() == $b->getWeight()) {
      if ($a->getName() == $b->getName()) {
        return 0;
      }
      return $a->getName() < $b->getName() ? -1 : 1;
    }
    return $a->getWeight() < $b->getWeight() ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($vid, $parent = '0', $max_depth = NULL) {
    $cache_key = implode(':', func_get_args());
    if (!isset($this->trees[$cache_key])) {
      if (!isset($this->treeChildren[$vid])) {
        $query = $this->getQuery()
          ->condition('vid', $vid);
        $tids = $query->execute();

        $this->treeChildren[$vid] = [];
        $this->treeParents[$vid] = [];
        $terms = $this->loadMultiple($tids);
        // @see https://www.drupal.org/node/2862699
        // Multiple sorts don't work for config entities, so implement it here.
        usort($terms, [$this, 'weightNameCmp']);

        /**
         * @var \Drupal\config_terms\Entity\TermInterface $term
         */
        foreach ($terms as $term) {
          $term_parents = (array) $term->getParents();
          foreach ($term_parents as $term_parent) {
            $this->treeChildren[$vid][$term_parent][] = $term->id();
            $this->treeParents[$vid][$term->id()][] = $term_parent;
          }
          $this->treeTerms[$vid][$term->id()] = $term;
        }
      }

      $max_depth = (!isset($max_depth)) ? count($this->treeChildren[$vid]) : $max_depth;

      $tree = [];

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = [];
      $process_parents[] = $parent;

      // Loops over the parent terms and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      while (count($process_parents)) {
        $parent = array_pop($process_parents);
        // The number of parents determines the current depth.
        $depth = count($process_parents);
        if ($max_depth > $depth && !empty($this->treeChildren[$vid][$parent])) {
          $has_children = FALSE;
          $child = current($this->treeChildren[$vid][$parent]);
          do {
            if (empty($child)) {
              break;
            }
            $term = $this->treeTerms[$vid][$child];
            if (isset($this->treeParents[$vid][$term->id()])) {
              // Clone the term so that the depth attribute remains correct
              // in the event of multiple parents.
              $term = clone $term;
            }
            $term->setDepth($depth);
            unset($term->parent);
            $tid = $term->id();
            $term->setParents($this->treeParents[$vid][$tid]);
            $tree[] = $term;
            if (!empty($this->treeChildren[$vid][$tid])) {
              $has_children = TRUE;

              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current term as parent for the next iteration.
              $process_parents[] = $tid;

              // Reset pointers for child lists because we step in there more
              // often with multi parents.
              reset($this->treeChildren[$vid][$tid]);
              // Move pointer so that we get the correct term the next time.
              next($this->treeChildren[$vid][$parent]);
              break;
            }
          } while ($child = next($this->treeChildren[$vid][$parent]));

          if (!$has_children) {
            // We processed all terms in this hierarchy-level, reset pointer
            // so that this function works the next time it gets called.
            reset($this->treeChildren[$vid][$parent]);
          }
        }
      }
      $this->trees[$cache_key] = $tree;
    }
    return $this->trees[$cache_key];
  }

  /**
   * {@inheritdoc}
   */
  public function resetWeights($vid) {
    $tree = $this->loadTree($vid);
    foreach ($tree as $term) {
      $term->setWeight(0);
      $term->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTermOptions($vid) {
    $options = [];
    foreach ($this->loadTree($vid) as $item) {
      $options[$item->id()] = str_repeat('-', $item->getDepth()) . $item->getName();
    }
    return $options;
  }

}
