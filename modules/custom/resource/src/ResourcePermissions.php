<?php

/**
 * @file
 * Contains \Drupal\resource\ResourcePermissions.
 */

namespace Drupal\resource;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\resource\Entity\ResourceType;

/**
 * Provides dynamic permissions for resources of different types.
 */
class ResourcePermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of resource type permissions.
   *
   * @return array
   *   The resource type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function resourceTypePermissions() {
    $perms = array();
    // Generate resource permissions for all resource types.
    foreach (ResourceType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of resource permissions for a given resource type.
   *
   * @param \Drupal\resource\Entity\ResourceType $type
   *   The resource type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ResourceType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());
    $ops = array('view', 'edit', 'delete');
    $scopes = array('any', 'own');

    $permissions = [];
    $permissions["create $type_id resource entities"] = [
      'title' => $this->t('%type_name: Create new resource entities', $type_params),
    ];
    foreach ($ops as $op) {
      foreach ($scopes as $scope) {
        $scope_params = $type_params + ['%scope' => $scope, '%op' => ucfirst($op)];
        $permissions["$op $scope $type_id resource entities"] = [
          'title' => $this->t('%type_name: %op %scope resource entities', $scope_params),
        ];
      }
    }
    $permissions["view $type_id revisions"] = [
      'title' => $this->t('%type_name: View resource revisions', $type_params),
    ];
    $permissions["revert $type_id revisions"] = [
      'title' => $this->t('%type_name: Revert resource revisions', $type_params),
    ];
    $permissions["delete $type_id revisions"] = [
      'title' => $this->t('%type_name: Delete resource revisions', $type_params),
    ];
    return $permissions;
  }

}
