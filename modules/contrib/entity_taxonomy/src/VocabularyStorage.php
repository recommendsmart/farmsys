<?php

namespace Drupal\entity_taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a storage handler class for entity_taxonomy vocabularies.
 */
class VocabularyStorage extends ConfigEntityStorage implements VocabularyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('entity_taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getToplevelTids($vids) {
    $tids = \Drupal::entityQuery('entity_taxonomy_term')
      ->condition('vid', $vids, 'IN')
      ->condition('parent.target_id', 0)
      ->execute();

    return array_values($tids);
  }

}
