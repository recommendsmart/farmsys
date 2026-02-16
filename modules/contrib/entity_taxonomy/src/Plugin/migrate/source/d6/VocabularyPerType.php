<?php

namespace Drupal\entity_taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;

/**
 * Gets all the vocabularies based on the node types that have entity_taxonomy enabled.
 *
 * @MigrateSource(
 *   id = "d6_entity_taxonomy_vocabulary_per_type",
 *   source_module = "entity_taxonomy"
 * )
 */
class VocabularyPerType extends Vocabulary {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->join('vocabulary_node_types', 'nt', 'v.vid = nt.vid');
    $query->fields('nt', ['type']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the i18n entity_taxonomy translation setting for this vocabulary.
    // 0 - No multilingual options
    // 1 - Localizable terms. Run through the localization system.
    // 2 - Predefined language for a vocabulary and its terms.
    // 3 - Per-language terms, translatable (referencing terms with different
    // languages) but not localizable.
    $i18nentity_taxonomy_vocab = $this->variableGet('i18nentity_taxonomy_vocabulary', []);
    $vid = $row->getSourceProperty('vid');
    $i18nentity_taxonomy_vocabulary = FALSE;
    if (array_key_exists($vid, $i18nentity_taxonomy_vocab)) {
      $i18nentity_taxonomy_vocabulary = $i18nentity_taxonomy_vocab[$vid];
    }
    $row->setSourceProperty('i18nentity_taxonomy_vocabulary', $i18nentity_taxonomy_vocabulary);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'nt';
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
