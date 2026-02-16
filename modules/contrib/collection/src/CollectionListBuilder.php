<?php

namespace Drupal\collection;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Collection entities.
 *
 * @ingroup collection
 */
class CollectionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    $loaded_collections = $this->storage->loadMultiple($entity_ids);
    $displayed_collections = [];

    foreach ($loaded_collections as $collection) {
      if ($collection->access('view')) {
        $displayed_collections[] = $collection;
      }
    }

    return $displayed_collections;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('label'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\collection\Entity\Collection $entity */
    $row['name'] = $entity->toLink();
    $row['type'] = $entity->bundle();
    return $row + parent::buildRow($entity);
  }

}
