<?php

namespace Drupal\group_flex_content\Plugin;

use Drupal\gnode\Plugin\GroupNodePermissionProvider;

/**
 * Provides group permissions for GroupContent entities.
 */
class GroupFlexContentPermissionProvider extends GroupNodePermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = parent::buildPermissions();

    // Provide permissions for the content visibility options.
    $prefix = 'Entity:';
    $permissions["use visibility anonymous for $this->pluginId entity"] = [
      'title' => "$prefix Use visibility option 'Any visitors of the website'",
    ];
    $permissions["use visibility outsider for $this->pluginId entity"] = [
      'title' => "$prefix Use visibility option 'Users registered on the website only'",
    ];
    $permissions["use visibility member for $this->pluginId entity"] = [
      'title' => "$prefix Use visibility option 'Members only'",
    ];

    return $permissions;
  }

}
