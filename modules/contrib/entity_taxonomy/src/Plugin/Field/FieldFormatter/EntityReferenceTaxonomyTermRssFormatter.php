<?php

namespace Drupal\entity_taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference entity_taxonomy term RSS' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_rss_category",
 *   label = @Translation("RSS category"),
 *   description = @Translation("Display reference to entity_taxonomy term in RSS."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceTaxonomyTermRssFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $parent_entity = $items->getEntity();
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $parent_entity->rss_elements[] = [
        'key' => 'category',
        'value' => $entity->label(),
        'attributes' => [
          'domain' => $entity->id() ? Url::fromRoute('entity.entity_taxonomy_term.canonical', ['entity_taxonomy_term' => $entity->id()], ['absolute' => TRUE])->toString() : '',
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity_taxonomy terms.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'entity_taxonomy_term';
  }

}
