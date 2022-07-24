<?php

namespace Drupal\basket\Admin;

use Drupal\Core\Render\Markup;
use Drupal\Core\Access\AccessResult;

/**
 * {@inheritdoc}
 */
class ManagerMenu {

  /**
   * {@inheritdoc}
   */
  public static function block() {
    $elements = [];
    // menu_tree.
    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters('basket');
    $tree = $menu_tree->load('basket', $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => __CLASS__ . '::checkAccess'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $menu = $menu_tree->build($tree);
    if (!empty($menu)) {
      $elements = [
        'user'      => \Drupal::service('Basket')->full('getUserInfo'),
        'menu'        => [
          '#theme'    => 'basket_admin_menu',
          '#info'        => [
            'menu'      => $menu,
            'logo'      => \Drupal::service('Basket')->getLogo(),
          ],
        ],
      ];
    }
    // End menu.
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function checkAccess(array $tree) {
    foreach ($tree as $key => &$element) {
      $options = $element->link->getOptions();
      if (!empty($options['basket_access']) && !\Drupal::currentUser()->hasPermission($options['basket_access'])) {
        $element->access = AccessResult::forbidden()->cachePerPermissions();
      }
      if (!empty($element->subtree)) {
        self::checkAccess($element->subtree);
      }
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function linkAlter(&$vars) {
    if (!empty($vars['url'])) {
      $options = $vars['url']->getOptions();
      if (!empty($options['ico_name'])) {
        $ico_module = !empty($options['ico_module']) ? $options['ico_module'] : 'basket';
        $vars['text'] = Markup::create('<span class="ico">' . \Drupal::service('Basket')->getIco($options['ico_name'], $ico_module) . '</span> <span class="text">' . $vars['text'] . '</span>');
      }
      if (!empty($options['view_count']) && !empty($count = \Drupal::service('Basket')->getCounts($options['view_count']))) {
        $vars['text'] = Markup::create($vars['text'] . '<span class="count">' . $count . '</span>');
      }
    }
    if (!$vars['url']->isExternal()) {
      $pathCurrent = \Drupal::service('path.current')->getPath();
      switch ($vars['url']->getRouteName()) {
        case'basket.admin.pages':
          $params = $vars['url']->getRouteParameters();
          if (strpos($pathCurrent, 'admin/basket/orders-') !== FALSE && empty($params)) {
            $vars['options']['attributes']['class'][] = 'is-active';
          }
          break;
      }
    }
  }

}
