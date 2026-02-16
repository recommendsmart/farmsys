<?php

namespace Drupal\ingredient\Utility;

use Drupal\Component\Utility\Xss;

/**
 * Provides the ingredient.quantity service.
 */
class IngredientQuantityUtility {

  /**
   * Converts an ingredient's quantity from decimal to fraction.
   *
   * @param float $ingredient_quantity
   *   The ingredient quantity formatted as a decimal.
   * @param string $fraction_format
   *   A string representing the fraction format, used by sprintf().
   * @param bool $edit_mode
   *   Whether or not the ingredient is being edited.
   *
   * @return string
   *   The ingredient quantity formatted as a fraction.
   */
  public function getQuantityFromDecimal($ingredient_quantity, $fraction_format = '{%d} %d&frasl;%d', $edit_mode = FALSE) {
    if (strpos($ingredient_quantity, '.')) {
      $decimal = abs($ingredient_quantity);
      $whole = floor($decimal);
      $numerator = 0;
      $denominator = 1;
      $top_heavy = 0;

      $power = 1;
      $flag = 0;
      while ($flag == 0) {
        $argument = $decimal * $power;
        if ($argument == floor($argument)) {
          $flag = 1;
        }
        else {
          $power = $power * 10;
        }
      }

      // We have to workaround for repeating, non-exact decimals for thirds,
      // sixths, ninths, twelfths.
      $overrides = [
        // Thirds.
        '3333' => [1, 3],
        '6666' => [2, 3],
        '9999' => [3, 3],
        '1666' => [1, 6],
        // Sixths.
        '8333' => [5, 6],
        // Ninths.
        '1111' => [1, 9],
        '2222' => [2, 9],
        '4444' => [4, 9],
        '5555' => [5, 9],
        '7777' => [7, 9],
        '8888' => [8, 9],
        '0833' => [1, 12],
        '4166' => [5, 12],
        '5833' => [7, 12],
        // twelfths.
        '9166' => [11, 12],
      ];
      // Truncate the whole part to get just the fractional part.
      $conversionstr = substr((string) ($decimal - floor($decimal)), 2, 4);
      if (array_key_exists($conversionstr, $overrides)) {
        if ($overrides[$conversionstr][0] == $overrides[$conversionstr][1]) {
          return ($whole + 1);
        }
        $denominator = $overrides[$conversionstr][1];
        $numerator   = (floor($decimal) * $denominator) + $overrides[$conversionstr][0];
      }
      else {
        $numerator = $decimal * $power;
        $denominator = $power;
      }

      // Repeating decimals have been corrected.
      $gcd = $this->getGcd($numerator, $denominator);

      $numerator = $numerator / $gcd;
      $denominator = $denominator / $gcd;
      $top_heavy = $numerator;

      $numerator = abs($top_heavy) - (abs($whole) * $denominator);

      $ingredient_quantity = sprintf($fraction_format, $whole, $numerator, $denominator);

      if (($whole == 0) && (strpos($ingredient_quantity, '{') >= 0)) {
        // Remove anything in curly braces.
        $ingredient_quantity = preg_replace('/{.*}/', '', $ingredient_quantity);
      }
      else {
        // Remove just the curly braces, but keep everything between them.
        $ingredient_quantity = preg_replace('/{|}/', '', $ingredient_quantity);
      }

      // In edit mode we don't want to show html tags like <sup> and <sub>.
      if ($edit_mode) {
        $ingredient_quantity = strip_tags($ingredient_quantity);
      }
    }

    return Xss::filterAdmin(trim($ingredient_quantity));
  }

  /**
   * Finds the greatest common divisor of two numbers.
   *
   * @param int $a
   *   The initial dividend of the operation.
   * @param int $b
   *   The initial divisor of the operation.
   *
   * @return int
   *   The greatest common divisor of $a and $b.
   */
  protected function getGcd($a, $b) {
    while ($b != 0) {
      $remainder = $a % $b;
      $a = $b;
      $b = $remainder;
    }
    return abs($a);
  }

  /**
   * Converts an ingredient's quantity from fraction to decimal.
   *
   * @param string $ingredient_quantity
   *   The ingredient quantity formatted as a fraction.
   *
   * @return float
   *   The ingredient quantity formatted as a decimal.
   */
  public function getQuantityFromFraction($ingredient_quantity) {
    // Replace a dash separated fraction with a ' ' to normalize the input
    // string.
    $ingredient_quantity = preg_replace('/^(\d+)[\-](\d+)[\/](\d+)/', '${1} ${2}/${3}', $ingredient_quantity);

    if ($pos_slash = strpos($ingredient_quantity, '/')) {
      $pos_space = strpos($ingredient_quantity, ' ');

      // Can't trust $pos_space to be a zero value if there is no space
      // so set it explicitly.
      if ($pos_space === FALSE) {
        $pos_space = 0;
      }

      $whole = (int) substr($ingredient_quantity, 0, $pos_space);
      $numerator = (int) substr($ingredient_quantity, $pos_space, $pos_slash);
      $denominator = (int) substr($ingredient_quantity, $pos_slash + 1);
      $ingredient_quantity = $whole + ($numerator / $denominator);
    }

    return $ingredient_quantity;
  }

}
