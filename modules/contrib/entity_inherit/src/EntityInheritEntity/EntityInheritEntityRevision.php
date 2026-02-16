<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValue;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface;

/**
 * An entity or entity revision.
 */
abstract class EntityInheritEntityRevision implements EntityInheritEntityRevisionInterface, EntityInheritReadableEntityInterface {

  use StringTranslationTrait;

  /**
   * The injected app singleton.
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * The Drupal entity.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $drupalEntity;

  /**
   * The Drupal entity type.
   *
   * @var string
   */
  protected $type;

  /**
   * Constructor.
   *
   * @param string $type
   *   The Drupal entity type such as "node".
   * @param null|\Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The Drupal entity object, or NULL if we don't have it.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The global app.
   */
  public function __construct(string $type, $entity, EntityInherit $app) {
    $this->type = $type;
    $this->app = $app;
    $this->drupalEntity = $entity;
  }

  /**
   * Get all inheritable field names.
   *
   * @return array
   *   All inheritable field names.
   */
  public function allFieldNames() : array {
    return $this->app->bundleFieldNames($this->getType(), $this->getBundle());
  }

  /**
   * Get the Drupal entity.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   This Drupal entity.
   */
  abstract public function getDrupalEntity();

  /**
   * {@inheritdoc}
   */
  public function getMergedParents() : EntityInheritExistingMultipleEntitiesInterface {
    $return = $this->app->getEntityFactory()->newCollection();

    $fields = $this->app->getParentEntityFields()->validOnly('parent');

    $return->add($this->referencedEntities($fields));

    return $return;
  }

  /**
   * Get this entity's bundle.
   */
  public function getBundle() : string {
    return $this->getDrupalEntity()->bundle();
  }

  /**
   * Retrieve a field object linked to a Drupal entity.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId $field_name
   *   A field name.
   *
   * @return mixed
   *   A Drupal field object, or NULL.
   *
   * @throws \Exception
   */
  public function getField(EntityInheritFieldId $field_name) {
    $return = NULL;
    try {
      $field = $this->app->fieldFactory()->fromId($field_name);
      if ($field->entityType() == $this->type) {
        if (!$this->getDrupalEntity()->hasField($field->fieldName()->fieldName())) {
          return NULL;
        }
        return $this->getDrupalEntity()->get($field->fieldName()->fieldName($this->getDrupalEntity()));
      }
    }
    catch (\Throwable $t) {
      $this->app->watchdogAndUserError($t, $this->t('Could not fetch field from entity.'));
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() : string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldValues() : EntityInheritFieldValueCollectionInterface {
    $return = $this->app->getFieldValueFactory()->newCollection();

    foreach ($this->allFieldNames() as $field_name) {
      $return->add(new EntityInheritFieldValue($this->app, $field_name, $this->value($field_name), $this->originalValue($field_name)));
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function hasField(EntityInheritFieldId $field) : bool {
    return array_key_exists($field->uniqueId(), $this->allFieldNames());
  }

  /**
   * {@inheritdoc}
   */
  public function inheritableFields() : EntityInheritFieldListInterface {
    return $this->app->inheritableFields($this->getType(), $this->getBundle());
  }

  /**
   * Get the original value of a field.
   *
   * @param \Drupal\entity_inherit\EntityInheritField\EntityInheritFieldId $field_name
   *   A field.
   *
   * @return array
   *   An original value.
   */
  abstract public function originalValue(EntityInheritFieldId $field_name) : array;

  /**
   * {@inheritdoc}
   */
  public function referencedEntities(EntityInheritFieldListInterface $fields) : EntityInheritExistingMultipleEntitiesInterface {
    $return = $this->app->getEntityFactory()->newCollection();

    foreach ($fields->toArray() as $field) {
      if ($field_object = $this->getField($field->fieldName())) {
        $drupal_entities = $field_object->referencedEntities();
        foreach ($drupal_entities as $drupal_entity) {
          $return->add(new EntityInheritExistingEntity($drupal_entity->getEntityTypeId(), $drupal_entity->id(), $drupal_entity, $this->app));
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function value(EntityInheritFieldId $field_name) : array {
    $candidate = $this->getField($field_name);
    return $candidate ? $candidate->getValue() : [];
  }

}
