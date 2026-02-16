<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Profile Contact Box Default' formatter.
 *
 * @FieldFormatter(
 *   id = "friggeri_cv_profile_contact_box_default",
 *   label = @Translation("Profile Contact Box Default"),
 *   field_types = {
 *     "friggeri_cv_profile_contact_box"
 *   }
 * )
 */
class ProfileContactBoxDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => '<h5>' . $item->heading . '</h5>
                      <i class="' . $item->font_awesome_icon . '"> ' . $item->contacts . '</i>',
        '#attached' => [
          'library' => [
            'friggeri_cv/font-awesome',
          ],
        ],
      ];
    }

    return $element;
  }

}
