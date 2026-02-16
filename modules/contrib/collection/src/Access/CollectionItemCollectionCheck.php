<?php
namespace Drupal\collection\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Access\AccessResult;

/**
 * Route access check on both {collection} and {collection_item} parameters.
 *
 * This checker simply confirms that the collection for the upcast
 * {collection_item} parameter matches the upcast {collection}
 */
class CollectionItemCollectionCheck implements AccessInterface {

  /**
   * The access check.
   *
   * @param Drupal\Core\Routing\RouteMatch $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatch $route_match) {
    $collection = $route_match->getParameter('collection');
    $collection_item = $route_match->getParameter('collection_item');
    return AccessResult::allowedIf($collection === $collection_item->collection->entity);
  }

}
