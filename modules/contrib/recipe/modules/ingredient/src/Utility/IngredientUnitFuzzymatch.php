<?php

namespace Drupal\ingredient\Utility;

use Drupal\Core\Config\ConfigFactory;

/**
 * Provides the ingredient.fuzzymatch service.
 */
class IngredientUnitFuzzymatch {

  /**
   * The ingredient.units configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $ingredientUnitConfig;

  /**
   * Constructs a new IngredientUnitUtility object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->ingredientUnitConfig = $config_factory->get('ingredient.units');
  }

  /**
   * Returns a best-guess matched unit key for a unit of measure.
   *
   * @param string $subject
   *   The unit of measure for which the function will search.
   *
   * @return string|false
   *   The unit's key from configuration or FALSE if there was no match.
   */
  public function getUnitFuzzymatch($subject) {
    $unit_sets = $this->ingredientUnitConfig->get('unit_sets');
    // Merge the unit sets into a single array for simplicity.
    $units = [];
    foreach ($unit_sets as $set) {
      $units = array_merge($units, $set['units']);
    }

    // Empty strings should use the default non-printing 'unit'.
    if (empty($subject)) {
      $subject = 'unit';
    }

    // First pass unit case must match exactly( T=Tbsp, t=tsp ).
    foreach ($units as $unit_key => $unit) {
      $patterns = [];
      // Add name pattern.
      $patterns[] = '^' . $unit['name'] . 's{0,1}$';
      if (isset($unit['plural'])) {
        // Add plural name pattern.
        $patterns[] = '^' . $unit['plural'] . 's{0,1}$';
      }
      if (isset($unit['abbreviation'])) {
        // Add abbreviation pattern.
        $patterns[] = '^' . $unit['abbreviation'] . 's{0,1}\.{0,1}$';
      }
      if (isset($unit['aliases'])) {
        // Add alias patterns.
        foreach ($unit['aliases'] as $alias) {
          $patterns[] = '^' . trim($alias) . 's{0,1}\.{0,1}$';
        }
      }
      if (preg_match("/" . implode('|', $patterns) . "/", $subject)) {
        return $unit_key;
      }
    }

    // Second pass unit case doesn't matter.
    foreach ($units as $unit_key => $unit) {
      $patterns = [];
      // Add name pattern.
      $patterns[] = '^' . $unit['name'] . 's{0,1}$';
      if (isset($unit['plural'])) {
        // Add plural name pattern.
        $patterns[] = '^' . $unit['plural'] . 's{0,1}$';
      }
      if (isset($unit['abbreviation'])) {
        // Add abbreviation pattern.
        $patterns[] = '^' . $unit['abbreviation'] . 's{0,1}\.{0,1}$';
      }
      if (isset($unit['aliases'])) {
        // Add alias patterns.
        foreach ($unit['aliases'] as $alias) {
          $patterns[] = '^' . trim($alias) . 's{0,1}\.{0,1}$';
        }
      }
      if (preg_match("/" . implode('|', $patterns) . "/i", $subject)) {
        return $unit_key;
      }
    }

    return FALSE;
  }

}
