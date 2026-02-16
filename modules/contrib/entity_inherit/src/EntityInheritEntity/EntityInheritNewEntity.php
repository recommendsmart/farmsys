<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritSingleFieldValueInterface;

/**
 * An entity.
 */
class EntityInheritNewEntity extends EntityInheritEntity {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInheritSingleFieldValueInterface $field_value) : bool {
    $field_name = $field_value->fieldName();

    return ($this->hasField($field_name) && $this->value($field_name) == []);
  }

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
    $this->app = $app;
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
  public function getMergedParents() : EntityInheritExistingMultipleEntitiesInterface {
    $return = $this->app->getEntityFactory()->newCollection();

    $return->add($this->referencedEntities($this->app->getParentEntityFields()->validOnly('parent')));

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function hasNewParents() : bool {
    return count($this->getMergedParents()) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function originalValue(EntityInheritFieldId $field_name) : array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function presaveAsParent() {
    // It is impossible for a new entity to be a parent of another entity.
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function triggersQueue() : bool {
    return FALSE;
  }

}
