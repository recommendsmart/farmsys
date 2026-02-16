<?php

namespace Drupal\collection\Plugin\views\access;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Views access plugin for the collection items listing.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "collection_items_access",
 *   title = @Translation("Collection items access"),
 *   help = @Translation("Grants access via the CollectionItemsAccessCheck service.")
 * )
 */
class CollectionItemsAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Collection items access');
  }

  /**
   * {@inheritdoc}
   *
   * All validation done in route. Must be TRUE or controller will render an
   * empty page.
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * This method is called when the view is saved.
   */
  public function alterRouteDefinition(Route $route) {
    // Ensure that the `_collection_items_access` requirement is set. It's used
    // for the `entity.collection_item.collection` route, but would be missing
    // for a View that uses the same path. This requirement service tag was
    // created by the `collection_item.collection.access_checker` service. Note
    // that it will only work if the %collection parameter has been upcast. That
    // could have happened here, but is done in
    // CollectionRouteSubscriber::alterRoutes() instead so that the collection
    // parameter will be upcast even if a View doesn't use this access plugin.
    // Another option would be to set the '_entity_access' requirement to
    // 'collection.update', like the Views Entity Operation Access module would.
    $route->setRequirement('_collection_items_access', 'TRUE');
  }

}
