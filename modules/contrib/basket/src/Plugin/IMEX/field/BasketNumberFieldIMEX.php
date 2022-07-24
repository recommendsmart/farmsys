<?php

namespace Drupal\basket\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;

/**
 * Prices processing plugin.
 *
 * @BasketIMEXfield(
 *  id        = "basket_number",
 *  type      = {"basket_price_field:value","basket_price_field:old_value"},
 *  name      = "Basket number field",
 *  type_info = "(decimal)",
 * )
 */
class BasketNumberFieldIMEX implements BasketIMEXfieldInterface {

  /**
   * Getting data for export.
   */
  public function getValues($entity, $fieldName) {
    $values = [];
    if (!empty($entity->basketIMEXgetSubField) && !empty($entity->{$fieldName}[0]->{$entity->basketIMEXgetSubField})) {
      $values[] = $entity->{$fieldName}[0]->{$entity->basketIMEXgetSubField};
    }
    return implode(PHP_EOL, $values);
  }

  /**
   * Data array formation.
   */
  public function setValues($entity, $importValue = '') {
    if (!empty($importValue)) {
      $importValue = str_replace(',', '.', $importValue);
      $importValue = floatval(preg_replace("/[^-0-9\.]/", "", $importValue));
    }
    return trim($importValue);
  }

  /**
   * Additional field processing after $entity update / creation.
   */
  public function postSave($entity, $importValue = '') {}

}
