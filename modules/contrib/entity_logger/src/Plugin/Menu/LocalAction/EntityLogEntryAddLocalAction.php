<?php

namespace Drupal\entity_logger\Plugin\Menu\LocalAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Modifies the 'Add log entry' local action.
 */
class EntityLogEntryAddLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $route_parameters = parent::getRouteParameters($route_match);
    $type = $route_match->getRouteObject()->getOption('_entity_logger_entity_type_id');
    if ($type) {
      $route_parameters['entity_type'] = $type;
      $entity = $route_match->getParameter($type);
      if ($entity instanceof EntityInterface) {
        $route_parameters['entity'] = $entity->id();
      }
    }
    return $route_parameters;
  }

}
