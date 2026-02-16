<?php

namespace Drupal\config_terms;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Provides an interface for configuration entity storage.
 */
interface VocabStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Gets top-level term IDs of vocab.
   *
   * @param array $vids
   *   Array of vocab IDs.
   *
   * @return array
   *   Array of top-level term IDs.
   */
  public function getToplevelTids(array $vids);

  /**
   * Get the list of all config term vocabs.
   *
   * @return array
   *   Array of vocabs, in format [vid => label].
   */
  public function getVocabsList();

}
