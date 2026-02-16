<?php

namespace Drupal\group_storage\Routing;

use Drupal\storage\Entity\StorageType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_storage group content.
 */
class GroupStorageRouteProvider {

  /**
   * Provides the shared collection route for group storage plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (StorageType::loadMultiple() as $name => $storage_type) {
      $plugin_id = "group_storage:$name";
      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }
    // If there are no storage types yet, we cannot have any plugin IDs and should
    // therefore exit early because we cannot have any routes for them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_content.group_storage_relate_page'] = new Route('group/{group}/storage/add');
    $routes['entity.group_content.group_storage_relate_page']
      ->setDefaults([
        '_title' => 'Add existing content',
        '_controller' => '\Drupal\group_storage\Controller\GroupStorageController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_storage_add_page'] = new Route('group/{group}/storage/create');
    $routes['entity.group_content.group_storage_add_page']
      ->setDefaults([
        '_title' => 'Add new storage',
        '_controller' => '\Drupal\group_storage\Controller\GroupStorageController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
