<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'PDF' formatter.
 *
 * @FieldFormatter(
 *   id = "friggeri_cv_pdf",
 *   label = @Translation("PDF"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class PdfFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $img = base_path() . drupal_get_path("module", "friggeri_cv")
      . "/img/pdf.png";

    foreach ($items as $delta => $item) {
      $id  = $item->getValue()['target_id'];
      $url = Url::fromRoute(
        'entity.profile.pdf',
        ['profile' => $id]
      )->setAbsolute()->toString();

      $element[$delta] = [
        '#markup' => "<a href='$url' target='_blank'><img src='$img'></a>",
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');

    return $target_type === 'profile';
  }

}
