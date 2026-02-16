<?php

namespace Drupal\recipe\Plugin\migrate\source\recipe61;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 recipe source from database.
 *
 * @MigrateSource(
 *   id = "recipe61_recipe",
 *   source_module = "recipe"
 * )
 */
class Recipe61 extends DrupalSqlBase {

  /**
   * Unit names that changed when the Drupal 7 version was introduced.
   *
   * @var string[]
   */
  const CHANGED_UNITS = [
    'pint' => 'us liquid pint',
    'quart' => 'us liquid quart',
    'gallon' => 'us gallon',
    'tablespoon (metric)' => 'tablespoon',
    'metric tablespoon' => 'tablespoon',
    'teaspoon (metric)' => 'teaspoon',
    'metric teaspoon' => 'teaspoon',
    'millilitre' => 'milliliter',
    'centilitre' => 'centiliter',
    'decilitre' => 'decilitre',
    'litre' => 'liter',
  ];

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
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Select the ingredient reference data and add it to the row.
    $query = $this->select('recipe_node_ingredient', 'i');
    $query->leftJoin('recipe_unit', 'ru', 'i.unit_id = ru.id');
    $query->fields('i', ['quantity', 'ingredient_id', 'weight', 'note'])
      ->fields('ru', ['name'])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->orderBy('weight', 'ASC');
    $results = $query->execute();
    $ingredients = [];
    foreach ($results as $result) {
      // Check for updated unit names.
      $result['name'] = strtolower($result['name']);
      $result['unit_key'] = isset(self::CHANGED_UNITS[$result['name']]) ? self::CHANGED_UNITS[$result['name']] : $result['name'];
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
      'yield' => $this->t('Recipe yield amount'),
      'yield_unit' => $this->t('Units of the recipe yield'),
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
