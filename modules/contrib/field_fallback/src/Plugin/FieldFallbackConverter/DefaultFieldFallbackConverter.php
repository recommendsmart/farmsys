<?php

namespace Drupal\field_fallback\Plugin\FieldFallbackConverter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterBase;

/**
 * The default field fallback converter.
 *
 * This converter assigns the same value to the target field.
 *
 * @FieldFallbackConverter(
 *   id = "default",
 *   label = @Translation("Default"),
 *   source = {"*"},
 *   target = {"*"},
 *   weight = -999
 * )
 */
class DefaultFieldFallbackConverter extends FieldFallbackConverterBase {

  /**
   * {@inheritdoc}
   */
  public function convert(FieldItemListInterface $field) {
    return $field->getValue();
  }

}
