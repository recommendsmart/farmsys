<?php

namespace Drupal\field_fallback_test\Plugin\FieldFallbackConverter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterBase;

/**
 * Converter that converts an image field value to a string.
 *
 * @FieldFallbackConverter(
 *   id = "string_to_any_value",
 *   label = @Translation("String to any value"),
 *   source = {"string"},
 *   target = {"*"},
 *   weight = 0
 * )
 */
class ConvertStringToAnyValue extends FieldFallbackConverterBase {

  /**
   * {@inheritdoc}
   */
  public function convert(FieldItemListInterface $field) {
    return 'test value';
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(FieldDefinitionInterface $target_field, FieldDefinitionInterface $source_field): bool {
    return $source_field->getName() !== 'title';
  }

}
