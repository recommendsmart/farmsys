<?php

namespace Drupal\basket\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'basket_price_field' field type.
 *
 * @FieldType(
 *   id = "basket_price_field",
 *   label = @Translation("Basket Price Field"),
 *   module = "basket",
 *   category = @Translation("Number"),
 *   description = @Translation("Basket Price Field"),
 *   default_widget = "BasketPriceFieldWidget",
 *   default_formatter = "BasketPriceFieldFormatter"
 * )
 */
class BasketPriceField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'old_value'     => [
          'type'      => 'numeric',
          'precision' => 10,
          'scale'     => 2,
        ],
        'value'     => [
          'type'      => 'numeric',
          'precision' => 10,
          'scale'     => 2,
        ],
        'currency'  => [
          'type'      => 'int',
          'size'      => 'normal',
          'not null'  => FALSE,
        ],
      ],
      'indexes'     => [
        'value'         => ['value'],
        'currency'      => ['currency'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    $currency = $this->get('currency')->getValue();
    if (empty($value) || empty($currency)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['old_value'] = DataDefinition::create('string')->setLabel(t('Price old'));
    $properties['value'] = DataDefinition::create('string')->setLabel(t('Price'));
    $properties['currency'] = DataDefinition::create('string')->setLabel(t('Currency'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $old_value = $this->get('old_value')->getValue();
    if (empty($old_value)) {
      $this->get('old_value')->setValue(0);
    }
  }

}
