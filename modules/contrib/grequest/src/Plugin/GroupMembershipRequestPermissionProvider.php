<?php

namespace Drupal\grequest\Plugin;

use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Provides group permissions for group content entities.
 */
class GroupMembershipRequestPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function getEntityCreatePermission() {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityDeletePermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityViewPermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityUpdatePermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationViewPermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationUpdatePermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationDeletePermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationCreatePermission($scope = 'any') {
    // Handled by the admin permission.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = parent::buildPermissions();

    // Update the title to make user friendly.
    $permissions['administer membership requests']['title'] = 'Administer membership requests';

    $permissions['request group membership'] = [
      'title' => 'Request group membership',
      'allowed for' => ['outsider'],
    ];

    return $permissions;
  }

}
