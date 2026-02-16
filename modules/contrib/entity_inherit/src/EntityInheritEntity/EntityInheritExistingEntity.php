<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValue;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritSingleFieldValueInterface;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface;

/**
 * An entity which preexists.
 */
class EntityInheritExistingEntity extends EntityInheritEntity implements EntityInheritSingleExistingEntityInterface, EntityInheritExistingEntityCollectionInterface {

  use StringTranslationTrait;

  /**
   * The Drupal entity id.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructor.
   *
   * @param string $type
   *   The Drupal entity type such as "node".
   * @param string $id
   *   The Drupal entity id such as 1.
   * @param null|\Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The Drupal entity object, or NULL if we don't have it.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The global app.
   */
  public function __construct(string $type, string $id, $entity, EntityInherit $app) {
    $this->id = $id;
    parent::__construct($type, $entity, $app);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInheritSingleFieldValueInterface $field_value) : bool {
    $field_name = $field_value->fieldName();

    return ($this->value($field_name) == [] && $this->hasNewParents()) || ($this->hasField($field_name) && $this->value($field_name) == $field_value->previousValue() && $field_value->changed());
  }

  /**
   * Get all children of this entity.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface
   *   This entity's children.
   */
  public function children() : EntityInheritExistingMultipleEntitiesInterface {
    return $this->app->getStorage()->getChildrenOf($this->getType(), $this->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function fieldValues() : EntityInheritFieldValueCollectionInterface {
    $factory = $this->app->getFieldValueFactory();
    $return = $factory->newCollection();
    $original = $this->original();

    foreach ($this->inheritableFields()->toFieldIdsArray() as $field_id) {
      $return->add($factory->newFieldValue($field_id, $this->value($field_id), $original->value($field_id)));
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEntity() {
    if ($this->drupalEntity === NULL) {
      $candidate = $this->app->getEntityTypeManager()->getStorage($this->type)->load($this->id);

      if ($candidate && is_a($candidate, FieldableEntityInterface::class)) {
        $this->drupalEntity = $candidate;
      }
    }
    if ($this->drupalEntity === NULL) {
      throw new \Exception('Cannot create entity of type ' . $this->type . ' with id ' . $this->id);
    }
    return $this->drupalEntity;
  }

  /**
   * Get this entity's id.
   *
   * @return string
   *   This entity's id.
   */
  public function getId() : string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasNewParents() : bool {
    return count($this->getMergedParents()->remove($this->original()->getMergedParents())) > 0;
  }

  /**
   * Get the original entity before it was modified on save.
   *
   * @return \Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntityRevisionInterface
   *   The original entity.
   */
  public function original() : EntityInheritEntityRevisionInterface {
    $entity = $this->getDrupalEntity();
    if (isset($entity->original)) {
      return new EntityInheritOriginalEntity($entity->original, $this->app);
    }
    else {
      // We are not in the process of being saved; so there is no "original"
      // property.
      return $this;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function originalValue(EntityInheritFieldId $field_name) : array {
    return $this->original()->value($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function presaveAsParent() {
    $field_values = $this->fieldValues();
    $this->app->getQueue()->add(array_keys($this->children()->toArray()), $field_values->toOriginalArray(), $field_values->toChangedArray());
  }

  /**
   * {@inheritdoc}
   */
  public function process(array $parent) {
    $entity = $this->getDrupalEntity();

    foreach ($parent['original'] as $field => $original_value) {
      if (array_key_exists($field, $parent['changed']) && $parent['changed'][$field] != $parent['original'][$field]) {
        $fieldvalue = new EntityInheritFieldValue($this->app, new EntityInheritFieldId(explode('.', $field)[0], explode('.', $field)[1]), $parent['changed'][$field], $parent['original'][$field]);
        $this->updateField($fieldvalue);
      }
    }

    $entity->save();

    $this->drupalEntity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    return [
      $this->toStorageId() => $this,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function toStorageId() : string {
    return $this->type . ':' . $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function triggersQueue() : bool {
    return (count($this->children()) && !$this->app->getQueue()->contains($this->toStorageId()));
  }

}
