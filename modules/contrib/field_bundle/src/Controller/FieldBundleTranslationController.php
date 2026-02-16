<?php

namespace Drupal\field_bundle\Controller;

use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns responses for field bundle translation pages.
 */
class FieldBundleTranslationController extends ContentTranslationController {

  /**
   * {@inheritdoc}
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $build = parent::overview($route_match, $entity_type_id);
    /** @var \Drupal\field_bundle\FieldBundleInterface $entity */
    $entity = $build['#entity'];
    $build['#title'] = $this->t('Translations of field bundle %label with ID %id', [
      '%label' => $entity->label(),
      '%id' => $entity->id(),
    ]);
    return $build;
  }

}
