<?php

namespace Drupal\recipe\Plugin\migrate\source\recipe71;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 recipe source from database.
 *
 * This source plugin migrates all fields from the recipe table except for the
 * yield and yield_unit fields.  They are handled by the Recipe72 source class.
 * This class handles migrations of Recipe 7.x-1.x data.  If the Recipe module
 * schema version is 7207 or higher, then the query returns empty results and is
 * skipped.
 *
 * @MigrateSource(
 *   id = "recipe71_recipe",
 *   source_module = "recipe"
 * )
 */
class Recipe71 extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('recipe', 'r')
      ->fields('r')
      ->orderBy('r.nid');
    $query->join('node', 'n','r.nid = n.nid');
    $query->fields('n', ['tnid', 'language']);
    $this->handleTranslations($query);
    // If the schema version is greater than 7207, then all of the fields that
    // are migrated by this plugin are handled by the Field API migration.
    if ($this->getModuleSchemaVersion('recipe') > 7207) {
      $query->alwaysFalse();
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Select the ingredient reference data and add it to the row.
    $query = $this->select('recipe_node_ingredient', 'i')
      ->fields('i')
      ->condition('nid', $row->getSourceProperty('nid'))
      ->orderBy('weight', 'ASC');
    $results = $query->execute();
    $ingredients = [];
    foreach ($results as $result) {
      $ingredients[] = $result;
    }
    $row->setSourceProperty('ingredients', $ingredients);

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
      'source' => $this->t('Recipe source'),
      'description' => $this->t('Recipe description'),
      'instructions' => $this->t('Recipe instructions'),
      'notes' => $this->t('Recipe notes'),
      'preptime' => $this->t('Recipe preparation time'),
      'cooktime' => $this->t('Recipe cook time'),
      'ingredients' => $this->t('Recipe ingredients, measures, and notes'),
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
