<?php

namespace Drupal\entity_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller to render logging pages.
 */
class EntityLoggerController extends ControllerBase {

  /**
   * Returns the log for a given entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function log(RouteMatchInterface $route_match) {
    $output = [];

    $entity = $this->getEntityFromRouteMatch($route_match);

    if ($entity instanceof EntityInterface) {
      $output['log'] = [
        '#type' => 'view',
        '#name' => 'entity_logger',
        '#display_id' => 'embed_entity_log',
        '#arguments' => [$entity->getEntityTypeId(), $entity->id()],
        '#embed' => TRUE,
        '#title' => $this->t('Log'),
      ];
    }

    return $output;
  }

  /**
   * Render page title for log page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function pageTitle(RouteMatchInterface $route_match) {
    $target_entity = $this->getEntityFromRouteMatch($route_match);
    return $this->t('Logs for @label', ['@label' => $target_entity->label()]);
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_entity_logger_entity_type_id');
    return $route_match->getParameter($parameter_name);
  }

}
