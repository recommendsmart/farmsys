<?php

namespace Drupal\access_records\Controller;

use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns responses for access record translation pages.
 */
class AccessRecordTranslationController extends ContentTranslationController {

  /**
   * {@inheritdoc}
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $build = parent::overview($route_match, $entity_type_id);
    /** @var \Drupal\access_records\AccessRecordInterface $entity */
    $entity = $build['#entity'];
    $build['#title'] = $this->t('Translations of access record %label with ID %id', [
      '%label' => $entity->label(),
      '%id' => $entity->id(),
    ]);
    return $build;
  }

}
