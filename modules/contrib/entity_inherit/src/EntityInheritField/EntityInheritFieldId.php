<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field ID.
 */
class EntityInheritFieldId {

  /**
   * A content type such as node.
   *
   * @var string
   */
  protected $entityType;

  /**
   * A field name such as field_parents or body.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Constructor.
   *
   * @param string $entity_type
   *   An entity type such as node.
   * @param string $field_name
   *   A field name such as field_parents or body.
   */
  public function __construct(string $entity_type, string $field_name) {
    foreach ([
      'entity type' => $entity_type,
      'field name' => $field_name,
    ] as $type => $var) {
      if (strpos($var, '.') !== FALSE) {
        throw new \Exception($var . ' is not a valid ' . $type);
      }
    }
    $this->entityType = $entity_type;
    $this->fieldName = $field_name;
  }

  /**
   * Get the field name.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   A Drupal entity. Used to confirm that the field is compatible with that
   *   entity. If NULL, then we don't check.
   *
   * @return string
   *   A field name.
   *
   * @throws \Exception
   *   An exception is thrown if the entity is not compatible with this
   *   field id.
   */
  public function fieldName($entity = NULL) : string {
    if ($entity && $entity->getEntityTypeId() != $this->entityType) {
      throw new \Exception('An entity of type ' . $entity->getEntityTypeId() . ' cannot have a field of type ' . $this->entityType);
    }
    return $this->fieldName;
  }

  /**
   * Check whether this field matches an entity type/name combination.
   *
   * @param string $entity_type
   *   An entity type such as node.
   * @param string $field_name
   *   A field name such as field_parents or body.
   *
   * @return bool
   *   TRUE if matches.
   */
  public function matches(string $entity_type, string $field_name) {
    return $this->fieldName == $field_name && $this->entityType == $entity_type;
  }

  /**
   * Return a unique ID for this field id as a string.
   *
   * @return string
   *   A unique ID such as "node.body".
   */
  public function uniqueId() : string {
    return $this->entityType . '.' . $this->fieldName;
  }

}
