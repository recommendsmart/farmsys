<?php

namespace Drupal\entity_inherit\EntityInheritField;

/**
 * Reprensents a Drupal field list.
 */
class EntityInheritFieldList implements EntityInheritFieldListInterface {

  /**
   * The internal list of field objects.
   *
   * @var array
   */
  protected $array;

  /**
   * Constructor.
   *
   * @param array $array
   *   An array of field objects.
   */
  public function __construct(array $array = []) {
    $this->array = $array;
  }

  /**
   * {@inheritdoc}
   */
  public function add(EntityInheritField $field) {
    $this->array[$field->__toString()] = $field;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->array);
  }

  /**
   * {@inheritdoc}
   */
  public function filter(array $field_list) : EntityInheritFieldListInterface {
    $return = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      foreach ($field_list as $field_string) {
        if (is_string($field_string) && $candidate->matchesString($field_string)) {
          $return->add($candidate);
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function filterByType(array $entity_types) : EntityInheritFieldListInterface {
    $return = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      if (in_array($candidate->entityType(), $entity_types)) {
        $return->add($candidate);
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function filterByName(array $names) : EntityInheritFieldListInterface {
    $return = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      if (in_array($candidate->fieldName()->fieldName(), $names)) {
        $return->add($candidate);
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function findById(string $id) : EntityInheritFieldInterface {
    if (array_key_exists($id, $this->array)) {
      return $this->array[$id];
    }
    throw new \Exception('Could not find field with id ' . $id . ' in list.');
  }

  /**
   * {@inheritdoc}
   */
  public function includes(string $entity_type, string $field_name) : bool {
    foreach ($this->array as $field) {
      if ($field->matches($entity_type, $field_name)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidOnly(string $category) : EntityInheritFieldListInterface {
    $invalid = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      if (!$candidate->valid($category)) {
        $invalid->add($candidate);
      }
    }

    return $invalid;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    return $this->array;
  }

  /**
   * {@inheritdoc}
   */
  public function toFieldIdsArray() : array {
    $return = [];

    foreach ($this->array as $candidate) {
      $return[$candidate->__toString()] = $candidate->fieldName();
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function toFieldNamesArray() : array {
    $return = [];

    foreach ($this->array as $candidate) {
      $return[$candidate->fieldName()] = $candidate->fieldName();
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function toTextArea() : string {
    return implode(PHP_EOL, $this->array);
  }

  /**
   * {@inheritdoc}
   */
  public function validOnly(string $category) : EntityInheritFieldListInterface {
    $valid = new EntityInheritFieldList();

    foreach ($this->array as $candidate) {
      if ($candidate->valid($category)) {
        $valid->add($candidate);
      }
    }

    return $valid;
  }

}
