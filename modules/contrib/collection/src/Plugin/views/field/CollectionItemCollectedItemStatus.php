<?php

namespace Drupal\collection\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * A handler to provide the "published" status of the collected item entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("collection_item_collected_item_status")
 */
class CollectionItemCollectedItemStatus extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $status = 'N/A';
    $collection_item = $values->_entity;

    if ($collection_item->item->entity instanceof EntityPublishedInterface) {
      $status = $collection_item->item->entity->isPublished() ? 'Yes' : 'No';
    }
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Override the parent query function, since this is a computed field.
  }
}
