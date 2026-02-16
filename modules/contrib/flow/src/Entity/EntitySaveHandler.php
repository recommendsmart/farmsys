<?php

namespace Drupal\flow\Entity;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityRepositoryTrait;

/**
 * Handles the saving of multiple entities.
 */
class EntitySaveHandler {

  use EntityRepositoryTrait;

  /**
   * The saving list threshold.
   *
   * When the number of entities in a saving list surpasses this value, then
   * saving will be performed.
   *
   * @var int
   */
  protected static int $threshold = 20;

  /**
   * Constructs a new entity save handler instance.
   *
   * @param int $threshold
   *   The saving list threshold.
   */
  public function __construct(int $threshold = 20) {
    static::$threshold = $threshold;
  }

  /**
   * Get the entity save handler service.
   *
   * @return \Drupal\flow\Entity\EntitySaveHandler
   *   The entity save handler.
   */
  public static function service(): EntitySaveHandler {
    return \Drupal::service('flow.entity_save_handler');
  }

  /**
   * Saves the given list of entities, if it makes sense to do so.
   *
   * Any entity that got saved will be removed from the given list.
   * This method should only be called when it's guaranteed that somewhere later
   * within the same PHP process ::ensureSave() will be finally called.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] &$entities
   *   The list of entities to save.
   */
  public function saveIfRequired(array &$entities): void {
    if (count($entities) > static::$threshold) {
      $this->ensureSave($entities);
      return;
    }
    if (count($entities) > 1) {
      foreach ($entities as $entity) {
        foreach ($entity->referencedEntities() as $referenced) {
          if ($referenced->isNew() && in_array($referenced, $entities, TRUE) && ($referenced !== $entity)) {
            $this->ensureSave($entities);
            return;
          }
        }
      }
    }
  }

  /**
   * Ensures that all entities are being saved that are in the given list.
   *
   * Any entity that is ensured to be saved (or got already saved) will be
   * removed from the given list. Entities may remain in the list, when they
   * are not safe to be saved now, but instead to be saved later. No further
   * call of ::ensureSave() is needed, as this handler guarantees the save.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] &$entities
   *   The list of entities to save.
   */
  public function ensureSave(array &$entities): void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $ensured */
    $ensured = [];
    // Treat entities laying in the Flow stack as already ensured for saving.
    foreach (Flow::$stack as &$stacked_entities) {
      foreach ($stacked_entities as $stacked_entity) {
        if (!in_array($stacked_entity, $ensured, TRUE)) {
          if ($uuid = $stacked_entity->uuid()) {
            $ensured[$uuid] = $stacked_entity;
          }
          else {
            $ensured[] = $stacked_entity;
          }
        }
      }
    }
    foreach ($entities as $i => $entity) {
      if (in_array($entity, $ensured, TRUE) || ($entity->uuid() && isset($ensured[$entity->uuid()]))) {
        unset($entities[$i]);
        continue;
      }
      if ($entity->isNew() && $entity->uuid() && ($loaded = $this->getEntityRepository()->loadEntityByUuid($entity->getEntityTypeId(), $entity->uuid()))) {
        if (!$loaded->isNew()) {
          // Entity got already saved.
          unset($entities[$i]);
          continue;
        }
      }
      foreach ($entity->referencedEntities() as $referenced) {
        if ($referenced->isNew() && (in_array($referenced, $ensured, TRUE) || ($referenced->uuid() && isset($ensured[$referenced->uuid()])))) {
          // When the contained reference is in the list of ensured entities,
          // and the reference is new, then it is within its own saving process.
          // For that case, this entity needs to be saved afterwards. Otherwise
          // it would cause an infinite loop of save attempts, because new
          // referenced entities are being automatically saved by
          // EntityReferenceItem. Saving the entity afterwards will happen, if
          // not otherwise done, handled by flow_entity_insert() and
          // flow_entity_update() that both call _flow_process_after_task().
          if ($entity->isNew()) {
            // When both entities are new, we need to take care of it below.
            $ensured_new[] = $referenced;
            continue;
          }
          continue 2;
        }
      }
      if ($entity->isNew()) {
        // Take a look whether this new entity is being referenced from one
        // of the ensured entities. If so, we need to take care by properly
        // saving it, before it can be referenced from an ensured entity.
        $entity_saved = FALSE;
        /** @var \Drupal\Core\Entity\ContentEntityInterface $ensured_entity */
        foreach ($ensured as $ensured_entity) {
          $is_referenced = in_array($entity, $ensured_entity->referencedEntities(), TRUE);
          if ($is_referenced && !$entity_saved) {
            if (!empty($ensured_new)) {
              // For the case both entities are new, it is a problem of what
              // came first. The entity needs to be saved for being referenced,
              // but this would cause an infinite loop as EntityReferenceItem
              // would automatically try to save the other one too. Therefore
              // the new ones need to be filtered out, save the entity, re-add
              // the new ones and let this one to be saved again afterwards.
              $unfiltered_lists = [];
              $filtered_lists = [];
              foreach ($entity as $field_name => $item_list) {
                if ($item_list instanceof EntityReferenceFieldItemListInterface) {
                  $referenced_entities = $item_list->referencedEntities();
                  foreach ($ensured_new as $ensured_new_entity) {
                    if (in_array($ensured_new_entity, $referenced_entities, TRUE)) {
                      $unfiltered_lists[$field_name] = $unfiltered_lists[$field_name] ?? $referenced_entities;
                      $filtered_lists[$field_name] = array_filter($filtered_lists[$field_name] ?? $referenced_entities, function ($referenced_entity) use ($ensured_new_entity) {
                        return $referenced_entity !== $ensured_new_entity;
                      });
                    }
                  }
                }
              }
              foreach ($filtered_lists as $field_name => $references) {
                /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
                $entity->get($field_name)->setValue($references);
              }
            }
            $flow_is_active = Flow::isActive();
            Flow::setActive(FALSE);
            try {
              $entity->save();
              $entity_saved = TRUE;
              if (!empty($unfiltered_lists)) {
                foreach ($unfiltered_lists as $field_name => $references) {
                  /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
                  $entity->get($field_name)->setValue($references);
                }
              }
              if ($entity instanceof RevisionableInterface) {
                // Another save will follow.
                $entity->updateLoadedRevisionId();
                $entity->setNewRevision(FALSE);
              }
            }
            finally {
              Flow::setActive($flow_is_active);
            }
          }
          if ($is_referenced) {
            foreach ($ensured_entity as $field_name => $item_list) {
              if ($item_list instanceof EntityReferenceFieldItemListInterface) {
                foreach ($item_list as $item) {
                  if ($item->entity === $entity) {
                    $item->setValue($entity);
                  }
                }
              }
            }
          }
        }
        if (!empty($ensured_new)) {
          // Save the entity again afterwards.
          $after_save[] = $entity;
          continue;
        }
      }
      unset($entities[$i]);
      $entity->save();
      if ($uuid = $entity->uuid()) {
        $ensured[$uuid] = $entity;
      }
      else {
        $ensured[] = $entity;
      }
    }
    if (!empty($after_save)) {
      foreach ($after_save as $entity) {
        array_push(Flow::$save, $entity);
      }
    }
  }

}
