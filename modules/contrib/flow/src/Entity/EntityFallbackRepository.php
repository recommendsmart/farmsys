<?php

namespace Drupal\flow\Entity;

/**
 * A repository holding entities as fallback subject items.
 */
class EntityFallbackRepository {

  /**
   * The current list of fallback items, keyed by fallback hash.
   *
   * @var \Drupal\Core\Entity\EntityInterface[][]
   */
  public static array $items = [];

}
