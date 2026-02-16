<?php

namespace Drupal\flow\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Qualifies entities.
 */
class EntityQualifier {

  /**
   * Get the entity qualifier service.
   *
   * @return \Drupal\flow\Entity\EntityQualifier
   *   The entity qualifier service.
   */
  public static function service(): EntityQualifier {
    return \Drupal::service('flow.entity_qualifier');
  }

  /**
   * Evaluates whether the given entity is qualified.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to evaluate its qualification. This must be a content entity.
   * @param \Drupal\flow\Plugin\FlowQualifierInterface[] $qualifiers
   *   The qualifiers to evaluate the entity against.
   *
   * @return bool
   *   Returns TRUE if the entity is qualified, FALSE otherwise.
   */
  public function qualifies(EntityInterface $entity, iterable $qualifiers): bool {
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException("Only content entities are supported for qualifying.");
    }
    $i = 0;
    /** @var \Drupal\flow\Plugin\FlowQualifierInterface $qualifier */
    foreach ($qualifiers as $qualifier) {
      $i++;
      if ($qualifier->qualifies($entity)) {
        return TRUE;
      }
    }
    // No qualifier means an entity always qualifies.
    return $i === 0;
  }

}
