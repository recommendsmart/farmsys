<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;

/**
 * An original entity.
 */
class EntityInheritOriginalEntity extends EntityInheritEntityRevision {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The global app.
   */
  public function __construct(FieldableEntityInterface $entity, EntityInherit $app) {
    $this->drupalEntity = $entity;
    parent::__construct($entity->getEntityTypeId(), $entity, $app);
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEntity() {
    return $this->drupalEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function originalValue(EntityInheritFieldId $field_name) : array {
    return [];
  }

}
