<?php

namespace Drupal\banana_dashboard\Controller;

use Drupal\core\Url;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Banana Dashboard module.
 */
class BananaDashboardController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getBananaDashboard() {
    $dashboard_menu = banana_dashboard_get('dashboard_menu', []);
    foreach ($dashboard_menu as $key => $value) {
      if (empty($value['url']) || !\Drupal::service('path.validator')->isValid(substr($value['url'], 1))) {
        unset($dashboard_menu[$key]);
      }
      unset($dashboard_menu['id']);
      unset($dashboard_menu['provider']);
    }

    $dashboard = banana_dashboard_get('dashboard', []);
    $groups = banana_dashboard_get('dashboard_menu_groups', ['System']);
    $menu_group = [];
    foreach ($groups as $group) {
      $menu_group[$group] = [];
    }

    $legacy_icons_map = _banana_dashboard_legacy_icon_map();

    foreach ($dashboard_menu as $menu) {
      $group = isset($menu['group']) ? $menu['group'] : 'System';
      // Replace the legacy icons with fa-icons.
      if (isset($legacy_icons_map[$menu['icon']])) {
        $menu['icon'] = $legacy_icons_map[$menu['icon']];
      }
      // Handle domain prefixes.
      $menu['url'] = (Url::fromRoute($menu['url']))->getRouteName();
      $menu_group[$group][] = $menu;
    }
    foreach ($menu_group as $group => $menu) {
      if (empty($menu)) {
        unset($menu_group[$group]);
      }
    }
    return [
      '#theme' => 'banana_dashboard',
      '#title' => $dashboard['title'],
      '#dashboard_menu' => $menu_group,
    ];
  }

}
