<?php

namespace Drupal\recipe\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 recipe source from database.
 *
 * This source plugin migrates the yield and yield_unit fields from the recipe
 * table.  Those two fields were not converted to the Field API by the 7.x-2.x
 * branch.  In other words, this source plugin was split off from the Recipe71
 * source class to support 7.x-2.x migrations.
 *
 * @MigrateSource(
 *   id = "recipe72_recipe",
 *   source_module = "recipe"
 * )
 */
class Recipe72 extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('recipe', 'r')
      ->fields('r', ['nid', 'yield', 'yield_unit'])
      ->orderBy('r.nid');
    $query->join('node', 'n','r.nid = n.nid');
    $query->fields('n', ['tnid', 'language']);
    $this->handleTranslations($query);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Make sure we always have a translation set.
    if ($row->getSourceProperty('tnid') == 0) {
      $row->setSourceProperty('tnid', $row->getSourceProperty('nid'));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Recipe node ID'),
      'tnid' => $this->t('The translation node ID'),
      'yield' => $this->t('Recipe yield amount'),
      'yield_unit' => $this->t('Units of the recipe yield'),
      'language' => $this->t('Node language'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['nid' => ['type' => 'integer', 'alias' => 'r']];
  }

  /**
   * Adapt our query for translations.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The generated query.
   */
  protected function handleTranslations(SelectInterface $query) {
    // Check whether or not we want translations.
    if (empty($this->configuration['translations'])) {
      // No translations: Yield untranslated nodes, or default translations.
      $query->where('n.tnid = 0 OR n.tnid = n.nid');
    }
    else {
      // Translations: Yield only non-default translations.
      $query->where('n.tnid <> 0 AND n.tnid <> n.nid');
    }
  }

}
