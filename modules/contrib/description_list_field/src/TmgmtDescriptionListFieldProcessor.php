<?php

declare(strict_types = 1);

namespace Drupal\description_list_field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tmgmt_content\DefaultFieldProcessor;

/**
 * TMGMT field processor for the description list field.
 */
class TmgmtDescriptionListFieldProcessor extends DefaultFieldProcessor {

  /**
   * {@inheritdoc}
   */
  public function extractTranslatableData(FieldItemListInterface $field) {
    $data = parent::extractTranslatableData($field);

    // Remove the #format from the columns which actually should not have a
    // text format.
    foreach ($data as $delta => &$value) {
      if (!is_numeric($delta)) {
        continue;
      }
      if (isset($value['term']['#format'])) {
        unset($value['term']['#format']);
      }
    }

    return $data;
  }

}
