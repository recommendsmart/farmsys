<?php

namespace Drupal\collection\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * A handler to provide an entity type label for collected items.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("collection_item_collected_item_entity_type_label")
 */
class CollectionItemCollectedItemEntityTypeLabel extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity_type_label = '';
    $collection_item = $values->_entity;

    if ($collection_item->item){
      $entity_type_label = $collection_item->item->entity->getEntityType()->getLabel();

      if ($collection_item->item->entity->getEntityTypeId() === 'node') {
        $entity_type_label .= ': ' . node_get_type_label($collection_item->item->entity);
      }
      elseif ($collection_item->item->entity->getEntityType()->get('bundle_entity_type') !== NULL) {
        $bundle_key = $collection_item->item->entity->getEntityType()->getKey('bundle');
        $entity_type_label .= ': ' . $collection_item->item->entity->$bundle_key->entity->label();
      }
    }

    return $entity_type_label;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Override the parent query function, since this is a computed field.
  }
}
