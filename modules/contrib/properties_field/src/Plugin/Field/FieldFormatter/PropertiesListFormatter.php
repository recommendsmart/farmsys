<?php

namespace Drupal\properties_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a definition list formatter for the properties field.
 *
 * @FieldFormatter(
 *   id = "properties_list",
 *   label = @Translation("Properties list"),
 *   field_types = {
 *     "properties"
 *   }
 * )
 */
class PropertiesListFormatter extends PropertiesFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($items->isEmpty()) {
      return [];
    }

    $list_items = [];
    foreach ($items as $item) {
      $plugin = $this->getValueTypePlugin($item->type);
      $value = $plugin ? $plugin->formatterRender($item->value) : '';

      $list_items[] = [
        'label' => $item->label,
        'value' => $value,
      ];
    }

    return [
      [
        '#theme' => 'properties_list',
        '#items' => $list_items,
      ]
    ];
  }

}
