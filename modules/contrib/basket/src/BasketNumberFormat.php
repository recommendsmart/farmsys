<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketNumberFormat {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $sigFigs = 3) {
    $numero = $value;
    if ($numero > 9999) {
      $units = ['', 'K', 'M', 'G', 'T', 'P', 'E'];
      $index = floor(log10($value) / 3);
      $value = $index ? $value / pow(1000, $index) : $value;
      return $this->sigFig($value, $sigFigs) . $units[$index];
    }
    else {
      return number_format($numero, 0, '', '.');
      ;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sigFig($value, $sigFigs = 3) {
    setlocale(LC_ALL, 'it_IT@euro', 'it_IT', 'it');
    $exponent = floor(log10(abs($value)) + 1);
    $significand = round(($value
          / pow(10, $exponent))
          * pow(10, $sigFigs))
            / pow(10, $sigFigs);
    return $significand * pow(10, $exponent);
  }

}
