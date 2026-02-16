<?php

namespace Drupal\entity_inherit\EntityInheritStorage;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritEntity\EntityInheritExistingMultipleEntitiesInterface;

/**
 * Storage.
 */
class EntityInheritStorage implements EntityInheritStorageInterface {

  use StringTranslationTrait;

  /**
   * The app singleton.
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The app singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->app = $app;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrenOf(string $type, string $id) : EntityInheritExistingMultipleEntitiesInterface {
    $drupal_entities = [];

    foreach (array_keys($this->app->getParentEntityFields()->validOnly('parent')->toArray()) as $field) {
      $drupal_entities = array_merge($drupal_entities, $this->getReferencingEntities($field, $type, $id));
    }

    return $this->app->getEntityFactory()->newCollection($drupal_entities);
  }

  /**
   * Get all entities whose source field targets entity of specified type, id.
   *
   * @param string $source_field_id
   *   An entity's source field such as 'node.field_parents'.
   * @param string $target_type
   *   An entity's target type such as 'node' or 'paragraph'.
   * @param string $target_id
   *   An entity's target id such as '1' or '24161'.
   *
   * @return array
   *   Array of Drupal entities. In case of an error, we will log the error and
   *   return an empty array.
   */
  public function getReferencingEntities(string $source_field_id, string $target_type, string $target_id) : array {
    $return = [];
    try {
      $source_type = $this->app->explodeFieldId($source_field_id)[0];
      $source_field_name = $this->app->explodeFieldId($source_field_id)[1];

      // All the Drupal entities we will return will necessarily be of type
      // $source_type because fields cannot be shared between entities of
      // different types.
      $query = $this->app
        ->getEntityTypeManager()
        ->getStorage($source_type)
        ->getQuery();
      $query->condition($source_field_name . '.target_id', $target_id);
      return $this->app
        ->getEntityTypeManager()
        ->getStorage($target_type)
        ->loadMultiple($query->execute());
    }
    catch (\Throwable $t) {
      $this->app->watchdogAndUserError($t, $this->t('Could not get referenceing entities.'));
    }
    return $return;
  }

}
