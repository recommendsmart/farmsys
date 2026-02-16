<?php

namespace Drupal\group_field_bundle\Routing;

use Drupal\field_bundle\Entity\FieldBundleConfig;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_node group content.
 */
class GroupFieldBundleRouteProvider {

  /**
   * Provides the shared collection route for group node plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (array_keys(FieldBundleConfig::loadMultiple()) as $name) {
      $plugin_id = "group_field_bundle:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no field bundle configurations yet, we cannot have any
    // plugin IDs and should therefore exit early.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_content.group_field_bundle_relate_page'] = new Route('group/{group}/field-bundle/add');
    $routes['entity.group_content.group_field_bundle_relate_page']
      ->setDefaults([
        '_title' => 'Add existing field bundle',
        '_controller' => '\Drupal\group_field_bundle\Controller\GroupFieldBundleController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_field_bundle_add_page'] = new Route('group/{group}/field-bundle/create');
    $routes['entity.group_content.group_field_bundle_add_page']
      ->setDefaults([
        '_title' => 'Add new field bundle',
        '_controller' => '\Drupal\group_field_bundle\Controller\GroupFieldBundleController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
