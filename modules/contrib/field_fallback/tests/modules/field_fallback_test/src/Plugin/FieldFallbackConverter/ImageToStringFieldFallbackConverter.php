<?php

namespace Drupal\field_fallback_test\Plugin\FieldFallbackConverter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterBase;
use Drupal\file\FileInterface;

/**
 * Converter that converts an image field value to a string.
 *
 * @FieldFallbackConverter(
 *   id = "image_to_string",
 *   label = @Translation("Image to string"),
 *   source = {"image"},
 *   target = {"string"},
 *   weight = 1
 * )
 */
class ImageToStringFieldFallbackConverter extends FieldFallbackConverterBase {

  /**
   * {@inheritdoc}
   */
  public function convert(FieldItemListInterface $field) {
    $file = $field->entity;
    return $file instanceof FileInterface ? $file->getFileUri() : NULL;
  }

}
