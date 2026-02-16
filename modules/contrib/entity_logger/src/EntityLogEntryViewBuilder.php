<?php

namespace Drupal\entity_logger;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder class for entity_log_entry entities.
 */
class EntityLogEntryViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build_list = [
      '#sorted' => TRUE,
      '#pre_render' => [[$this, 'buildMultiple']],
    ];
    $weight = 0;
    /** @var \Drupal\entity_logger\Entity\EntityLogEntryInterface $entity */
    foreach ($entities as $key => $entity) {
      $build_list[$key] = [
        '#type' => 'markup',
        '#markup' => new FormattableMarkup($entity->getMessage(), $entity->getContext()),
        // Collect cache defaults for this entity.
        '#cache' => [
          'tags' => Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags()),
          'contexts' => $entity->getCacheContexts(),
          'max-age' => $entity->getCacheMaxAge(),
        ],
      ];

      $entityType = $this->entityTypeId;
      $this->moduleHandler()->alter([$entityType . '_build', 'entity_build'], $build_list[$key], $entity, $view_mode);

      $build_list[$key]['#weight'] = $weight++;
    }

    return $build_list;
  }

}
