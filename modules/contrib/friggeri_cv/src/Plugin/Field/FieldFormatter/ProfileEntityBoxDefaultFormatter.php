<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Profile Entity Box Default' formatter.
 *
 * @FieldFormatter(
 *   id = "friggeri_cv_profile_entity_box_default",
 *   label = @Translation("Profile Entity Box Default"),
 *   field_types = {
 *     "friggeri_cv_profile_entity_box"
 *   }
 * )
 */
class ProfileEntityBoxDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => '<div class="entity-box">
                        <div class="row">
                            <div class="col-xs-3">
                                <div class="tenure">' . $item->tenure . '</div>
                            </div>
                            <div class="col-xs-9">
                                <div class="entity-title">
                                    <div class="entity-name">' . $item->title . '</div>
                                    <div class="authority-name-loc">' . $item->employer . '</div>
                                </div>
                                <div class="entity-position">' . $item->domain . '</div>
                                <div class="entity-info">' . $item->info . '</div>
                            </div>
                        </div>
                    </div>',
        '#attached' => [
          'library' => [
            'friggeri_cv/entity-box-formatter',
          ],
        ],
      ];
    }

    return $element;
  }

}
