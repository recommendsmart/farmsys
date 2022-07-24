<?php

namespace Drupal\basket\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;

/**
 * Currency processing plugin.
 *
 * @BasketIMEXfield(
 *    id        = "basket_currency",
 *    type      = {"basket_price_field:currency"},
 *    name      = "Basket currency field",
 *    type_info = "(string)<br/>ISO code",
 * )
 */
class BasketCurrencyFieldIMEX implements BasketIMEXfieldInterface {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * Getting data for export.
   */
  public function getValues($entity, $fieldName) {
    $values = [];
    if (!empty($entity->basketIMEXgetSubField) && !empty($entity->{$fieldName}[0]->{$entity->basketIMEXgetSubField})) {
      $currency = $this->basket->Currency()->load($entity->{$fieldName}[0]->{$entity->basketIMEXgetSubField});
      if (!empty($currency)) {
        $values[] = $currency->iso;
      }
    }
    return implode(PHP_EOL, $values);
  }

  /**
   * Data array formation.
   */
  public function setValues($entity, $importValue = '') {
    if (!empty($importValue)) {
      $currency = $this->basket->Currency()->loadByISO(trim($importValue));
    }
    return !empty($currency) ? $currency->id : 0;
  }

  /**
   * Additional field processing after $entity update / creation.
   */
  public function postSave($entity, $importValue = '') {}

}
